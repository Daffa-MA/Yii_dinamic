<?php

namespace app\controllers;

use Yii;
use app\components\RelationMapper;
use yii\db\Connection;
use yii\db\IntegrityException;
use yii\db\Query;
use yii\helpers\Url;
use yii\helpers\HtmlPurifier;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\models\Form;
use app\models\FormSubmission;
use app\models\PublishedForm;
use app\models\DbTable;
use app\models\Project;
use app\components\ActiveProjectContext;
use app\components\ActiveDatabaseContext;
use app\components\CommanderAuthContext;
use app\components\FormFlowDebugLogger;
use app\components\ProjectAuthContext;
use app\components\ProjectPermissionService;
use app\components\SystemFieldService;
use app\models\ProjectUser;
use app\components\ProjectSchema;
use app\helpers\FormSystemFieldHelper;

class FormController extends Controller
{
    /** @var RelationMapper|null */
    private $relationMapper;

    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    private function assignActiveProject(Form $model): void
    {
        if (!$model->hasAttribute('project_id')) {
            return;
        }

        $activeProjectId = $this->getActiveProjectId();
        $model->project_id = $activeProjectId !== null ? (int)$activeProjectId : null;
    }

    private function getWorkspaceAuthenticatedUser(?int $projectId = null): ?ProjectUser
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $resolvedProjectId = $projectId ?? $this->getActiveProjectId();
        if ($resolvedProjectId === null) {
            return null;
        }

        return (new ProjectAuthContext())->getAuthenticatedUser($resolvedProjectId);
    }

    private function getEffectiveUserId(): ?int
    {
        $workspaceUser = $this->getWorkspaceAuthenticatedUser();
        if ($workspaceUser !== null) {
            return (int)$workspaceUser->id;
        }

        if (!Yii::$app->user->isGuest && Yii::$app->user->id !== null) {
            return (int)Yii::$app->user->id;
        }

        return null;
    }

    private function canAccessWorkspaceFormController(): bool
    {
        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return true;
        }

        if (!Yii::$app->user->isGuest && method_exists(Yii::$app->user->identity, 'isSuperAdmin') && Yii::$app->user->identity->isSuperAdmin()) {
            return true;
        }

        return $this->getWorkspaceAuthenticatedUser() !== null;
    }

    private function shouldBypassProjectContext(string $actionId): bool
    {
        return in_array($actionId, ['render', 'submit', 'public-render', 'success', 'fk-options', 'fk-quick-add'], true);
    }

    private function getRelationMapper(?Connection $db = null): RelationMapper
    {
        return new RelationMapper($db);
    }

    private function getForeignKeyConfigForForm(Form $form): array
    {
        $targetTable = $this->findTargetTableForForm($form);
        if ($targetTable === null || !(bool)$targetTable->is_created) {
            return [];
        }

        $projectId = $form->hasAttribute('project_id') ? (int)$form->project_id : null;
        $db = $this->getPhysicalDb($projectId);

        return $this->getRelationMapper($db)->buildForeignKeyConfig($targetTable);
    }

    private function isIncrementColumn(array $column): bool
    {
        return SystemFieldService::isPrimaryKey($column) || SystemFieldService::isAutoIncrement($column);
    }

    /**
     * Filter out primary key and auto increment columns from form schema while preserving structure.
     *
     * @param Form $form
     * @return string
     */
    private function getFilteredSchemaJs(Form $form): string
    {
        $schemaJs = (string)$form->schema_js;
        if ($schemaJs === '' || $schemaJs === '[]') {
            return '[]';
        }

        $targetTable = $this->findTargetTableForForm($form);
        if ($targetTable === null) {
            return $schemaJs;
        }

        $columns = $targetTable->getColumns()->asArray()->all();
        $hiddenNames = [];
        foreach ($columns as $col) {
            if (SystemFieldService::shouldHideFromForm($col)) {
                $name = strtolower(trim((string)($col['name'] ?? '')));
                if ($name !== '') {
                    $hiddenNames[$name] = true;
                }
            }
        }

        if (empty($hiddenNames)) {
            return $schemaJs;
        }

        $data = json_decode($schemaJs, true);
        if (!is_array($data)) {
            return $schemaJs;
        }

        $filterBlocks = function($blocks) use ($hiddenNames) {
            if (!is_array($blocks)) return $blocks;
            $filtered = [];
            foreach ($blocks as $block) {
                if (!is_array($block)) continue;
                $name = strtolower(trim((string)($block['name'] ?? $block['label'] ?? '')));
                if (isset($hiddenNames[$name])) {
                    continue;
                }
                $filtered[] = $block;
            }
            return $filtered;
        };

        if (isset($data['pages']) && is_array($data['pages'])) {
            foreach ($data['pages'] as $i => $page) {
                if (isset($page['blocks'])) {
                    $data['pages'][$i]['blocks'] = $filterBlocks($page['blocks']);
                }
            }
        }

        if (isset($data['blocks']) && is_array($data['blocks'])) {
            $data['blocks'] = $filterBlocks($data['blocks']);
        }

        if (!isset($data['pages']) && !isset($data['blocks']) && array_values($data) === $data) {
            $data = $filterBlocks($data);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get flattened filtered blocks for rendering.
     *
     * @param Form $form
     * @return array<int, array<string, mixed>>
     */
    private function getFilteredBlocks(Form $form): array
    {
        $filteredJs = $this->getFilteredSchemaJs($form);
        $tempForm = new Form();
        $tempForm->schema_js = $filteredJs;
        return $tempForm->getSchema();
    }

    private function resolveFormTargetTableInfo(Form $form): array
    {
        if (method_exists($form, 'getEffectiveTableId')) {
            $effectiveTableId = (int)($form->getEffectiveTableId() ?? 0);
            if ($effectiveTableId > 0) {
                return ['id' => $effectiveTableId, 'source' => 'effective_table_id'];
            }
        }

        if ($form->hasAttribute('db_table_id')) {
            $newId = (int)$form->getAttribute('db_table_id');
            if ($newId > 0) {
                return ['id' => $newId, 'source' => 'db_table_id'];
            }
        }

        if ($form->hasAttribute('table_id')) {
            $legacyId = (int)$form->getAttribute('table_id');
            if ($legacyId > 0) {
                return ['id' => $legacyId, 'source' => 'table_id'];
            }
        }

        return ['id' => 0, 'source' => ''];
    }

    private function resolveFormTargetTableId(Form $form): int
    {
        $info = $this->resolveFormTargetTableInfo($form);
        return (int)($info['id'] ?? 0);
    }

    private function shouldInsertDirectlyToTable(Form $form): bool
    {
        if (method_exists($form, 'shouldInsertToTargetTable')) {
            return $form->shouldInsertToTargetTable();
        }

        if ($form->hasAttribute('insert_to_table')) {
            return (int)$form->getAttribute('insert_to_table') === 1;
        }

        return $form->storage_type === 'database';
    }

    private function findTargetTableForForm(Form $form): ?DbTable
    {
        $info = $this->resolveFormTargetTableInfo($form);
        $tableId = (int)($info['id'] ?? 0);
        if ($tableId <= 0) {
            return null;
        }

        $criteria = ['id' => $tableId];
        if (ProjectSchema::supportsProjectContext() && $form->hasAttribute('project_id') && (int)$form->project_id > 0) {
            $criteria['project_id'] = (int)$form->project_id;
        }

        return DbTable::findOne($criteria);
    }

    private function resolveTargetTableSchemaForForm(Form $form)
    {
        $targetTable = $this->findTargetTableForForm($form);
        if ($targetTable === null || !(bool)$targetTable->is_created) {
            return null;
        }

        try {
            return Yii::$app->db->schema->getTableSchema((string)$targetTable->name, true);
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve target table schema for form ' . (int)$form->id . ': ' . $e->getMessage(), 'form-submit');
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $schema
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFormSchemaFields(array $schema, Form $form, &$mappingDebug = null): array
    {
        $tableSchema = $this->resolveTargetTableSchemaForForm($form);
        if ($tableSchema === null || empty($schema)) {
            return $schema;
        }

        $normalized = [];
        $debugRows = [];
        foreach ($schema as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $resolved = $this->normalizeFormFieldAgainstSchema($field, (int)$index, $tableSchema, $mappingDebugRow);
            $normalized[] = $resolved;
            $debugRows[] = $mappingDebugRow;
        }

        if (is_array($mappingDebug)) {
            $mappingDebug = $debugRows;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $debugRow
     * @return array<string, mixed>
     */
    private function normalizeFormFieldAgainstSchema(array $field, int $index, $tableSchema, &$debugRow = []): array
    {
        $sourceColumn = null;
        $sourceColumnId = (int)($field['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = \app\models\DbTableColumn::findOne($sourceColumnId);
        }

        $resolvedName = $this->resolveSchemaColumnNameFromField($field, $index, $tableSchema, $sourceColumn, $debugRow);
        $resolvedLabel = $this->resolveSchemaFieldLabelFromField($field, $resolvedName, $sourceColumn);
        $resolvedType = trim((string)($field['type'] ?? $field['field_type'] ?? $field['inputType'] ?? 'text'));
        $resolvedInputType = trim((string)($field['inputType'] ?? $resolvedType));
        if ($resolvedInputType === '') {
            $resolvedInputType = $resolvedType !== '' ? $resolvedType : 'text';
        }

        $resolvedField = $field;
        $resolvedField['original_name'] = trim((string)($field['original_name'] ?? $field['name'] ?? ''));
        $resolvedField['resolved_name'] = $resolvedName;
        $resolvedField['resolved_column_name'] = $resolvedName;
        $resolvedField['resolved_label'] = $resolvedLabel;
        $resolvedField['name'] = $resolvedName;
        $resolvedField['field_name'] = $resolvedName;
        $resolvedField['field_key'] = $resolvedName;
        $resolvedField['column_name'] = $resolvedName;
        $resolvedField['label'] = $resolvedLabel;
        $resolvedField['field_label'] = $resolvedLabel;
        $resolvedField['type'] = $resolvedType !== '' ? $resolvedType : 'text';
        $resolvedField['inputType'] = $resolvedInputType;
        $resolvedField['source_column_name'] = $sourceColumn !== null ? (string)$sourceColumn->name : (string)($field['source_column_name'] ?? '');
        $resolvedField['source_column_label'] = $sourceColumn !== null ? (string)($sourceColumn->label ?? $sourceColumn->name) : (string)($field['source_column_label'] ?? '');
        $resolvedField['source_column_type'] = $sourceColumn !== null ? (string)($sourceColumn->type ?? '') : (string)($field['source_column_type'] ?? '');

        $relationConfig = $this->extractRelationConfig($field);
        if (!empty($relationConfig)) {
            $resolvedField['relation_config'] = $relationConfig;
        }

        if (!empty($field['is_foreign_key']) || !empty($field['fk_referenced_table']) || !empty($relationConfig['referenced_table']) || !empty($relationConfig['referenced_table_name'])) {
            $resolvedField['is_foreign_key'] = true;
            $resolvedField['fk_referenced_table'] = (string)($field['fk_referenced_table'] ?? $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? '');
            $resolvedField['fk_referenced_column'] = (string)($field['fk_referenced_column'] ?? $relationConfig['referenced_column'] ?? $relationConfig['referenced_column_name'] ?? '');
            $resolvedField['fk_display_column'] = (string)($field['fk_display_column'] ?? $relationConfig['display_column'] ?? $relationConfig['display_column_name'] ?? '');
        }

        if (($resolvedField['is_foreign_key'] ?? false) && !in_array($resolvedField['inputType'], ['select', 'radio', 'checkboxes'], true)) {
            $resolvedField['inputType'] = 'select';
            $resolvedField['type'] = 'select';
        }

        $debugRow = [
            'raw_field' => trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? '')),
            'label' => trim((string)($field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? '')),
            'resolved_column' => $resolvedName,
            'resolved_label' => $resolvedLabel,
            'candidates' => $debugRow['candidates'] ?? [],
            'reason' => $debugRow['reason'] ?? '',
        ];

        return $resolvedField;
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed>|null $sourceColumn
     * @param array<string, mixed> $debugRow
     */
    private function resolveSchemaColumnNameFromField(array $field, int $index, $tableSchema, $sourceColumn = null, &$debugRow = []): string
    {
        $schemaLookup = $this->buildSchemaColumnLookup($tableSchema);
        $identityCandidates = [];
        $labelCandidates = [];
        foreach ([
            $field['name'] ?? null,
            $field['field_name'] ?? null,
            $field['field_key'] ?? null,
            $field['column_name'] ?? null,
            $field['original_column'] ?? null,
            $field['local_column'] ?? null,
            $field['source_column'] ?? null,
            $field['source_column_name'] ?? null,
            $field['relation_target_column'] ?? null,
            $field['relation_value_column'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $identityCandidates[] = trim($candidate);
            }
        }

        $relationConfig = $this->extractRelationConfig($field);
        foreach ([
            $relationConfig['local_column'] ?? null,
            $relationConfig['source_column'] ?? null,
            $relationConfig['column_name'] ?? null,
            $relationConfig['original_column'] ?? null,
            $relationConfig['field_name'] ?? null,
            $relationConfig['field_key'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $identityCandidates[] = trim($candidate);
            }
        }

        if ($sourceColumn !== null && !empty($sourceColumn->name)) {
            array_unshift($identityCandidates, (string)$sourceColumn->name);
        }

        foreach ([
            $field['label'] ?? null,
            $field['field_label'] ?? null,
            $field['labelText'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $labelCandidates[] = trim($candidate);
            }
        }

        $resolved = $this->matchSchemaColumnCandidate($identityCandidates, $schemaLookup, $debugRow);
        if (($resolved === null || $resolved === '') && !empty($labelCandidates)) {
            $resolved = $this->matchSchemaColumnCandidate($labelCandidates, $schemaLookup, $debugRow);
        }
        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        $fallback = 'field_' . ($index + 1);
        $debugRow['reason'] = 'no matching schema column';
        return $fallback;
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed>|null $sourceColumn
     */
    private function resolveSchemaFieldLabelFromField(array $field, string $fieldName, $sourceColumn = null): string
    {
        $label = trim((string)($field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? ''));
        if ($sourceColumn !== null) {
            $sourceLabel = trim((string)($sourceColumn->label ?? ''));
            if ($sourceLabel !== '' && ($label === '' || !$this->labelMatchesFieldName($label, $fieldName))) {
                $label = $sourceLabel;
            }
        }

        if ($label === '' || !$this->labelMatchesFieldName($label, $fieldName)) {
            $label = $fieldName !== '' ? ucwords(str_replace('_', ' ', $fieldName)) : 'Field';
        }

        return $label;
    }

    /**
     * @param mixed $tableSchema
     * @return array<string, string>
     */
    private function buildSchemaColumnLookup($tableSchema): array
    {
        $lookup = [];
        if ($tableSchema === null || empty($tableSchema->columns)) {
            return $lookup;
        }

        foreach ($tableSchema->columns as $columnName => $column) {
            $columnName = (string)$columnName;
            $aliases = [
                $columnName,
                $this->normalizeInputKey($columnName),
                $this->normalizeInputKey(ucwords(str_replace('_', ' ', $columnName))),
            ];

            if (strpos($this->normalizeInputKey($columnName), '_id') !== false) {
                $aliases[] = preg_replace('/_id$/', '', $this->normalizeInputKey($columnName)) ?: '';
            }

            foreach ([
                $column->label ?? null,
                $column->comment ?? null,
            ] as $aliasValue) {
                if (is_string($aliasValue) && trim($aliasValue) !== '') {
                    $aliases[] = trim($aliasValue);
                    $aliases[] = $this->normalizeInputKey(trim($aliasValue));
                }
            }

            foreach (array_values(array_filter($aliases)) as $alias) {
                $normalizedAlias = $this->normalizeInputKey((string)$alias);
                if ($normalizedAlias === '') {
                    continue;
                }
                if (!isset($lookup[$normalizedAlias])) {
                    $lookup[$normalizedAlias] = $columnName;
                }
            }
        }

        return $lookup;
    }

    /**
     * @param array<int, string> $candidates
     * @param array<string, string> $lookup
     * @param array<string, mixed> $debugRow
     */
    private function matchSchemaColumnCandidate(array $candidates, array $lookup, &$debugRow = []): ?string
    {
        $bestMatch = null;
        $bestScore = 0.0;
        $debugCandidates = [];
        foreach (array_values(array_unique(array_filter(array_map('trim', $candidates)))) as $candidate) {
            $normalizedCandidate = $this->normalizeInputKey($candidate);
            if ($normalizedCandidate === '') {
                continue;
            }

            $debugCandidates[] = $candidate;
            if (isset($lookup[$candidate])) {
                $debugRow['candidates'] = $debugCandidates;
                $debugRow['reason'] = 'exact raw candidate match';
                return $lookup[$candidate];
            }
            if (isset($lookup[$normalizedCandidate])) {
                $debugRow['candidates'] = $debugCandidates;
                $debugRow['reason'] = 'exact normalized candidate match';
                return $lookup[$normalizedCandidate];
            }

            $candidateTokens = array_values(array_filter(explode('_', $normalizedCandidate)));
            foreach ($lookup as $alias => $columnName) {
                $normalizedAlias = $this->normalizeInputKey($alias);
                if ($normalizedAlias === '') {
                    continue;
                }
                $aliasTokens = array_values(array_filter(explode('_', $normalizedAlias)));

                $score = 0.0;
                if ($normalizedAlias === $normalizedCandidate) {
                    $score = 100.0;
                } elseif (count($candidateTokens) > 1 && count($aliasTokens) > 1 && empty(array_diff($candidateTokens, $aliasTokens)) && empty(array_diff($aliasTokens, $candidateTokens))) {
                    $score = 98.0;
                } elseif (
                    $normalizedAlias !== 'id'
                    && $normalizedCandidate !== 'id'
                    && (str_contains($normalizedAlias, $normalizedCandidate) || str_contains($normalizedCandidate, $normalizedAlias))
                ) {
                    $score = 80.0;
                } else {
                    $intersection = array_intersect($candidateTokens, $aliasTokens);
                    $union = array_unique(array_merge($candidateTokens, $aliasTokens));
                    if (!empty($union)) {
                        $score = (count($intersection) / count($union)) * 70.0;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $columnName;
                }
            }
        }

        $debugRow['candidates'] = $debugCandidates;
        $debugRow['score'] = $bestScore;
        $debugRow['reason'] = $bestMatch !== null && $bestScore >= 45.0 ? 'fuzzy schema match' : 'no matching schema column';

        return $bestScore >= 45.0 ? $bestMatch : null;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function extractRelationConfig(array $field): array
    {
        foreach (['relation_config', 'relationConfig', 'relation'] as $key) {
            if (isset($field[$key]) && is_array($field[$key])) {
                return $field[$key];
            }
        }

        return [];
    }

    private function labelMatchesFieldName(string $label, string $fieldName): bool
    {
        $labelTokens = array_values(array_filter(explode('_', $this->normalizeInputKey($label))));
        $fieldTokens = array_values(array_filter(explode('_', $this->normalizeInputKey($fieldName))));
        if (empty($labelTokens) || empty($fieldTokens)) {
            return false;
        }

        return count(array_intersect($labelTokens, $fieldTokens)) > 0;
    }

    private function ensureGuestCanAccessPublicForm(Form $form): void
    {
        if ($this->canAccessFormAsAuthorizedPageContent((int)$form->id)) {
            return;
        }

        if (!Yii::$app->user->isGuest) {
            return;
        }

        $isPublished = PublishedForm::find()
            ->where(['form_id' => (int)$form->id])
            ->exists();
        if (!$isPublished) {
            throw new NotFoundHttpException('The requested form does not exist.');
        }
    }

    private function canAccessFormAsAuthorizedPageContent(int $formId): bool
    {
        $renderContext = (string)Yii::$app->request->post('render_context', Yii::$app->request->get('render_context', ''));
        if ($renderContext !== 'page_content') {
            return false;
        }

        $pageId = (int)Yii::$app->request->post('page_id', Yii::$app->request->get('page_id', 0));
        if ($formId <= 0 || $pageId <= 0) {
            return false;
        }

        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return true;
        }

        if ($projectId === null || !(new ProjectAuthContext())->isAuthenticated($projectId)) {
            return false;
        }

        return (new ProjectPermissionService())->canUseLegacyFormAsPageContent($formId, $pageId, $projectId);
    }

    private function stripForeignKeySuffix(string $value): string
    {
        $normalized = $this->normalizeInputKey($value);
        if ($normalized !== '' && substr($normalized, -3) === '_id') {
            return substr($normalized, 0, -3);
        }

        return $normalized;
    }

    /**
     * @param array<string, array<string, mixed>> $fkConfig
     */
    private function resolveForeignKeyKey(array $fkConfig, string $fieldName, string $fieldLabel = ''): ?string
    {
        if ($fieldName !== '' && isset($fkConfig[$fieldName]) && is_array($fkConfig[$fieldName])) {
            return $fieldName;
        }

        $normalizedFieldName = $this->normalizeInputKey($fieldName);
        $normalizedFieldWithoutId = $this->stripForeignKeySuffix($fieldName);

        foreach ($fkConfig as $key => $config) {
            if (!is_array($config)) {
                continue;
            }

            $candidateFields = [
                (string)$key,
                (string)($config['field'] ?? ''),
            ];

            foreach ($candidateFields as $candidateField) {
                if ($candidateField === '') {
                    continue;
                }

                if ($candidateField === $fieldName) {
                    return (string)$key;
                }

                $normalizedCandidate = $this->normalizeInputKey($candidateField);
                if ($normalizedCandidate === '') {
                    continue;
                }

                if ($normalizedFieldName !== '' && $normalizedCandidate === $normalizedFieldName) {
                    return (string)$key;
                }

                if ($normalizedFieldWithoutId !== '' && $this->stripForeignKeySuffix($candidateField) === $normalizedFieldWithoutId) {
                    return (string)$key;
                }
            }
        }

        return null;
    }

    /**
     * Align FK config key with schema field names used by public-render.
     *
     * @param array<int, array<string, mixed>> $schema
     * @param array<string, array<string, mixed>> $fkConfig
     * @return array<string, array<string, mixed>>
     */
    private function mapForeignKeyConfigToSchema(array $schema, array $fkConfig): array
    {
        if (empty($schema) || empty($fkConfig)) {
            return $fkConfig;
        }

        $resolvedConfig = $fkConfig;
        foreach ($schema as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldName = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            $fieldLabel = trim((string)($field['label'] ?? $fieldName));
            $resolvedKey = $this->resolveForeignKeyKey($fkConfig, $fieldName, $fieldLabel);
            if ($resolvedKey === null || !isset($fkConfig[$resolvedKey]) || !is_array($fkConfig[$resolvedKey])) {
                continue;
            }

            $mappedConfig = $fkConfig[$resolvedKey];
            $mappedConfig['sourceField'] = (string)($mappedConfig['field'] ?? $resolvedKey);
            $mappedConfig['field'] = $fieldName;
            if (!isset($mappedConfig['fieldLabel']) || trim((string)$mappedConfig['fieldLabel']) === '') {
                $mappedConfig['fieldLabel'] = $fieldLabel;
            }

            $resolvedConfig[$fieldName] = $mappedConfig;
        }

        return $resolvedConfig;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getResolvedForeignKeyConfigForForm(Form $form): array
    {
        $fkConfig = $this->getForeignKeyConfigForForm($form);
        if (empty($fkConfig)) {
            return [];
        }

        return $this->mapForeignKeyConfigToSchema($form->getSchema(), $fkConfig);
    }

    private function buildFriendlyIntegrityErrorMessage(IntegrityException $exception, Form $form): string
    {
        $message = $exception->getMessage();
        $isForeignKeyError = stripos($message, 'foreign key constraint fails') !== false;
        if (!$isForeignKeyError) {
            return 'Data gagal disimpan karena constraint database. Mohon periksa kembali input Anda.';
        }

        $fieldLabel = null;
        $constraintName = null;
        $columnName = null;
        if (preg_match('/CONSTRAINT [`"]?([^`"\\s]+)[`"]?/i', $message, $constraintMatch)) {
            $constraintName = $constraintMatch[1];
        }
        if (preg_match('/FOREIGN KEY \\(`([^`]+)`\\)/i', $message, $columnMatch)) {
            $columnName = $columnMatch[1];
        }

        $fkConfig = $this->getForeignKeyConfigForForm($form);
        foreach ($fkConfig as $field => $config) {
            $configConstraint = isset($config['constraintName']) ? (string)$config['constraintName'] : '';
            $configField = isset($config['field']) ? (string)$config['field'] : (string)$field;

            if ($constraintName !== null && $configConstraint !== '' && strcasecmp($configConstraint, $constraintName) === 0) {
                $fieldLabel = isset($config['fieldLabel']) ? (string)$config['fieldLabel'] : $configField;
                break;
            }
            if ($columnName !== null && strcasecmp($configField, $columnName) === 0) {
                $fieldLabel = isset($config['fieldLabel']) ? (string)$config['fieldLabel'] : $configField;
                break;
            }
        }

        if ($fieldLabel === null && $columnName !== null) {
            $fieldLabel = ucwords(str_replace('_', ' ', $columnName));
        }
        if ($fieldLabel === null || trim($fieldLabel) === '') {
            return 'Data relasi yang dipilih tidak valid. Pastikan memilih data yang tersedia.';
        }

        return "Data pada field '{$fieldLabel}' tidak valid. Pastikan memilih data yang tersedia.";
    }

    private function resolveInsertedReferenceValue(string $tableName, string $referencedColumn, array $insertData): ?string
    {
        if (array_key_exists($referencedColumn, $insertData)) {
            $explicitValue = $insertData[$referencedColumn];
            if ($explicitValue !== null && $explicitValue !== '') {
                return (string)$explicitValue;
            }
        }

        $lastInsertId = (string)Yii::$app->db->getLastInsertID();
        if ($lastInsertId !== '') {
            return $lastInsertId;
        }

        if (empty($insertData)) {
            return null;
        }

        $query = (new Query())
            ->select([$referencedColumn])
            ->from($tableName);
        foreach ($insertData as $columnName => $columnValue) {
            $query->andWhere([$columnName => $columnValue]);
        }

        $resolvedValue = $query
            ->orderBy([$referencedColumn => SORT_DESC])
            ->scalar();

        if ($resolvedValue === false || $resolvedValue === null || $resolvedValue === '') {
            return null;
        }

        return (string)$resolvedValue;
    }

    /**
     * Capture user-input fields that are posted outside generated schema mapping.
     */
    private function mergeAdditionalPostedInputs(array $data): array
    {
        $post = Yii::$app->request->post();
        $reservedKeys = [
            Yii::$app->request->csrfParam,
            'user_email',
            'user_name',
            'firebase_uid',
            'form_pages',
            'publish_now',
            'form_id',
        ];

        foreach ($post as $key => $value) {
            if (!is_string($key) || in_array($key, $reservedKeys, true) || array_key_exists($key, $data) || SystemFieldService::isSystemFieldData(['name' => $key])) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $data[$key] = (string) $value;
            }
        }

        // Capture uploaded file names so custom drag-drop file inputs are still recorded.
        foreach ($_FILES as $key => $fileMeta) {
            if (!is_string($key) || in_array($key, $reservedKeys, true) || array_key_exists($key, $data) || SystemFieldService::isSystemFieldData(['name' => $key])) {
                continue;
            }

            $fileName = $this->extractUploadedFileNames($fileMeta['name'] ?? null, $fileMeta['error'] ?? null);
            if ($fileName !== null) {
                $data[$key] = $fileName;
            }
        }

        return $data;
    }

    private function isInteractiveSubmissionField(array $field): bool
    {
        $type = strtolower(trim((string)($field['type'] ?? 'text-input')));
        $nonInputTypes = [
            'container', 'columns', 'grid', 'section',
            'heading', 'text', 'richtext', 'divider', 'spacer',
            'image', 'video', 'alert', 'button', 'submit', 'hidden',
        ];

        return !in_array($type, $nonInputTypes, true);
    }

    private function hasMeaningfulSubmissionValue($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->hasMeaningfulSubmissionValue($item)) {
                    return true;
                }
            }
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return true;
        }

        return trim((string)$value) !== '';
    }

    private function hasAtLeastOneFilledField(array $schema, array $data): bool
    {
        $lookup = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $lookup[$key] = $value;
            $normalizedKey = $this->normalizeInputKey($key);
            if ($normalizedKey !== '') {
                $lookup[$normalizedKey] = $value;
            }
        }

        foreach ($schema as $index => $field) {
            if (!is_array($field) || FormSystemFieldHelper::isSystemFieldData($field) || !$this->isInteractiveSubmissionField($field)) {
                continue;
            }

            $fieldName = $this->resolveSchemaFieldName($field, (int)$index);
            if ($fieldName === '') {
                continue;
            }

            $candidateKeys = [$fieldName];
            $normalizedFieldName = $this->normalizeInputKey($fieldName);
            if ($normalizedFieldName !== '') {
                $candidateKeys[] = $normalizedFieldName;
            }

            foreach ($candidateKeys as $candidateKey) {
                if ($candidateKey === '' || !array_key_exists($candidateKey, $lookup)) {
                    continue;
                }

                if ($this->hasMeaningfulSubmissionValue($lookup[$candidateKey])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Convert PHP $_FILES name/error structure into storable value.
     */
    private function extractUploadedFileNames($name, $error): ?string
    {
        if (is_array($name)) {
            $collected = [];
            foreach ($name as $idx => $childName) {
                $childError = is_array($error) && array_key_exists($idx, $error) ? $error[$idx] : UPLOAD_ERR_NO_FILE;
                $resolved = $this->extractUploadedFileNames($childName, $childError);
                if ($resolved !== null && $resolved !== '') {
                    $collected[] = $resolved;
                }
            }

            return !empty($collected) ? json_encode($collected, JSON_UNESCAPED_UNICODE) : null;
        }

        if ($error === UPLOAD_ERR_NO_FILE || $name === null || $name === '') {
            return null;
        }

        return (string) $name;
    }

    private function normalizeInputKey(string $key): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9_]+/i', '_', $key), '_'));
    }

    private function resolveSchemaFieldName(array $field, int $index = 0): string
    {
        $name = trim((string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $sourceColumnId = (int)($field['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = \app\models\DbTableColumn::findOne($sourceColumnId);
            if ($sourceColumn !== null && trim((string)$sourceColumn->name) !== '') {
                return (string)$sourceColumn->name;
            }
        }

        return 'field_' . ($index + 1);
    }

    private function resolveSchemaFieldLabel(array $field, string $fieldName): string
    {
        $label = trim((string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? ''));
        if ($label !== '') {
            return $label;
        }

        return $fieldName !== '' ? ucwords(str_replace('_', ' ', $fieldName)) : 'Field';
    }

    private function resolveSafeReturnUrl(): ?string
    {
        $returnUrl = trim((string) Yii::$app->request->post('return_url', Yii::$app->request->get('return_url', '')));
        if ($returnUrl === '') {
            return null;
        }

        if (Url::isRelative($returnUrl)) {
            return $returnUrl;
        }

        $hostInfo = rtrim((string) Yii::$app->request->hostInfo, '/');
        if ($hostInfo !== '' && str_starts_with($returnUrl, $hostInfo . '/')) {
            return substr($returnUrl, strlen($hostInfo));
        }

        return null;
    }

    private function supportsLengthConstraint(array $column): bool
    {
        $type = strtoupper(trim((string)($column['type'] ?? '')));
        return in_array($type, [
            'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT',
            'BINARY', 'VARBINARY', 'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'LONGBLOB',
        ], true);
    }

    /**
     * @param array<int, array<string, mixed>> $schema
     * @return array{byField: array<string, array<string, mixed>>, lookup: array<string, array<string, mixed>>}
     */
    private function buildFormFieldConstraints(Form $form, array $schema = []): array
    {
        $byField = [];
        $lookup = [];
        $targetTable = $this->findTargetTableForForm($form);

        if ($targetTable !== null) {
            $columns = $targetTable->getColumns()->orderBy(['sort_order' => SORT_ASC])->asArray()->all();
            foreach ($columns as $column) {
                $maxLength = (int)($column['length'] ?? 0);
                if ($maxLength <= 0 || !$this->supportsLengthConstraint($column)) {
                    continue;
                }

                $label = trim((string)($column['label'] ?? $column['name'] ?? 'Input'));
                $fieldName = trim((string)($column['name'] ?? ''));
                if ($fieldName === '') {
                    continue;
                }

                $constraint = [
                    'field' => $fieldName,
                    'label' => $label !== '' ? $label : $fieldName,
                    'maxlength' => $maxLength,
                    'source' => 'table_metadata',
                ];

                foreach (array_unique([
                    $fieldName,
                    $this->normalizeInputKey($fieldName),
                    $this->normalizeInputKey($label),
                    $this->stripForeignKeySuffix($fieldName),
                ]) as $key) {
                    if ($key === '') {
                        continue;
                    }
                    $lookup[$key] = $constraint;
                }
            }
        }

        foreach ($schema as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldName = $this->resolveSchemaFieldName($field, (int)$index);
            if ($fieldName === '') {
                continue;
            }

            $schemaMaxLength = (int)($field['max_length'] ?? $field['maxlength'] ?? 0);
            $normalizedFieldName = $this->normalizeInputKey($fieldName);
            $normalizedLabel = $this->normalizeInputKey((string)($field['label'] ?? $field['field_label'] ?? ''));
            $constraint = $lookup[$fieldName]
                ?? ($normalizedFieldName !== '' ? ($lookup[$normalizedFieldName] ?? null) : null)
                ?? ($normalizedLabel !== '' ? ($lookup[$normalizedLabel] ?? null) : null);

            if ($constraint === null && $schemaMaxLength > 0) {
                $constraint = [
                    'field' => $fieldName,
                    'label' => $this->resolveSchemaFieldLabel($field, $fieldName),
                    'maxlength' => $schemaMaxLength,
                    'source' => 'form_schema',
                ];
            } elseif ($constraint !== null && $schemaMaxLength > 0) {
                $constraint['maxlength'] = min((int)$constraint['maxlength'], $schemaMaxLength);
            }

            if ($constraint === null || (int)($constraint['maxlength'] ?? 0) <= 0) {
                continue;
            }

            $constraint['field'] = $fieldName;
            if (trim((string)($constraint['label'] ?? '')) === '') {
                $constraint['label'] = $this->resolveSchemaFieldLabel($field, $fieldName);
            }

            $byField[$fieldName] = $constraint;
            $lookup[$fieldName] = $constraint;
            if ($normalizedFieldName !== '') {
                $lookup[$normalizedFieldName] = $constraint;
            }
            if ($normalizedLabel !== '') {
                $lookup[$normalizedLabel] = $constraint;
            }
        }

        return [
            'byField' => $byField,
            'lookup' => $lookup,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $schema
     * @return array<int, string>
     */
    private function validateSubmissionLengths(Form $form, array $data, array $schema = []): array
    {
        $constraints = $this->buildFormFieldConstraints($form, $schema);
        $errors = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $constraint = $constraints['lookup'][$key]
                ?? $constraints['lookup'][$this->normalizeInputKey($key)]
                ?? null;
            if ($constraint === null) {
                continue;
            }

            $maxLength = (int)($constraint['maxlength'] ?? 0);
            if ($maxLength <= 0) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $candidate) {
                if ($candidate === null || is_bool($candidate) || is_int($candidate) || is_float($candidate)) {
                    continue;
                }

                $stringValue = trim((string)$candidate);
                if ($stringValue === '') {
                    continue;
                }

                if (mb_strlen($stringValue, 'UTF-8') > $maxLength) {
                    $label = trim((string)($constraint['label'] ?? $key)) ?: $key;
                    $errors[] = "Field {$label} maksimal hanya boleh {$maxLength} karakter.";
                    break;
                }
            }
        }

        return array_values(array_unique($errors));
    }

    private function buildFriendlySubmissionErrorMessage(\Throwable $exception, Form $form, array $schema = []): string
    {
        if ($exception instanceof IntegrityException) {
            return $this->buildFriendlyIntegrityErrorMessage($exception, $form);
        }

        $message = $this->sanitizeDatabaseErrorMessage((string)$exception->getMessage());
        if (preg_match('/Data too long for column [`"]?([^`"]+)[`"]?/i', $message, $matches) === 1) {
            return $this->buildFieldLengthErrorMessage((string)$matches[1], $form, $schema);
        }

        if (stripos($message, 'SQLSTATE') !== false || stripos($message, 'syntax error') !== false) {
            return 'Terjadi kesalahan saat menyimpan data. Mohon periksa kembali input Anda.';
        }

        return 'Data gagal disimpan. Mohon periksa kembali input Anda dan coba lagi.';
    }

    private function buildFieldLengthErrorMessage(string $columnName, Form $form, array $schema = []): string
    {
        $constraints = $this->buildFormFieldConstraints($form, $schema);
        $columnName = $this->normalizeDatabaseColumnName($columnName);
        $lookupKeys = [
            $columnName,
            $this->normalizeInputKey($columnName),
            ucwords(str_replace('_', ' ', $columnName)),
            str_replace('_', ' ', $columnName),
        ];

        $constraint = null;
        foreach ($lookupKeys as $lookupKey) {
            if ($lookupKey !== '' && isset($constraints['lookup'][$lookupKey])) {
                $constraint = $constraints['lookup'][$lookupKey];
                break;
            }
        }

        $label = trim((string)($constraint['label'] ?? '')) ?: ucwords(str_replace('_', ' ', $columnName));
        $maxLength = (int)($constraint['maxlength'] ?? 0);

        if ($maxLength > 0) {
            return "Field {$label} maksimal hanya boleh {$maxLength} karakter.";
        }

        return "Field {$label} terlalu panjang. Mohon ringkas isinya dan coba lagi.";
    }

    private function normalizeDatabaseColumnName(string $columnName): string
    {
        $columnName = trim($columnName);
        $columnName = preg_replace('/\s+At Row\s+\d+.*$/i', '', $columnName) ?? $columnName;
        $columnName = preg_replace('/\s+The SQL being executed was:.*$/is', '', $columnName) ?? $columnName;
        $columnName = trim($columnName, " \t\n\r\0\x0B`'\"");

        return trim($columnName);
    }

    private function sanitizeDatabaseErrorMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return $message;
        }

        $message = preg_replace('/\s+At Row\s+\d+.*$/i', '', $message) ?? $message;
        $message = preg_replace('/\s+The SQL being executed was:.*$/is', '', $message) ?? $message;
        $message = preg_replace('/\s+SQLSTATE\[[^\]]+\].*$/i', '', $message) ?? $message;

        return trim($message);
    }

    private function castValueForTableColumn($value, array $column)
    {
        if ($value === null) {
            return null;
        }

        $type = strtoupper((string)($column['type'] ?? ''));
        $isNullable = !empty($column['is_nullable']);

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' && $isNullable) {
                return null;
            }
        }

        if ($type === 'JSON') {
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if (is_string($value)) {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $value;
                }
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (in_array($type, ['INT', 'BIGINT', 'TINYINT', 'BOOLEAN'], true)) {
            if ($value === 'on') {
                return 1;
            }
            if (is_numeric($value)) {
                return (int)$value;
            }
            return $value;
        }

        if (in_array($type, ['DECIMAL', 'FLOAT'], true) && is_numeric($value)) {
            return (float)$value;
        }

        return $value;
    }

    /**
     * Save submission into the selected custom table when mapping exists.
     */
    private function persistSubmissionToCustomTable(Form $form, array $data, ?int $targetTableId = null, array &$debugContext = []): bool
    {
        $tableId = $targetTableId !== null ? (int)$targetTableId : $this->resolveFormTargetTableId($form);
        if ($tableId <= 0) {
            return false;
        }

        $tableCriteria = ['id' => $tableId];
        if (ProjectSchema::supportsProjectContext() && $form->hasAttribute('project_id') && (int)$form->project_id > 0) {
            $tableCriteria['project_id'] = (int)$form->project_id;
        }

        $table = DbTable::findOne($tableCriteria);
        if ($table === null && isset($tableCriteria['project_id'])) {
            $table = DbTable::findOne(['id' => $tableId]);
        }
        if ($table === null) {
            $debugContext['metadata_found'] = false;
            $debugContext['resolved_table_name'] = null;
            $debugContext['insert_result'] = 'metadata_missing';
            throw new \RuntimeException('Target table metadata was not found.');
        }
        if (!(bool)$table->is_created) {
            throw new \RuntimeException("Target table '{$table->name}' has not been created in database.");
        }

        // Resolve physical DB from the form's project to avoid relying on current session context.
        $projectId = $form->hasAttribute('project_id') && (int)$form->project_id > 0 ? (int)$form->project_id : null;
        $targetDb = $this->getPhysicalDb($projectId);
        $tableSchema = $targetDb->schema->getTableSchema($table->name, true);

        if ($tableSchema === null) {
            throw new \RuntimeException("Physical table '{$table->name}' was not found in database.");
        }

        $columns = $table->getColumns()
            ->orderBy(['sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        $normalizedColumnNames = [];
        foreach ($columns as $columnMeta) {
            $metaName = $this->normalizeInputKey((string)($columnMeta['name'] ?? ''));
            if ($metaName !== '') {
                $normalizedColumnNames[$metaName] = true;
            }
        }

        $dataLookup = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $dataLookup[$key] = $value;
            $dataLookup[$this->normalizeInputKey($key)] = $value;
        }

        $repeatFieldNames = $this->resolveDirectSubmitRepeatFieldNames($form);
        $insertData = [];
        foreach ($columns as $column) {
            $columnName = (string)($column['name'] ?? '');
            if ($columnName === '') {
                continue;
            }

            $schemaColumn = $tableSchema->columns[$columnName] ?? null;
            $isPrimaryKey = !empty($tableSchema->primaryKey) && in_array($columnName, (array)$tableSchema->primaryKey, true);
            if ($schemaColumn === null || $isPrimaryKey || SystemFieldService::shouldHideFromForm(array_merge($column, [
                'autoIncrement' => !empty($schemaColumn->autoIncrement),
            ]))) {
                continue;
            }

            $candidateKeys = [$columnName];
            $normalizedColumnName = $this->normalizeInputKey($columnName);
            if ($normalizedColumnName !== '') {
                $candidateKeys[] = $normalizedColumnName;
            }

            if ($normalizedColumnName !== '' && substr($normalizedColumnName, -3) === '_id') {
                $withoutId = substr($normalizedColumnName, 0, -3);
                if ($withoutId !== '' && !isset($normalizedColumnNames[$withoutId])) {
                    $candidateKeys[] = $withoutId;
                }
            }

            foreach (array_values(array_unique($candidateKeys)) as $candidateKey) {
                if (!array_key_exists($candidateKey, $dataLookup)) {
                    continue;
                }

                $rawValue = $dataLookup[$candidateKey];
                if (in_array($columnName, $repeatFieldNames, true) && is_array($rawValue)) {
                    $insertData[$columnName] = $this->normalizeSubmittedArrayValues($rawValue);
                } else {
                    $insertData[$columnName] = $this->castValueForTableColumn($rawValue, $column);
                }
                break;
            }
        }

        $preSystemInsertData = $insertData;
        $insertData = SystemFieldService::applyCreateValues($insertData, $tableSchema->columns);
        $debugContext['system_fields_applied'] = array_values(array_diff(array_keys($insertData), array_keys($preSystemInsertData)));
        $debugContext['metadata_found'] = true;
        $debugContext['resolved_table_name'] = $table->name;
        $debugContext['metadata_source'] = $debugContext['metadata_source'] ?? ($targetTableId !== null ? 'provided_target_table_id' : 'resolved_target_table_id');

        if (empty($insertData)) {
            $debugContext['insert_result'] = 'empty_data';
            return false;
        }

        $submissionRows = $this->buildDirectSubmissionRows($insertData, $repeatFieldNames, $form);
        foreach ($submissionRows as $rowPayload) {
            $targetDb->createCommand()->insert($table->name, $rowPayload)->execute();
        }

        $multipleRowDebug = $this->buildMultipleRowSubmitDebug($repeatFieldNames, $insertData, $submissionRows);
        $debugContext['submit_mode'] = $multipleRowDebug['submit_mode'];
        $debugContext['multiple_row_field'] = $multipleRowDebug['multiple_row_field'];
        $debugContext['selected_values'] = $multipleRowDebug['selected_values'];
        $debugContext['insert_count'] = $multipleRowDebug['insert_count'];
        $debugContext['insert_result'] = count($submissionRows) > 1 ? 'success_multiple_rows' : 'success';
        return true;
    }

    /**
     * @return array<int, string>
     */
    private function resolveDirectSubmitRepeatFieldNames(Form $form): array
    {
        $mappingDebug = [];
        $schema = $this->normalizeFormSchemaFields($this->getFilteredBlocks($form), $form, $mappingDebug);
        $fieldNames = [];
        foreach ($schema as $index => $field) {
            if (!is_array($field) || !$this->shouldExpandSubmissionField($field)) {
                continue;
            }

            $fieldName = $this->resolveSchemaFieldName($field, (int)$index);
            if ($fieldName !== '') {
                $fieldNames[] = $fieldName;
            }
        }

        return array_values(array_unique($fieldNames));
    }

    private function shouldExpandSubmissionField(array $field): bool
    {
        foreach (array_merge([
            $field['multiple_row_field'] ?? null,
            $field['multipleRowField'] ?? null,
            $field['is_multiple_row_field'] ?? null,
            $field['isMultipleRowField'] ?? null,
            $field['save_as_multiple_rows'] ?? null,
            $field['saveAsMultipleRows'] ?? null,
            $field['repeat_rows'] ?? null,
            $field['repeatRows'] ?? null,
            $field['expand_rows'] ?? null,
            $field['expandRows'] ?? null,
            $field['repeat_on_multiple'] ?? null,
            $field['repeatOnMultiple'] ?? null,
            $field['multi_row'] ?? null,
            $field['multiRow'] ?? null,
            $field['submit_mode'] ?? null,
            $field['submitMode'] ?? null,
            $field['behavior'] ?? null,
            $field['field_behavior'] ?? null,
        ], $this->extractSubmitBehaviorCandidates($field)) as $candidate) {
            if (is_bool($candidate) && $candidate) {
                return true;
            }
            if (is_int($candidate) && $candidate === 1) {
                return true;
            }
            if (is_string($candidate)) {
                $normalized = strtolower(trim($candidate));
                if (in_array($normalized, ['1', 'true', 'yes', 'on', 'multiple_row_insert', 'multiple-row-insert'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<int, mixed>
     */
    private function extractSubmitBehaviorCandidates(array $field): array
    {
        $candidates = [];
        foreach (['field_config', 'fieldConfig', 'field_settings', 'fieldSettings', 'settings', 'config', 'behavior_config', 'behaviorConfig', 'dynamic_behavior', 'dynamicBehavior', 'detected_behavior', 'detectedBehavior'] as $key) {
            $config = $field[$key] ?? null;
            if (is_string($config) && trim($config) !== '') {
                $decoded = json_decode($config, true);
                $config = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($config)) {
                continue;
            }

            foreach ([
                'multiple_row_field', 'multipleRowField', 'is_multiple_row_field', 'isMultipleRowField',
                'save_as_multiple_rows', 'saveAsMultipleRows', 'repeat_rows', 'repeatRows',
                'expand_rows', 'expandRows', 'repeat_on_multiple', 'repeatOnMultiple',
                'multi_row', 'multiRow', 'submit_mode', 'submitMode', 'behavior', 'field_behavior',
            ] as $candidateKey) {
                if (array_key_exists($candidateKey, $config)) {
                    $candidates[] = $config[$candidateKey];
                }
            }
        }

        return $candidates;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array<int, string> $repeatFieldNames
     * @return array<int, array<string, mixed>>
     */
    private function buildDirectSubmissionRows(array $insertData, array $repeatFieldNames, ?Form $form = null): array
    {
        $repeatFieldName = '';
        $repeatValues = [];
        foreach ($repeatFieldNames as $fieldName) {
            if (!array_key_exists($fieldName, $insertData)) {
                continue;
            }

            $value = $insertData[$fieldName];
            if (is_array($value)) {
                $values = $this->normalizeSubmittedArrayValues($value);
            } elseif (is_string($value) && str_contains($value, ',')) {
                $values = $this->normalizeSubmittedArrayValues(explode(',', $value));
            } else {
                $values = $this->normalizeSubmittedArrayValues([$value]);
            }

            if (!empty($values)) {
                $repeatFieldName = $fieldName;
                $repeatValues = $values;
                break;
            }
        }

        if ($repeatFieldName === '' && $form !== null) {
            $mappingDebug = [];
            $schema = $this->normalizeFormSchemaFields($this->getFilteredBlocks($form), $form, $mappingDebug);
            foreach ($schema as $index => $field) {
                if (!is_array($field) || !$this->shouldExpandSubmissionField($field)) {
                    continue;
                }

                $fieldName = $this->resolveSchemaFieldName($field, (int)$index);
                if ($fieldName === '' || !array_key_exists($fieldName, $insertData)) {
                    continue;
                }

                $candidateValue = $insertData[$fieldName];
                if (is_array($candidateValue)) {
                    $repeatValues = $this->normalizeSubmittedArrayValues($candidateValue);
                } elseif (is_string($candidateValue) && str_contains($candidateValue, ',')) {
                    $repeatValues = $this->normalizeSubmittedArrayValues(explode(',', $candidateValue));
                } elseif ($candidateValue !== null && $candidateValue !== '') {
                    $repeatValues = $this->normalizeSubmittedArrayValues([$candidateValue]);
                }

                if (!empty($repeatValues)) {
                    $repeatFieldName = $fieldName;
                    break;
                }
            }
        }

        if ($repeatFieldName === '' || empty($repeatValues)) {
            $row = [];
            foreach ($insertData as $columnName => $value) {
                $row[$columnName] = $value;
            }
            return [$row];
        }

        $rows = [];
        foreach ($repeatValues as $repeatValue) {
            $row = [];
            foreach ($insertData as $columnName => $value) {
                if ($columnName === $repeatFieldName) {
                    $row[$columnName] = $repeatValue;
                } else {
                    $row[$columnName] = $value;
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeSubmittedArrayValues(array $values): array
    {
        return array_values(array_filter(array_map(static function ($value): string {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            if (is_scalar($value)) {
                return trim((string)$value);
            }
            return '';
        }, $values), static function (string $value): bool {
            return $value !== '';
        }));
    }

    /**
     * @param array<int, string> $repeatFieldNames
     * @param array<string, mixed> $insertData
     * @param array<int, array<string, mixed>> $submissionRows
     * @return array<string, mixed>
     */
    private function buildMultipleRowSubmitDebug(array $repeatFieldNames, array $insertData, array $submissionRows): array
    {
        $multipleRowField = '';
        $selectedValues = [];
        foreach ($repeatFieldNames as $fieldName) {
            if (!array_key_exists($fieldName, $insertData)) {
                continue;
            }

            $multipleRowField = $fieldName;
            $value = $insertData[$fieldName];
            if (is_array($value)) {
                $selectedValues = $this->normalizeSubmittedArrayValues($value);
            } elseif (is_string($value) && str_contains($value, ',')) {
                $selectedValues = $this->normalizeSubmittedArrayValues(explode(',', $value));
            } elseif ($value !== null && $value !== '') {
                $selectedValues = $this->normalizeSubmittedArrayValues([$value]);
            }
            break;
        }

        return [
            'submit_mode' => $multipleRowField !== '' ? 'multiple_row_insert' : 'single_row',
            'multiple_row_field' => $multipleRowField,
            'selected_values' => $selectedValues,
            'insert_count' => count($submissionRows),
        ];
    }

    /**
     * Extract blocks from mixed schema shapes (pages, blocks, or legacy array).
     */
    private function extractBlocksFromSchema($schemaData): array
    {
        if (!is_array($schemaData)) {
            return [];
        }

        if (isset($schemaData['pages']) && is_array($schemaData['pages'])) {
            $allBlocks = [];
            foreach ($schemaData['pages'] as $page) {
                if (is_array($page) && isset($page['blocks']) && is_array($page['blocks'])) {
                    $allBlocks = array_merge($allBlocks, $page['blocks']);
                }
            }
            return $allBlocks;
        }

        if (isset($schemaData['blocks']) && is_array($schemaData['blocks'])) {
            return $schemaData['blocks'];
        }

        return array_values($schemaData) === $schemaData ? $schemaData : [];
    }

    /**
     * Normalize custom design to a safe subset for storage/rendering.
     *
     * Custom JS is intentionally disabled because arbitrary script execution
     * from database-backed form design is not safe for shared/public forms.
     *
     * @param array<string, mixed> $customDesign
     * @return array<string, string>
     */
    private function sanitizeCustomDesign(array $customDesign): array
    {
        return [
            'css' => $this->sanitizeCustomCss((string)($customDesign['css'] ?? '')),
            'htmlBefore' => $this->sanitizeCustomHtml((string)($customDesign['htmlBefore'] ?? '')),
            'htmlAfter' => $this->sanitizeCustomHtml((string)($customDesign['htmlAfter'] ?? '')),
            'js' => '',
        ];
    }

    private function sanitizeCustomHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        return HtmlPurifier::process($html, [
            'HTML.SafeIframe' => false,
            'URI.DisableExternalResources' => false,
            'URI.DisableResources' => false,
            'Attr.EnableID' => false,
            'HTML.Allowed' => implode(',', [
                'div', 'span', 'p', 'br', 'hr',
                'strong', 'b', 'em', 'i', 'u',
                'ul', 'ol', 'li',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'blockquote', 'code', 'pre',
                'a[href|title|target|rel]',
                'img[src|alt|title|width|height]',
            ]),
            'Attr.AllowedFrameTargets' => ['_blank'],
            'AutoFormat.RemoveEmpty' => true,
        ]);
    }

    private function sanitizeCustomCss(string $css): string
    {
        $css = trim($css);
        if ($css === '') {
            return '';
        }

        $css = preg_replace('/<\\/?style\\b[^>]*>/i', '', $css) ?? $css;
        $css = preg_replace('/@import\\s+/i', '', $css) ?? $css;
        $css = preg_replace('/expression\\s*\\(/i', '', $css) ?? $css;
        $css = preg_replace('/javascript\\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/vbscript\\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/behavior\\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/-moz-binding\\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/url\\s*\\(\\s*[\'"]?\\s*(javascript|vbscript)\\s*:/i', 'url(', $css) ?? $css;

        return trim($css);
    }

    /**
     * Normalize builder payload into canonical schema_js shape.
     */
    private function normalizeBuilderSchema(?string $pagesData, ?string $rawSchema): string
    {
        $decodedPagesData = null;
        if (is_string($pagesData) && trim($pagesData) !== '') {
            $decoded = json_decode($pagesData, true);
            if (is_array($decoded)) {
                $decodedPagesData = $decoded;
            }
        }

        $decodedRawSchema = [];
        if (is_string($rawSchema) && trim($rawSchema) !== '') {
            $decoded = json_decode($rawSchema, true);
            if (is_array($decoded)) {
                $decodedRawSchema = $decoded;
            }
        }

        $pages = [];
        if (is_array($decodedPagesData) && isset($decodedPagesData['pages']) && is_array($decodedPagesData['pages'])) {
            $pages = $decodedPagesData['pages'];
        }

        $customDesign = [];
        if (is_array($decodedPagesData) && isset($decodedPagesData['customDesign']) && is_array($decodedPagesData['customDesign'])) {
            $customDesign = $this->sanitizeCustomDesign($decodedPagesData['customDesign']);
        } elseif (isset($decodedRawSchema['customDesign']) && is_array($decodedRawSchema['customDesign'])) {
            $customDesign = $this->sanitizeCustomDesign($decodedRawSchema['customDesign']);
        }

        $rawBlocks = $this->extractBlocksFromSchema($decodedRawSchema);

        if (empty($pages)) {
            $pages = [[
                'id' => 'page_1',
                'name' => 'Page 1',
                'blocks' => $rawBlocks,
            ]];
        } else {
            $hasAnyPageBlocks = false;
            foreach ($pages as $index => $page) {
                if (!is_array($page)) {
                    $page = [];
                }

                $pageBlocks = isset($page['blocks']) && is_array($page['blocks']) ? array_values($page['blocks']) : [];
                if (!empty($pageBlocks)) {
                    $hasAnyPageBlocks = true;
                }

                $pages[$index] = [
                    'id' => !empty($page['id']) ? (string)$page['id'] : 'page_' . ($index + 1),
                    'name' => !empty($page['name']) ? (string)$page['name'] : 'Page ' . ($index + 1),
                    'blocks' => $pageBlocks,
                ];
            }

            if (!$hasAnyPageBlocks && !empty($rawBlocks)) {
                $pages[0]['blocks'] = $rawBlocks;
            }
        }

        $allBlocks = [];
        foreach ($pages as $page) {
            if (isset($page['blocks']) && is_array($page['blocks'])) {
                $allBlocks = array_merge($allBlocks, $page['blocks']);
            }
        }

        if (empty($allBlocks) && !empty($rawBlocks)) {
            $allBlocks = $rawBlocks;
        }

        return json_encode([
            'pages' => $pages,
            'customDesign' => $customDesign,
            'blocks' => $allBlocks,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['render', 'submit', 'public-render', 'success', 'fk-options', 'fk-quick-add'],
                        'allow' => true,
                        'roles' => ['?', '@'], // Allow both guests and authenticated users
                    ],
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            return $this->canAccessWorkspaceFormController();
                        },
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user->isGuest || $this->shouldBypassProjectContext($action->id)) {
            return true;
        }

        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = $this->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola form.');
            $this->redirect(['project/index']);
            return false;
        }

        return true;
    }

    /**
     * List all forms
     */
    public function actionIndex()
    {
        $activeProjectId = $this->getActiveProjectId();
        $schemaColumn = Form::getSchemaStorageColumn();
        $isCommanderSuperAdmin = (new CommanderAuthContext())->isSuperAdmin();

        $submissionCountSubQuery = FormSubmission::find()
            ->select(['form_id', 'submission_count' => 'COUNT(*)'])
            ->groupBy('form_id');

        $query = Form::find()
            ->alias('f')
            ->select([
                'f.id',
                'f.user_id',
                'f.name',
                'schema_js' => new \yii\db\Expression('f.' . $schemaColumn),
                'f.created_at',
                'submission_count' => new \yii\db\Expression('COALESCE(fs_count.submission_count, 0)'),
            ])
            ->leftJoin(['fs_count' => $submissionCountSubQuery], 'fs_count.form_id = f.id')
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC]);
        if (!$isCommanderSuperAdmin) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $query->where(['f.user_id' => $effectiveUserId]);
            }
        }
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $query->andWhere(['f.project_id' => $activeProjectId]);
        }

        // Search functionality
        $search = Yii::$app->request->get('q');
        if ($search) {
            $query->andWhere(['like', 'f.name', $search]);
        }

        $forms = $query->all();

        return $this->render('index', [
            'forms' => $forms,
            'search' => $search,
        ]);
    }

    /**
     * Create new form
     */
    public function actionCreate()
    {
        $model = new Form();
        $effectiveUserId = $this->getEffectiveUserId();
        if ($effectiveUserId !== null) {
            $model->user_id = $effectiveUserId;
        }
        $this->assignActiveProject($model);
        $model->schema_js = '[]';
        if ($model->hasAttribute('insert_to_table')) {
            $model->setAttribute('insert_to_table', 0);
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($effectiveUserId !== null) {
                $model->user_id = $effectiveUserId;
            }
            $this->assignActiveProject($model);
            $model->name = trim((string) $model->name);
            if ($model->name === '') {
                $model->name = 'Untitled Page ' . date('Y-m-d H:i:s');
            }

            // Handle multi-page form and custom design data
            $pagesData = Yii::$app->request->post('form_pages');
            $postedFormData = Yii::$app->request->post($model->formName(), []);
            $rawSchema = isset($postedFormData['schema_js']) ? (string)$postedFormData['schema_js'] : (string)$model->schema_js;
            
            // DEBUG: Log what we received
            if (YII_DEBUG && $pagesData) {
                Yii::info('=== FORM SUBMIT DEBUG ===', 'app');
                Yii::info('Raw form_pages: ' . $pagesData, 'app');
                $decoded = json_decode($pagesData, true);
                if ($decoded) {
                    Yii::info('Custom Design received: ' . json_encode($decoded['customDesign'] ?? 'NOT SET'), 'app');
                }
            }
            
            $model->schema_js = $this->normalizeBuilderSchema($pagesData, $rawSchema);
            
            if ($model->save()) {
                $shouldPublish = (bool) Yii::$app->request->post('publish_now', false);

                if ($shouldPublish) {
                    $effectiveUserId = $this->getEffectiveUserId();
                    $publishedForm = PublishedForm::find()
                        ->where(['form_id' => $model->id, 'user_id' => $effectiveUserId])
                        ->one();

                    if ($publishedForm === null) {
                        $publishedForm = new PublishedForm();
                        if ($effectiveUserId !== null) {
                            $publishedForm->user_id = $effectiveUserId;
                        }
                        $publishedForm->form_id = $model->id;
                    }

                    $publishedForm->name = $model->name;

                    if ($publishedForm->save()) {
                        Yii::$app->session->setFlash('success', 'Form created and published successfully!');
                        return $this->redirect(['published-form/index']);
                    }

                    Yii::error('Failed to publish form after create: ' . print_r($publishedForm->errors, true), 'app');
                    Yii::$app->session->setFlash('warning', 'Form created, but failed to publish. Please try publish again from the form page.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }

                Yii::$app->session->setFlash('success', 'Form created successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $firstError = $model->getFirstErrors();
            $errorMessage = !empty($firstError) ? reset($firstError) : 'Failed to create form. Please check input data.';
            Yii::$app->session->setFlash('error', $errorMessage);
        }

        return $this->render('create', [
            'model' => $model,
            'filteredSchemaJs' => $this->getFilteredSchemaJs($model),
        ]);
    }

    /**
     * Update existing form
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $model->user_id = $effectiveUserId;
            }
            $this->assignActiveProject($model);
            $model->name = trim((string) $model->name);
            if ($model->name === '') {
                $model->name = 'Untitled Page ' . $model->id;
            }
            
            // Handle multi-page form and custom design data
            $pagesData = Yii::$app->request->post('form_pages');
            $postedFormData = Yii::$app->request->post($model->formName(), []);
            $rawSchema = isset($postedFormData['schema_js']) ? (string)$postedFormData['schema_js'] : (string)$model->schema_js;
            if ($pagesData) {
                $model->schema_js = $this->normalizeBuilderSchema($pagesData, $rawSchema);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Form updated successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $firstError = $model->getFirstErrors();
            $errorMessage = !empty($firstError) ? reset($firstError) : 'Failed to update form. Please check input data.';
            Yii::$app->session->setFlash('error', $errorMessage);
        }

        // Use the same builder UI as create page so edit experience stays identical.
        return $this->render('create', [
            'model' => $model,
            'filteredSchemaJs' => $this->getFilteredSchemaJs($model),
        ]);
    }

    /**
     * View form details
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $schema = $this->getFilteredBlocks($model);
        $totalSubmissions = (int) FormSubmission::find()
            ->where(['form_id' => $id])
            ->count();

        $recentSubmissions = FormSubmission::find()
            ->select(['id', 'form_id', 'created_at'])
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();

        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'form',
            'hero_label' => 'Dynamic Form',
            'page_title' => (string)$model->name,
            'page_description' => (string)($model->description ?? ''),
            'layout' => (string)($model->form_type ?? 'builder'),
            'form_count' => count($schema),
            'status' => 'Active',
            'workspace_name' => $activeProject instanceof Project ? (string)$activeProject->name : 'Workspace',
        ];

        return $this->render('view', [
            'model' => $model,
            'schema' => $schema,
            'totalSubmissions' => $totalSubmissions,
            'recentSubmissions' => $recentSubmissions,
        ]);
    }

    /**
     * Delete form
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Form deleted successfully!');

        return $this->redirect(['index']);
    }

    /**
     * Render form for public access (no auth required)
     */
    public function actionPublicRender($id)
    {
        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $schema = $this->getFilteredBlocks($model);
        $schemaMappingDebug = [];
        $schema = $this->normalizeFormSchemaFields($schema, $model, $schemaMappingDebug);
        $fkConfig = [];
        $fieldConstraints = $this->buildFormFieldConstraints($model, $schema)['byField'];

        try {
            $fkConfig = $this->mapForeignKeyConfigToSchema($schema, $this->getForeignKeyConfigForForm($model));
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve foreign key config for public-render: ' . $e->getMessage(), 'app');
        }

        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'form',
            'hero_label' => 'Dynamic Form',
            'page_title' => (string)$model->name,
            'page_description' => '',
            'layout' => (string)($model->form_type ?? 'builder'),
            'form_count' => count($schema),
            'status' => 'Active',
            'workspace_name' => $activeProject instanceof Project ? (string)$activeProject->name : 'Workspace',
        ];

        return $this->render('public-render', [
            'model' => $model,
            'schema' => $schema,
            'fkConfig' => $fkConfig,
            'fieldConstraints' => $fieldConstraints,
        ]);
    }

    /**
     * Save custom design via AJAX
     */
    public function actionSaveDesign($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $rawBody = Yii::$app->request->getRawBody();
            $data = json_decode($rawBody, true);

            if (!$data) {
                return ['success' => false, 'error' => 'Invalid JSON data'];
            }

            if ($id > 0) {
                // Update existing form
                $model = $this->findModel($id);
            } else {
                // For new forms, we can't save yet - user needs to create the form first
                return ['success' => false, 'error' => 'Please save the form first before saving design'];
            }

            // Get all blocks from pages
            $allBlocks = [];
            if (isset($data['pages'])) {
                foreach ($data['pages'] as $page) {
                    if (isset($page['blocks'])) {
                        $allBlocks = array_merge($allBlocks, $page['blocks']);
                    }
                }
            }

            // Save to schema_js with custom design
            $model->schema_js = json_encode([
                'pages' => $data['pages'] ?? [],
                'customDesign' => $this->sanitizeCustomDesign((array)($data['customDesign'] ?? [])),
                'blocks' => $allBlocks
            ], JSON_UNESCAPED_UNICODE);

            if ($model->save()) {
                return ['success' => true, 'message' => 'Design saved successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to save design', 'errors' => $model->errors];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Render form for public/submission
     */
    public function actionRender($id)
    {
        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $schema = $this->getFilteredBlocks($model);
        $schemaMappingDebug = [];
        $schema = $this->normalizeFormSchemaFields($schema, $model, $schemaMappingDebug);
        $fkConfig = [];
        $fieldConstraints = $this->buildFormFieldConstraints($model, $schema)['byField'];

        try {
            $fkConfig = $this->mapForeignKeyConfigToSchema($schema, $this->getForeignKeyConfigForForm($model));
            Yii::info('FK Config for Form ' . $id . ': ' . json_encode(array_keys($fkConfig)), 'app');
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve foreign key config for render: ' . $e->getMessage(), 'app');
        }

        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'form',
            'hero_label' => 'Dynamic Form',
            'page_title' => (string)$model->name,
            'page_description' => '',
            'layout' => (string)($model->form_type ?? 'builder'),
            'form_count' => count($schema),
            'status' => 'Active',
            'workspace_name' => $activeProject instanceof Project ? (string)$activeProject->name : 'Workspace',
        ];

        return $this->render('render', [
            'model' => $model,
            'schema' => $schema,
            'fkConfig' => $fkConfig,
            'fieldConstraints' => $fieldConstraints,
        ]);
    }

    /**
     * Submit form data
     */
    public function actionSubmit($id)
    {
        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $schema = $this->getFilteredBlocks($model);
        $schemaMappingDebug = [];
        $schema = $this->normalizeFormSchemaFields($schema, $model, $schemaMappingDebug);
        $returnUrl = $this->resolveSafeReturnUrl();

        if (Yii::$app->request->isPost) {
            $postPayload = Yii::$app->request->post();
            $data = [];
            $fieldMappingDebug = [];
            foreach ($schema as $index => $field) {
                if (!is_array($field)) continue;
                if (FormSystemFieldHelper::isSystemFieldData($field)) continue;
                $name = $this->resolveSchemaFieldName($field, (int)$index);
                if ($name) {
                    $candidateKeys = array_values(array_unique(array_filter([
                        $name,
                        (string)($field['original_name'] ?? ''),
                        (string)($field['field_name'] ?? ''),
                        (string)($field['field_key'] ?? ''),
                        (string)($field['column_name'] ?? ''),
                        (string)($field['label'] ?? ''),
                        (string)($field['field_label'] ?? ''),
                        (string)($field['source_column_name'] ?? ''),
                        (string)($field['source_column_label'] ?? ''),
                    ])));
                    $resolvedValue = null;
                    foreach ($candidateKeys as $candidateKey) {
                        if (array_key_exists($candidateKey, $postPayload)) {
                            $resolvedValue = $postPayload[$candidateKey];
                            break;
                        }

                        $normalizedCandidate = $this->normalizeInputKey((string)$candidateKey);
                        foreach ($postPayload as $postedKey => $postedValue) {
                            if (!is_string($postedKey)) {
                                continue;
                            }
                            if ($this->normalizeInputKey($postedKey) === $normalizedCandidate) {
                                $resolvedValue = $postedValue;
                                break 2;
                            }
                        }
                    }

                    $data[$name] = $resolvedValue !== null ? $resolvedValue : '';
                    $fieldMappingDebug[] = [
                        'raw_field' => (string)($field['original_name'] ?? $field['name'] ?? ''),
                        'label' => (string)($field['label'] ?? $field['field_label'] ?? ''),
                        'resolved_column' => $name,
                        'candidate_keys' => $candidateKeys,
                        'resolved_value' => $resolvedValue,
                    ];
                }
            }

            $data = $this->mergeAdditionalPostedInputs($data);
            $normalizedPayloadDebug = [
                'target_table' => ($targetTableForDebug = $this->findTargetTableForForm($model)) !== null ? (string)$targetTableForDebug->name : '',
                'schema_columns' => array_map(static function ($field): string {
                    return (string)($field['name'] ?? '');
                }, $schema),
                'raw_post_keys' => array_keys($postPayload),
                'normalized_payload' => $data,
                'field_mapping' => $fieldMappingDebug,
            ];
            Yii::info($normalizedPayloadDebug, 'submit_debug');
            $lengthErrors = $this->validateSubmissionLengths($model, $data, $schema);
            if (!empty($lengthErrors)) {
                Yii::$app->session->setFlash('error', implode(' ', $lengthErrors));
                if ($returnUrl !== null) {
                    return $this->redirect($returnUrl);
                }
                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                return $this->redirect(['render', 'id' => $id]);
            }

            if (!$this->hasAtLeastOneFilledField($schema, $data)) {
                Yii::$app->session->setFlash('error', 'Form belum diisi. Isi minimal satu field sebelum submit.');
                if ($returnUrl !== null) {
                    return $this->redirect($returnUrl);
                }
                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                return $this->redirect(['render', 'id' => $id]);
            }

            // Auto inject Firebase user data from hidden fields
            if (Yii::$app->request->post('user_email')) {
                $data['_firebase_email'] = Yii::$app->request->post('user_email');
            }
            if (Yii::$app->request->post('user_name')) {
                $data['_firebase_name'] = Yii::$app->request->post('user_name');
            }
            if (Yii::$app->request->post('firebase_uid')) {
                $data['_firebase_uid'] = Yii::$app->request->post('firebase_uid');
            }

            // Auto inject Yii logged in user data if available
            $workspaceUser = $this->getWorkspaceAuthenticatedUser($this->getActiveProjectId());
            if ($workspaceUser !== null) {
                $data['_user_id'] = (int)$workspaceUser->id;
                $data['_user_name'] = (string)$workspaceUser->username;
                if ($workspaceUser->hasAttribute('email') && !empty($workspaceUser->email)) {
                    $data['_user_email'] = (string)$workspaceUser->email;
                }
            } elseif (!Yii::$app->user->isGuest) {
                $identity = Yii::$app->user->identity;
                $data['_user_id'] = $identity->getId();
                $data['_user_name'] = $identity->username;
                
                // User model might not have email field (it doesn't in current schema)
                if (property_exists($identity, 'email') && isset($identity->email)) {
                    $data['_user_email'] = $identity->email;
                }
            }

            $targetTableInfo = $this->resolveFormTargetTableInfo($model);
            $targetTableId = (int)($targetTableInfo['id'] ?? 0);
            $targetTableSource = (string)($targetTableInfo['source'] ?? '');
            $insertDirectlyToTable = $this->shouldInsertDirectlyToTable($model);
            $submitDebug = [
                'host' => Yii::$app->request->hostInfo,
                'project_id' => $this->getActiveProjectId(),
                'active_db' => (string)($dbContext['activeDatabase'] ?? $db->dsn),
                'render_context' => (string)Yii::$app->request->post('render_context', Yii::$app->request->get('render_context', '')),
                'page_id' => (int)Yii::$app->request->post('page_id', Yii::$app->request->get('page_id', 0)),
                'menu_id' => (int)Yii::$app->request->post('menu_id', Yii::$app->request->get('menu_id', 0)),
                'role' => ($workspaceUser = $this->getWorkspaceAuthenticatedUser($this->getActiveProjectId())) !== null ? strtolower(trim((string)$workspaceUser->role)) : '',
                'form_id' => (int)$model->id,
                'target_table_id' => $targetTableId,
                'resolved_table_name' => null,
                'metadata_found' => false,
                'metadata_source' => $targetTableSource,
                'submitted_fields' => array_keys($data),
                'system_fields_applied' => [],
                'insert_result' => 'pending',
                'error' => null,
            ];
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $storedToTable = false;
                if ($insertDirectlyToTable) {
                    if ($targetTableId <= 0) {
                        $submitDebug['insert_result'] = 'missing_target_table';
                        FormFlowDebugLogger::logSubmit($submitDebug);
                        throw new \RuntimeException('Form ini belum terhubung ke tabel database tujuan.');
                    }

                    try {
                        $storedToTable = $this->persistSubmissionToCustomTable($model, $data, $targetTableId, $submitDebug);
                    } catch (\Throwable $persistError) {
                        Yii::error('Persist to custom table failed: ' . $persistError->getMessage(), 'app');
                        $submitDebug['insert_result'] = 'error';
                        $submitDebug['error'] = $persistError->getMessage();
                        FormFlowDebugLogger::logSubmit($submitDebug);
                        throw new \RuntimeException('Gagal menyimpan ke tabel target: ' . $persistError->getMessage());
                    }

                    if (!$storedToTable) {
                        $submitDebug['insert_result'] = 'no_matching_fields';
                        FormFlowDebugLogger::logSubmit($submitDebug);
                        throw new \RuntimeException('Tidak ada field yang cocok untuk disimpan ke tabel target.');
                    }
                }

                if (!$insertDirectlyToTable) {
                    $submission = new FormSubmission();
                    $submission->setAttribute('form_id', (int)$id);
                    $submission->setAttribute('user_id', $this->getEffectiveUserId());
                    
                    if ($submission->hasAttribute('firebase_uid')) {
                        $submission->setAttribute('firebase_uid', (string)Yii::$app->request->post('firebase_uid'));
                    }
                    if ($submission->hasAttribute('firebase_email')) {
                        $submission->setAttribute('firebase_email', (string)Yii::$app->request->post('user_email'));
                    }
                    if ($submission->hasAttribute('firebase_name')) {
                        $submission->setAttribute('firebase_name', (string)Yii::$app->request->post('user_name'));
                    }
                    
                    $submission->setAttribute('data_json', json_encode($data, JSON_UNESCAPED_UNICODE));

                    if (!$submission->save()) {
                        $errors = $submission->getFirstErrors();
                        $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Failed to submit form. Please try again.';
                        $submitDebug['insert_result'] = 'error';
                        $submitDebug['error'] = $errorMessage;
                        FormFlowDebugLogger::logSubmit($submitDebug);
                        throw new \RuntimeException($errorMessage);
                    }
                }

                $transaction->commit();
                $submitDebug['insert_result'] = 'success';
                FormFlowDebugLogger::logSubmit($submitDebug);

                if ($returnUrl !== null) {
                    Yii::$app->session->setFlash('success', 'Form "' . $model->name . '" berhasil dikirim.');
                    return $this->redirect($returnUrl);
                }

                return $this->redirect(['success', 'id' => $id]);
            } catch (IntegrityException $e) {
                $transaction->rollBack();
                Yii::warning('IntegrityException on form submit: ' . $e->getMessage(), 'app');
                Yii::$app->session->setFlash('error', $this->buildFriendlyIntegrityErrorMessage($e, $model));
                $submitDebug['insert_result'] = 'integrity_error';
                $submitDebug['error'] = $e->getMessage();
                FormFlowDebugLogger::logSubmit($submitDebug);

                if ($returnUrl !== null) {
                    return $this->redirect($returnUrl);
                }
                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                return $this->redirect(['render', 'id' => $id]);
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $this->buildFriendlySubmissionErrorMessage($e, $model, $schema));
                $submitDebug['insert_result'] = 'error';
                $submitDebug['error'] = $e->getMessage();
                FormFlowDebugLogger::logSubmit($submitDebug);

                if ($returnUrl !== null) {
                    return $this->redirect($returnUrl);
                }
                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                return $this->redirect(['render', 'id' => $id]);
            }
        }

        if ($returnUrl !== null) {
            return $this->redirect($returnUrl);
        }

        if (Yii::$app->user->isGuest) {
            return $this->redirect(['public-render', 'id' => $id]);
        }
        return $this->redirect(['render', 'id' => $id]);
    }

    /**
     * Success page after form submission (for public users)
     */
    public function actionSuccess($id)
    {
        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);

        return $this->render('success', [
            'model' => $model,
        ]);
    }

    /**
     * View submissions for a form
     */
    public function actionSubmissions($id)
    {
        $model = $this->findModel($id);

        $query = FormSubmission::find()
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC]);

        $countQuery = clone $query;
        $pages = new \yii\data\Pagination([
            'totalCount' => $countQuery->count(),
            'defaultPageSize' => 10,
        ]);

        $submissions = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        return $this->render('submissions', [
            'model' => $model,
            'submissions' => $submissions,
            'pages' => $pages,
        ]);
    }

    /**
     * Duplicate a form
     */
    public function actionDuplicate($id)
    {
        $model = $this->findModel($id);

        $newForm = new Form();
        $effectiveUserId = $this->getEffectiveUserId();
        if ($effectiveUserId !== null) {
            $newForm->user_id = $effectiveUserId;
        }
        $this->assignActiveProject($newForm);
        $newForm->name = $model->name . ' (Copy)';
        $newForm->schema_js = $model->schema_js;
        $newForm->table_id = $model->table_id;
        $newForm->storage_type = $model->storage_type;
        if ($newForm->hasAttribute('db_table_id') && $model->hasAttribute('db_table_id')) {
            $newForm->setAttribute('db_table_id', $model->getAttribute('db_table_id'));
        }
        if ($newForm->hasAttribute('insert_to_table') && $model->hasAttribute('insert_to_table')) {
            $newForm->setAttribute('insert_to_table', (int)$model->getAttribute('insert_to_table'));
        }

        if ($newForm->save()) {
            Yii::$app->session->setFlash('success', 'Form duplicated successfully!');
            return $this->redirect(['view', 'id' => $newForm->id]);
        }

        Yii::$app->session->setFlash('error', 'Failed to duplicate form.');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Export submissions to CSV
     */
    public function actionExport($id)
    {
        $model = $this->findModel($id);
        $schema = $this->getFilteredBlocks($model);

        $submissions = FormSubmission::find()
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $model->name . '_submissions_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        $headers = ['ID', 'Submitted At'];
        foreach ($schema as $field) {
            $headers[] = $field['label'] . ' (' . $field['name'] . ')';
        }
        fputcsv($output, $headers);

        // Data rows
        foreach ($submissions as $submission) {
            $data = $submission->getData();
            $row = [$submission->id, $submission->created_at];
            foreach ($schema as $field) {
                $row[] = $data[$field['name']] ?? '';
            }
            fputcsv($output, $row);
        }

        fclose($output);
        Yii::$app->end();
    }

    /**
     * Get table columns for auto-generating form fields via AJAX
     */
    public function actionGetTableColumns()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Get table_id from JSON body (not form post data)
        $body = Yii::$app->request->getRawBody();
        $data = json_decode($body, true);
        $tableId = $data['table_id'] ?? Yii::$app->request->post('table_id');

        Yii::info('GetTableColumns: tableId=' . var_export($tableId, true) . ', body=' . $body, 'app');

        if (!$tableId) {
            return [
                'success' => false,
                'error' => 'No table selected',
                'message' => 'Please select a table from the dropdown',
                'debug' => ['received_table_id' => $tableId, 'body' => $body]
            ];
        }

        // Verify table belongs to current user
        $effectiveUserId = $this->getEffectiveUserId();
        $tableCriteria = [
            'id' => (int)$tableId,
        ];
        if ($effectiveUserId !== null) {
            $tableCriteria['user_id'] = $effectiveUserId;
        }
        if (ProjectSchema::supportsProjectContext()) {
            $tableCriteria['project_id'] = $this->getActiveProjectId();
        }
        $table = \app\models\DbTable::findOne($tableCriteria);
        if (!$table) {
            return [
                'success' => false,
                'error' => 'Table not found or access denied',
                'message' => 'The selected table does not exist or you do not have permission to access it'
            ];
        }

        // Get all columns ordered by sort_order
        $columns = $table->getColumns()
            ->orderBy(['sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        $columns = array_values(array_filter($columns, function ($column) {
            return !SystemFieldService::shouldHideFromForm($column);
        }));

        if (empty($columns)) {
            return [
                'success' => false,
                'error' => 'No input columns found',
                'message' => 'All columns are system-generated (e.g., auto increment) or no columns are defined.',
                'table_name' => $table->name,
                'table_id' => $table->id
            ];
        }

        return [
            'success' => true,
            'columns' => $columns,
            'table_id' => $table->id,
            'table_name' => $table->name,
            'column_count' => count($columns)
        ];
    }

    public function actionFkOptions($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $field = (string)Yii::$app->request->post('field', Yii::$app->request->get('field', ''));
        if ($field === '') {
            return ['success' => false, 'message' => 'Field is required.'];
        }

        $fkConfig = $this->getResolvedForeignKeyConfigForForm($model);
        if (!isset($fkConfig[$field])) {
            $resolvedFieldKey = $this->resolveForeignKeyKey($fkConfig, $field, $field);
            if ($resolvedFieldKey === null || !isset($fkConfig[$resolvedFieldKey])) {
                return ['success' => false, 'message' => 'Field relasi tidak ditemukan.'];
            }
            $field = $resolvedFieldKey;
        }

        return [
            'success' => true,
            'field' => $field,
            'options' => $fkConfig[$field]['options'] ?? [],
        ];
    }

    public function actionFkQuickAdd($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $field = (string)Yii::$app->request->post('field', '');
        $payload = Yii::$app->request->post('payload', []);
        if (is_string($payload) && trim($payload) !== '') {
            $decodedPayload = json_decode($payload, true);
            if (is_array($decodedPayload)) {
                $payload = $decodedPayload;
            }
        }
        if (!is_array($payload)) {
            $payload = [];
        }
        if ($field === '') {
            return ['success' => false, 'message' => 'Field relasi wajib diisi.'];
        }

        $fkConfig = $this->getResolvedForeignKeyConfigForForm($model);
        if (!isset($fkConfig[$field])) {
            $resolvedFieldKey = $this->resolveForeignKeyKey($fkConfig, $field, $field);
            if ($resolvedFieldKey === null || !isset($fkConfig[$resolvedFieldKey])) {
                return ['success' => false, 'message' => 'Konfigurasi relasi untuk field tidak ditemukan.'];
            }
            $field = $resolvedFieldKey;
        }

        $config = $fkConfig[$field];
        $referencedTable = (string)($config['referencedTable'] ?? '');
        $referencedColumn = (string)($config['referencedColumn'] ?? '');
        $displayColumn = isset($config['displayColumn']) ? (string)$config['displayColumn'] : '';
        if ($referencedTable === '' || $referencedColumn === '') {
            return ['success' => false, 'message' => 'Konfigurasi referensi tidak valid.'];
        }

        $tableSchema = Yii::$app->db->schema->getTableSchema($referencedTable, true);
        if ($tableSchema === null) {
            return ['success' => false, 'message' => 'Tabel referensi tidak ditemukan.'];
        }

        $quickAddFields = [];
        foreach (($config['quickAddFields'] ?? []) as $quickField) {
            $fieldName = (string)($quickField['name'] ?? '');
            if ($fieldName !== '' && isset($tableSchema->columns[$fieldName]) && !SystemFieldService::shouldHideFromForm($tableSchema->columns[$fieldName])) {
                $quickAddFields[$fieldName] = true;
            }
        }

        $insertData = [];
        foreach ($quickAddFields as $fieldName => $enabled) {
            if (!$enabled) {
                continue;
            }
            $value = array_key_exists($fieldName, $payload) ? $payload[$fieldName] : null;
            $value = is_string($value) ? trim($value) : $value;
            if ($value === null || $value === '') {
                return [
                    'success' => false,
                    'message' => "Field '{$fieldName}' wajib diisi sebelum menambah data baru.",
                ];
            }
            $insertData[$fieldName] = $value;
        }

        if ($displayColumn !== '' && !array_key_exists($displayColumn, $insertData) && array_key_exists($displayColumn, $payload)) {
            $displayValue = is_string($payload[$displayColumn]) ? trim((string)$payload[$displayColumn]) : $payload[$displayColumn];
            if ($displayValue !== null && $displayValue !== '') {
                $insertData[$displayColumn] = $displayValue;
            }
        }

        if (empty($insertData)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dapat disimpan.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $insertData = SystemFieldService::applyCreateValues($insertData, $tableSchema->columns);
            Yii::$app->db->createCommand()->insert($referencedTable, $insertData)->execute();
            $transaction->commit();
        } catch (IntegrityException $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Data gagal disimpan karena melanggar aturan relasi.'];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Gagal menambah data baru.'];
        }

        $newValue = $this->resolveInsertedReferenceValue($referencedTable, $referencedColumn, $insertData);
        if ($newValue === null || $newValue === '') {
            return ['success' => false, 'message' => 'Data berhasil ditambah, tetapi nilai referensi baru tidak dapat ditentukan.'];
        }

        $newLabel = '';
        if ($displayColumn !== '' && array_key_exists($displayColumn, $insertData)) {
            $newLabel = (string)$insertData[$displayColumn];
        }
        if ($newLabel === '') {
            $newLabel = 'Record #' . $newValue;
        }

        return [
            'success' => true,
            'option' => [
                'value' => $newValue,
                'label' => $newLabel,
            ],
            'field' => $field,
        ];
    }

    /**
     * Publish a form (creates a published form entry)
     */
    public function actionPublish($id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Support both GET id and POST form_id
        $formId = Yii::$app->request->post('form_id', $id);

        Yii::info("Publish action called with formId: $formId, isAjax: " . (Yii::$app->request->isAjax ? 'yes' : 'no'), 'app');

        if (!$formId) {
            return ['success' => false, 'message' => 'Form ID is required.'];
        }

        try {
            $form = $this->findModel($formId);

            // Check if form_pages is sent (from publish modal with custom design)
            $pagesData = Yii::$app->request->post('form_pages');
            if ($pagesData) {
                Yii::info('Publish modal sent form_pages data', 'app');
                Yii::info('Raw form_pages: ' . $pagesData, 'app');
                $decoded = json_decode($pagesData, true);
                if ($decoded) {
                    Yii::info('Decoded form_pages: ' . json_encode($decoded), 'app');

                    $form->schema_js = $this->normalizeBuilderSchema($pagesData, (string)$form->schema_js);
                    
                    if (!$form->save()) {
                        Yii::error('Failed to save form with custom design: ' . print_r($form->errors, true), 'app');
                        return ['success' => false, 'message' => 'Failed to save form design: ' . implode(', ', $form->getFirstErrors())];
                    }
                    
                    Yii::info('✅ Form saved successfully with custom design', 'app');
                }
            }

            // Check if already published
            $effectiveUserId = $this->getEffectiveUserId();
            $existingPublished = PublishedForm::find()
                ->where(['form_id' => $formId, 'user_id' => $effectiveUserId])
                ->one();

            if (Yii::$app->request->isPost) {
                $name = Yii::$app->request->post('name', $form->name);

                Yii::info("Publishing form with name: $name, formId: $formId", 'app');

                if ($existingPublished) {
                    // Update existing published form
                    $existingPublished->name = $name;
                    if ($existingPublished->save()) {
                        $baseUrl = $this->getPublicUrl();
                        $formUrl = $baseUrl . '/form/public-render/' . $formId;

                        return [
                            'success' => true,
                            'message' => 'Form published successfully!',
                            'publicUrl' => $formUrl
                        ];
                    } else {
                        Yii::error('Failed to update published form: ' . print_r($existingPublished->errors, true), 'app');
                        return ['success' => false, 'message' => 'Failed to update: ' . implode(', ', $existingPublished->getFirstErrors())];
                    }
                } else {
                    // Create new published form
                    $publishedForm = new PublishedForm();
                    if ($effectiveUserId !== null) {
                        $publishedForm->user_id = $effectiveUserId;
                    }
                    $publishedForm->form_id = $formId;
                    $publishedForm->name = $name;

                    if ($publishedForm->save()) {
                        $baseUrl = $this->getPublicUrl();
                        $formUrl = $baseUrl . '/form/public-render/' . $formId;

                        return [
                            'success' => true,
                            'message' => 'Form published successfully!',
                            'publicUrl' => $formUrl
                        ];
                    } else {
                        Yii::error('Failed to create published form: ' . print_r($publishedForm->errors, true), 'app');
                        return ['success' => false, 'message' => 'Failed to publish: ' . implode(', ', $publishedForm->getFirstErrors())];
                    }
                }
            }

            // For GET requests, return form info
            return [
                'success' => true,
                'form' => [
                    'id' => $form->id,
                    'name' => $form->name,
                    'published' => $existingPublished !== null,
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Publish error: ' . $e->getMessage(), 'app');
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get the application base URL for public links.
     * Uses current request host to ensure links work in any environment (Local/Cloud).
     * @return string
     */
    protected function getPublicUrl()
    {
        $baseUrl = Yii::$app->request->hostInfo;
        return rtrim($baseUrl, '/');
    }

    private function getPhysicalDb(?int $projectId = null): Connection
    {
        $metadataDb = Yii::$app->db;
        $activeProjectId = $projectId ?? $this->getActiveProjectId();
        
        if ($activeProjectId === null) {
            return $metadataDb;
        }

        $project = Project::findOne($activeProjectId);
        if ($project === null) {
            return $metadataDb;
        }

        $databaseContext = new ActiveDatabaseContext();
        $legacyDatabaseName = sprintf('proj_u%d_p%d', (int)$project->user_id, (int)$project->id);

        $customDatabaseName = strtolower(trim((string)$project->name));
        $customDatabaseName = preg_replace('/[^a-z0-9]+/i', '_', $customDatabaseName) ?? '';
        $customDatabaseName = trim($customDatabaseName, '_');
        if ($customDatabaseName === '') {
            $customDatabaseName = 'project';
        }
        if (preg_match('/^[0-9]/', $customDatabaseName) === 1) {
            $customDatabaseName = 'project_' . $customDatabaseName;
        }
        if (strlen($customDatabaseName) > 64) {
            $customDatabaseName = rtrim(substr($customDatabaseName, 0, 64), '_');
        }

        $targetDatabase = $databaseContext->databaseExistsOnCurrentServer($legacyDatabaseName)
            && !$databaseContext->databaseExistsOnCurrentServer($customDatabaseName)
            ? $legacyDatabaseName
            : $customDatabaseName;

        if ($targetDatabase === '') {
            return $metadataDb;
        }

        $dsn = (string)$metadataDb->dsn;
        if (stripos($dsn, 'mysql:') !== 0) {
            return $metadataDb;
        }

        if (preg_match('/dbname=([^;]+)/i', $dsn, $matches) === 1 && trim((string)$matches[1]) === $targetDatabase) {
            return $metadataDb;
        }

        $projectDsn = preg_match('/dbname=([^;]+)/i', $dsn)
            ? (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $targetDatabase, $dsn, 1)
            : rtrim($dsn, ';') . ';dbname=' . $targetDatabase;

        $connection = Yii::createObject([
            'class' => Connection::class,
            'dsn' => $projectDsn,
            'username' => $metadataDb->username,
            'password' => $metadataDb->password,
            'charset' => $metadataDb->charset,
            'tablePrefix' => $metadataDb->tablePrefix,
            'attributes' => $metadataDb->attributes,
            'enableSchemaCache' => $metadataDb->enableSchemaCache,
            'schemaCacheDuration' => $metadataDb->schemaCacheDuration,
            'schemaCacheExclude' => $metadataDb->schemaCacheExclude,
            'schemaCache' => $metadataDb->schemaCache,
            'enableQueryCache' => $metadataDb->enableQueryCache,
            'queryCacheDuration' => $metadataDb->queryCacheDuration,
            'queryCache' => $metadataDb->queryCache,
        ]);
        $connection->open();

        return $connection;
    }

    /**
     * Finds the Form model based on its primary key value.
     *
     * @param integer $id
     * @return Form the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     * @throws ForbiddenHttpException if user does not own the form
     */
    protected function findModel($id, $checkOwnership = true)
    {
        $id = (int)$id;
        $isCommanderSuperAdmin = (new CommanderAuthContext())->isSuperAdmin();

        if (!$checkOwnership) {
            $model = Form::findOne($id);
            if ($model !== null) {
                return $model;
            }
            throw new NotFoundHttpException('The requested form does not exist.');
        }

        $criteria = [
            'id' => $id,
        ];
        if (!$isCommanderSuperAdmin) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $criteria['user_id'] = $effectiveUserId;
            }
        }

        $activeProjectId = $this->getActiveProjectId();
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $criteria['project_id'] = $activeProjectId;
        }

        if (($model = Form::findOne($criteria)) !== null) {
            return $model;
        }

        if (Form::find()->where(['id' => $id])->exists()) {
            throw new ForbiddenHttpException('You are not allowed to access this form in current project.');
        }

        throw new NotFoundHttpException('The requested form does not exist.');
    }
}
