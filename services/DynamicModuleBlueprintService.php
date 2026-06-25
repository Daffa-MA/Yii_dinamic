<?php

namespace app\services;

use app\components\ActiveProjectContext;
use app\components\ProjectSchema;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterForm;
use app\models\MasterDatatable;
use app\models\MasterMenu;
use Yii;
use yii\db\Connection;
use yii\helpers\Json;

class DynamicModuleBlueprintService
{
    public function apply(array $blueprint, ?int $userId = null, ?int $projectId = null): array
    {
        $db = Yii::$app->db;
        $userId = $userId ?: $this->resolveCurrentUserId();
        $projectId = $projectId ?? (new ActiveProjectContext())->getActiveProjectId();
        $created = [
            'tables' => [],
            'forms' => [],
            'datatables' => [],
            'menus' => [],
        ];

        foreach ((array)($blueprint['tables'] ?? []) as $tableConfig) {
            if (!is_array($tableConfig)) {
                continue;
            }
            $table = $this->ensureTable($db, $tableConfig, $userId, $projectId);
            if ($table instanceof DbTable) {
                $created['tables'][(string)$table->name] = (int)$table->id;
            }
        }

        foreach ((array)($blueprint['forms'] ?? []) as $formConfig) {
            if (!is_array($formConfig)) {
                continue;
            }
            $form = $this->ensureForm($formConfig, $created['tables'], $userId, $projectId);
            if ($form instanceof MasterForm) {
                $created['forms'][(string)$form->form_name] = (int)$form->id;
            }
        }

        foreach ((array)($blueprint['datatables'] ?? []) as $viewConfig) {
            if (!is_array($viewConfig)) {
                continue;
            }
            $datatable = $this->ensureDatatable($viewConfig, $created['tables'], $userId, $projectId);
            if ($datatable instanceof MasterDatatable) {
                $created['datatables'][(string)$datatable->name] = (int)$datatable->id;
            }
        }

        foreach ((array)($blueprint['menus'] ?? []) as $menuConfig) {
            if (!is_array($menuConfig)) {
                continue;
            }
            $menu = $this->ensureMenu($menuConfig);
            if ($menu instanceof MasterMenu) {
                $created['menus'][(string)$menu->name] = (int)$menu->id;
            }
        }

        return $created;
    }

    private function resolveCurrentUserId(): int
    {
        if (Yii::$app->has('user', true) && Yii::$app->user && !Yii::$app->user->isGuest) {
            return (int)Yii::$app->user->id ?: 1;
        }

        return 1;
    }

    private function ensureTable(Connection $db, array $config, int $userId, ?int $projectId): ?DbTable
    {
        $name = strtolower(trim((string)($config['name'] ?? '')));
        if ($name === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            return null;
        }

        $query = DbTable::find()->where(['name' => $name]);
        $dbTableSchema = DbTable::getTableSchema();
        if (ProjectSchema::supportsProjectContext() && $projectId !== null && $dbTableSchema !== null && $dbTableSchema->getColumn('project_id') !== null) {
            $query->andWhere(['project_id' => $projectId]);
        }
        $table = $query->one();
        if (!$table instanceof DbTable) {
            $table = new DbTable();
            $table->user_id = $userId;
            if ($table->hasAttribute('project_id')) {
                $table->project_id = $projectId;
            }
            $table->name = $name;
            $table->label = trim((string)($config['label'] ?? $name)) ?: $name;
            $table->description = trim((string)($config['description'] ?? ''));
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->save(false);
        }

        $columns = $this->normalizeColumns((array)($config['columns'] ?? []));
        $this->ensureMetadataColumns($table, $columns);
        $this->ensurePhysicalTable($db, (string)$table->name, $columns);

        if ($table->hasAttribute('is_created')) {
            $table->setAttribute('is_created', 1);
        }
        if ($table->hasAttribute('table_status')) {
            $table->setAttribute('table_status', 'created');
        }
        $table->save(false);

        return $table;
    }

    private function normalizeColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }
            $name = strtolower(trim((string)($column['name'] ?? '')));
            if ($name === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                continue;
            }
            $normalized[$name] = [
                'name' => $name,
                'label' => trim((string)($column['label'] ?? $name)) ?: $name,
                'type' => strtoupper(trim((string)($column['type'] ?? 'VARCHAR'))),
                'length' => (int)($column['length'] ?? 191),
                'nullable' => !empty($column['nullable']),
                'primary' => !empty($column['primary']),
                'auto_increment' => !empty($column['auto_increment']),
                'default' => $column['default'] ?? null,
                'foreign_key' => is_array($column['foreign_key'] ?? null) ? $column['foreign_key'] : null,
            ];
        }

        if (!isset($normalized['id'])) {
            $normalized = ['id' => [
                'name' => 'id',
                'label' => 'ID',
                'type' => 'INT',
                'length' => 11,
                'nullable' => false,
                'primary' => true,
                'auto_increment' => true,
                'default' => null,
                'foreign_key' => null,
            ]] + $normalized;
        }

        return array_values($normalized);
    }

    private function ensureMetadataColumns(DbTable $table, array $columns): void
    {
        foreach ($columns as $index => $column) {
            $model = DbTableColumn::find()
                ->where(['table_id' => (int)$table->id, 'name' => (string)$column['name']])
                ->one();
            if (!$model instanceof DbTableColumn) {
                $model = new DbTableColumn();
                $model->table_id = (int)$table->id;
                $model->name = (string)$column['name'];
            }
            $model->label = (string)$column['label'];
            $model->type = (string)$column['type'];
            $model->length = (int)$column['length'];
            $model->is_nullable = !empty($column['nullable']) ? 1 : 0;
            $model->is_primary = !empty($column['primary']) ? 1 : 0;
            if ($model->hasAttribute('is_auto_increment')) {
                $model->setAttribute('is_auto_increment', !empty($column['auto_increment']) ? 1 : 0);
            }
            $model->sort_order = $index;
            $fk = $column['foreign_key'];
            if ($fk !== null && $model->hasAttribute('is_foreign_key')) {
                $model->setAttribute('is_foreign_key', 1);
                $model->setAttribute('referenced_table_name', (string)($fk['table'] ?? ''));
                $model->setAttribute('referenced_column_name', (string)($fk['column'] ?? 'id'));
            }
            $model->save(false);
        }
    }

    private function ensurePhysicalTable(Connection $db, string $tableName, array $columns): void
    {
        if ($db->schema->getTableSchema($tableName, true) !== null) {
            return;
        }

        $definitions = [];
        $primaryKeys = [];
        foreach ($columns as $column) {
            $definition = $this->columnSql($column);
            $definitions[] = '`' . str_replace('`', '``', (string)$column['name']) . '` ' . $definition;
            if (!empty($column['primary'])) {
                $primaryKeys[] = '`' . str_replace('`', '``', (string)$column['name']) . '`';
            }
        }
        if (!empty($primaryKeys)) {
            $definitions[] = 'PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
        }

        $sql = 'CREATE TABLE `' . str_replace('`', '``', $tableName) . '` (' . implode(',', $definitions) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $db->createCommand($sql)->execute();
        $db->schema->refreshTableSchema($tableName);
    }

    private function columnSql(array $column): string
    {
        $type = strtoupper((string)$column['type']);
        $length = (int)$column['length'];
        if (in_array($type, ['INT', 'INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT'], true)) {
            $sqlType = $type . ($length > 0 ? '(' . $length . ')' : '');
        } elseif (in_array($type, ['TEXT', 'DATE', 'DATETIME', 'TIMESTAMP', 'DECIMAL', 'FLOAT', 'DOUBLE'], true)) {
            $sqlType = $type;
        } else {
            $sqlType = 'VARCHAR(' . max(1, min(255, $length ?: 191)) . ')';
        }
        $parts = [$sqlType];
        $parts[] = !empty($column['nullable']) ? 'NULL' : 'NOT NULL';
        if (!empty($column['auto_increment'])) {
            $parts[] = 'AUTO_INCREMENT';
        } elseif (array_key_exists('default', $column) && $column['default'] !== null) {
            $parts[] = "DEFAULT '" . str_replace("'", "''", (string)$column['default']) . "'";
        }

        return implode(' ', $parts);
    }

    private function ensureDatatable(array $config, array $tableIds, int $userId, ?int $projectId): ?MasterDatatable
    {
        $name = trim((string)($config['name'] ?? ''));
        $tableRef = trim((string)($config['table'] ?? ''));
        $tableId = (int)($config['table_id'] ?? ($tableIds[$tableRef] ?? 0));
        if ($name === '' || $tableId <= 0) {
            return null;
        }

        $model = MasterDatatable::findScoped()->andWhere(['name' => $name])->one();
        if (!$model instanceof MasterDatatable) {
            $model = new MasterDatatable();
            $model->user_id = $userId;
            if ($model->hasAttribute('project_id')) {
                $model->project_id = $projectId;
            }
        }

        $model->name = $name;
        $model->table_id = $tableId;
        $model->columns_config = Json::encode((array)($config['columns'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $model->filters_config = Json::encode((array)($config['filters'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $model->stats_config = Json::encode((array)($config['stats'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $model->workflow_config = Json::encode((array)($config['workflow'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $model->actions_config = Json::encode((array)($config['actions'] ?? ['view' => true, 'edit' => true, 'delete' => true]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $model->search_enabled = array_key_exists('search', $config) ? (int)(bool)$config['search'] : 1;
        $model->pagination_enabled = array_key_exists('pagination', $config) ? (int)(bool)$config['pagination'] : 1;
        $model->is_active = 1;
        $model->save(false);

        return $model;
    }

    private function ensureForm(array $config, array $tableIds, int $userId, ?int $projectId): ?MasterForm
    {
        $name = trim((string)($config['name'] ?? ''));
        $tableRef = trim((string)($config['table'] ?? ''));
        $tableId = (int)($config['table_id'] ?? ($tableIds[$tableRef] ?? 0));
        if ($name === '' || $tableId <= 0) {
            return null;
        }

        $table = DbTable::findOne($tableId);
        if (!$table instanceof DbTable) {
            return null;
        }

        $model = MasterForm::findScoped()->andWhere(['form_name' => $name])->one();
        if (!$model instanceof MasterForm) {
            $model = new MasterForm();
            if ($model->hasAttribute('user_id')) {
                $model->setAttribute('user_id', $userId);
            }
            if ($model->hasAttribute('project_id')) {
                $model->project_id = $projectId;
            }
        }

        $fields = is_array($config['fields'] ?? null)
            ? (array)$config['fields']
            : $this->buildFormFieldsFromTable($table);

        $model->form_name = $name;
        $model->slug = strtolower(trim((string)($config['slug'] ?? preg_replace('/[^a-z0-9]+/i', '-', $name)), '-'));
        $model->table_id = $tableId;
        $model->form_type = 'builder';
        $model->form_data = ['fields' => $fields];
        $model->is_active = 1;
        $model->save(false);

        try {
            (new FormEngineService())->getResolvedFormSchema($model);
        } catch (\Throwable $e) {
            Yii::warning('Dynamic module form sync failed: ' . $e->getMessage(), 'dynamic-module');
        }

        return $model;
    }

    private function buildFormFieldsFromTable(DbTable $table): array
    {
        $fields = [];
        foreach ($table->getColumns()->orderBy(['sort_order' => SORT_ASC])->all() as $column) {
            if ((bool)$column->is_primary || in_array((string)$column->name, ['created_at', 'created_by', 'updated_at', 'updated_by'], true)) {
                continue;
            }
            $type = strtolower((string)$column->type);
            $inputType = 'text';
            if (in_array($type, ['text', 'mediumtext', 'longtext'], true)) {
                $inputType = 'textarea';
            } elseif (in_array($type, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double'], true)) {
                $inputType = 'number';
            } elseif ($type === 'date') {
                $inputType = 'date';
            } elseif (in_array($type, ['datetime', 'timestamp'], true)) {
                $inputType = 'datetime-local';
            }

            $field = [
                'name' => (string)$column->name,
                'field_name' => (string)$column->name,
                'column_name' => (string)$column->name,
                'label' => (string)($column->label ?: $column->name),
                'inputType' => $inputType,
                'type' => $inputType,
                'required' => !(bool)$column->is_nullable,
            ];

            if ($column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key')) {
                $field['inputType'] = 'select';
                $field['type'] = 'select';
                $field['is_foreign_key'] = true;
                $field['picker_mode'] = 'autocomplete_with_modal';
                $field['relation_config'] = [
                    'local_column' => (string)$column->name,
                    'referenced_table' => (string)$column->getAttribute('referenced_table_name'),
                    'referenced_column' => (string)$column->getAttribute('referenced_column_name'),
                ];
            }

            $fields[] = $field;
        }

        return $fields;
    }

    private function ensureMenu(array $config): ?MasterMenu
    {
        $name = trim((string)($config['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $menu = MasterMenu::find()->where(['name' => $name])->one();
        if (!$menu instanceof MasterMenu) {
            $menu = new MasterMenu();
            $menu->name = $name;
        }
        $menu->type = trim((string)($config['type'] ?? MasterMenu::TYPE_ROUTE));
        $menu->route = trim((string)($config['route'] ?? ''));
        $menu->icon = trim((string)($config['icon'] ?? 'folder'));
        $menu->is_active = 1;
        $menu->sort_order = (int)($config['sort_order'] ?? 0);
        $menu->save(false);

        return $menu;
    }
}
