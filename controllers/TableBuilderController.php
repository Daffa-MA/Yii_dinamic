<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;

class TableBuilderController extends Controller
{
    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    private function assignActiveProject(DbTable $model): void
    {
        if (!$model->hasAttribute('project_id')) {
            return;
        }

        $activeProjectId = $this->getActiveProjectId();
        $model->project_id = $activeProjectId !== null ? (int)$activeProjectId : null;
    }

    private function hasPhysicalTableByName(string $tableName): bool
    {
        return Yii::$app->db->schema->getTableSchema($tableName, true) !== null;
    }

    private function hasPhysicalTable(DbTable $model): bool
    {
        return $this->hasPhysicalTableByName($model->name);
    }

    /**
     * Keep metadata status in sync with actual physical table existence.
     */
    private function syncTableCreationState(DbTable $model, bool $save = true): bool
    {
        $exists = $this->hasPhysicalTable($model);
        $current = (bool)$model->is_created;
        if ($current !== $exists) {
            $model->is_created = $exists;
            if ($save) {
                $model->save(false, ['is_created']);
            }
        }

        return $exists;
    }

    /**
     * Determine whether a metadata column should behave as AUTO_INCREMENT.
     */
    private function isAutoIncrementColumn(DbTableColumn $column): bool
    {
        $integerTypes = ['INT', 'BIGINT', 'TINYINT'];
        if ($column->hasAttribute('is_auto_increment')) {
            return (bool)$column->getAttribute('is_auto_increment');
        }

        // Backward compatibility for environments where metadata column doesn't exist yet.
        return (bool)$column->is_primary && in_array(strtoupper((string)$column->type), $integerTypes, true);
    }

    private function buildCreateTableSql(DbTable $model, array $columns): string
    {
        $db = Yii::$app->db;
        $columnDefs = [];
        $primaryKeys = [];
        $autoIncrementCandidates = [];

        foreach ($columns as $col) {
            $type = $col->getMySQLType();
            $isAutoIncrement = $this->isAutoIncrementColumn($col);
            $nullable = ($col->is_primary || $isAutoIncrement) ? 'NOT NULL' : ($col->is_nullable ? 'NULL' : 'NOT NULL');
            $default = ($isAutoIncrement || $col->default_value === null) ? '' : 'DEFAULT ' . $db->quoteValue($col->default_value);
            $comment = $col->comment ? 'COMMENT ' . $db->quoteValue($col->comment) : '';
            $autoIncrementSql = $isAutoIncrement ? 'AUTO_INCREMENT' : '';

            $def = "`{$col->name}` {$type} {$nullable} {$default} {$autoIncrementSql} {$comment}";
            $columnDefs[] = trim($def);

            if ($col->is_primary) {
                $primaryKeys[] = "`{$col->name}`";
            }
            if ($isAutoIncrement) {
                $autoIncrementCandidates[] = $col->name;
            }
        }

        if (count($autoIncrementCandidates) > 1) {
            throw new \RuntimeException('Only one AUTO_INCREMENT column is allowed per table.');
        }
        if (!empty($autoIncrementCandidates) && count($primaryKeys) !== 1) {
            throw new \RuntimeException('AUTO_INCREMENT requires exactly one primary key column.');
        }

        if (!empty($primaryKeys)) {
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }

        foreach ($columns as $col) {
            if ($col->is_unique && !$col->is_primary) {
                $columnDefs[] = "UNIQUE KEY `uk_{$col->name}` (`{$col->name}`)";
            }
        }

        return "CREATE TABLE `{$model->name}` (\n    " . implode(",\n    ", $columnDefs) . "\n) ENGINE={$model->engine} DEFAULT CHARSET={$model->charset} COLLATE={$model->collation}";
    }

    private function buildColumnModels(array $columns, int $tableId): array
    {
        $columnModels = [];
        $seenNames = [];

        foreach ($columns as $index => $colData) {
            if (empty($colData['name']) && empty($colData['label']) && empty($colData['type'])) {
                continue;
            }

            $column = new DbTableColumn();
            $column->table_id = $tableId;
            $column->name = strtolower(trim((string)($colData['name'] ?? '')));
            $column->label = trim((string)($colData['label'] ?? ''));
            $column->type = (string)($colData['type'] ?? '');
            $column->length = $colData['length'] !== '' && $colData['length'] !== null ? (int)$colData['length'] : null;
            $column->is_nullable = (bool)($colData['is_nullable'] ?? false);
            $column->is_primary = (bool)($colData['is_primary'] ?? false);
            $column->is_unique = (bool)($colData['is_unique'] ?? false);
            if ($column->hasAttribute('is_auto_increment')) {
                $column->setAttribute('is_auto_increment', (bool)($colData['is_auto_increment'] ?? false));
            }
            $column->default_value = $colData['default_value'] !== '' ? (string)$colData['default_value'] : null;
            $column->comment = $colData['comment'] !== '' ? (string)$colData['comment'] : null;
            $column->sort_order = $index;

            if ($this->isAutoIncrementColumn($column)) {
                $type = strtoupper((string)$column->type);
                if (!in_array($type, ['INT', 'BIGINT', 'TINYINT'], true)) {
                    $column->addError('is_auto_increment', 'Auto increment is only supported for INT, BIGINT, or TINYINT.');
                }
                $column->is_primary = true;
                $column->is_nullable = false;
            }

            if ($column->label === '' && $column->name !== '') {
                $column->label = ucwords(str_replace('_', ' ', $column->name));
            }

            if ($column->name !== '' && !preg_match('/^[a-z][a-z0-9_]*$/', $column->name)) {
                $column->addError('name', 'Column name must start with a letter and contain only lowercase letters, numbers, and underscores.');
            }

            if ($column->name !== '') {
                if (isset($seenNames[$column->name])) {
                    $column->addError('name', "Duplicate column name '{$column->name}' is not allowed.");
                }
                $seenNames[$column->name] = true;
            }

            $columnModels[] = $column;
        }

        return $columnModels;
    }

    private function collectColumnErrors(array $columnModels): array
    {
        $errors = [];

        foreach ($columnModels as $column) {
            if (!$column->validate()) {
                $identifier = $column->label ?: $column->name ?: 'Unnamed column';
                $errors[] = "Column '{$identifier}': " . implode(', ', $column->getErrorSummary(true));
            }
        }

        return $errors;
    }

    /**
     * Rebuild physical SQL table after metadata update and migrate overlapping data.
     */
    private function syncUpdatedPhysicalTable(DbTable $model, string $oldTableName, array $columnModels): void
    {
        $db = Yii::$app->db;
        $newTableName = (string)$model->name;

        if (!$this->hasPhysicalTableByName($oldTableName)) {
            $model->is_created = false;
            $model->save(false, ['is_created']);
            return;
        }

        if ($newTableName !== $oldTableName && $this->hasPhysicalTableByName($newTableName)) {
            throw new \RuntimeException("Cannot rename table to '{$newTableName}' because it already exists in database.");
        }

        $backupTableName = $oldTableName . '__bak_' . time();
        if ($this->hasPhysicalTableByName($backupTableName)) {
            $backupTableName .= '_' . mt_rand(1000, 9999);
        }

        $renamedToBackup = false;
        $newTableCreated = false;

        try {
            $db->createCommand("RENAME TABLE `{$oldTableName}` TO `{$backupTableName}`")->execute();
            $renamedToBackup = true;

            $sql = $this->buildCreateTableSql($model, $columnModels);
            $db->createCommand($sql)->execute();
            $newTableCreated = true;

            $backupSchema = $db->schema->getTableSchema($backupTableName, true);
            $newSchema = $db->schema->getTableSchema($newTableName, true);

            if ($backupSchema && $newSchema) {
                $oldColumns = array_keys($backupSchema->columns);
                $newColumns = array_keys($newSchema->columns);
                $commonColumns = array_values(array_intersect($oldColumns, $newColumns));

                if (!empty($commonColumns)) {
                    $quotedCols = implode(', ', array_map(static function ($col) {
                        return "`{$col}`";
                    }, $commonColumns));
                    $db->createCommand("INSERT INTO `{$newTableName}` ({$quotedCols}) SELECT {$quotedCols} FROM `{$backupTableName}`")->execute();
                }
            }

            $db->createCommand("DROP TABLE `{$backupTableName}`")->execute();
            $model->is_created = true;
            $model->save(false, ['is_created']);
        } catch (\Throwable $e) {
            try {
                if ($newTableCreated && $this->hasPhysicalTableByName($newTableName)) {
                    $db->createCommand("DROP TABLE `{$newTableName}`")->execute();
                }
                if ($renamedToBackup && $this->hasPhysicalTableByName($backupTableName)) {
                    $db->createCommand("RENAME TABLE `{$backupTableName}` TO `{$oldTableName}`")->execute();
                }
            } catch (\Throwable $rollbackError) {
                Yii::error('Rollback after table sync failure also failed: ' . $rollbackError->getMessage(), 'app');
            }

            throw $e;
        }
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
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

        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = $this->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola table.');
            $this->redirect(['project/index']);
            return false;
        }

        return true;
    }

    public function actionIndex()
    {
        $activeProjectId = $this->getActiveProjectId();
        $tablesQuery = DbTable::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC]);
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $tablesQuery->andWhere(['project_id' => $activeProjectId]);
        }
        $tables = $tablesQuery->all();

        // Build array with tables and their columns
        $tablesWithColumns = [];
        foreach ($tables as $table) {
            $this->syncTableCreationState($table);
            $columns = $table->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
            $item = new \stdClass();
            $item->table = $table;
            $item->columns = $columns;
            $tablesWithColumns[] = $item;
        }

        return $this->render('index', [
            'tables' => $tablesWithColumns,
        ]);
    }

    public function actionCreate()
    {
        $model = new DbTable();
        $model->user_id = Yii::$app->user->id;
        $this->assignActiveProject($model);
        $model->engine = 'InnoDB';
        $model->charset = 'utf8mb4';
        $model->collation = 'utf8mb4_unicode_ci';

        // Preserve column data for re-rendering on validation failure
        $savedColumns = [];

        if ($model->load(Yii::$app->request->post())) {
            $model->user_id = Yii::$app->user->id;
            $this->assignActiveProject($model);
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }

            // Save columns for restoring on validation failure
            $savedColumns = $columns;

            try {
                if ($model->save()) {
                    $transaction = Yii::$app->db->beginTransaction();

                    try {
                        $columnModels = $this->buildColumnModels($columns, (int)$model->id);

                        if (empty($columnModels)) {
                            throw new \RuntimeException('Please add at least one valid column before creating the table.');
                        }

                        $columnErrors = $this->collectColumnErrors($columnModels);
                        if (!empty($columnErrors)) {
                            throw new \RuntimeException(implode('<br>', $columnErrors));
                        }

                        foreach ($columnModels as $column) {
                            if (!$column->save(false)) {
                                throw new \RuntimeException("Failed to save column '{$column->name}'.");
                            }
                        }

                        $transaction->commit();
                        Yii::$app->session->setFlash('success', "Table '{$model->name}' definition saved successfully. Status: pending database creation.");

                        return $this->redirect(['index']);
                    } catch (\Throwable $e) {
                        $transaction->rollBack();
                        $model->delete();
                        Yii::$app->session->setFlash('error', $e->getMessage());
                    }
                } else {
                    // Model validation failed - show error and preserve columns
                    Yii::$app->session->setFlash('error', 'Please fix the errors below: ' . implode(', ', $model->getErrorSummary(true)));
                }
            } catch (\yii\db\IntegrityException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $model->addError('name', 'A table with this name already exists. Please choose a different name.');
                    Yii::$app->session->setFlash('error', 'A table with this name already exists. Please choose a different name.');
                } else {
                    $model->addError('name', 'Database error: ' . $e->getMessage());
                    Yii::$app->session->setFlash('error', 'Database error: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'savedColumns' => $savedColumns,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        $isCreated = $this->syncTableCreationState($model);

        // Fetch actual data from the database table if created
        $tableData = [];
        if ($isCreated) {
            try {
                $tableData = Yii::$app->db->createCommand("SELECT * FROM `{$model->name}` ORDER BY id DESC LIMIT 100")->queryAll();
            } catch (\Exception $e) {
                $tableData = [];
            }
        }

        return $this->render('view', [
            'model' => $model,
            'columns' => $columns,
            'tableData' => $tableData,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldTableName = (string)$model->name;
        $wasPhysicallyCreated = $this->syncTableCreationState($model);
        $savedColumns = array_map(static function (DbTableColumn $column) {
            return [
                'name' => $column->name,
                'label' => $column->label,
                'type' => $column->type,
                'length' => $column->length,
                'is_nullable' => (bool)$column->is_nullable,
                'is_primary' => (bool)$column->is_primary,
                'is_unique' => (bool)$column->is_unique,
                'is_auto_increment' => $column->hasAttribute('is_auto_increment') ? (bool)$column->getAttribute('is_auto_increment') : false,
                'default_value' => $column->default_value,
                'comment' => $column->comment,
            ];
        }, $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all());

        if ($model->load(Yii::$app->request->post())) {
            $model->user_id = Yii::$app->user->id;
            $this->assignActiveProject($model);
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }
            $savedColumns = $columns;
            
            try {
                if ($model->save()) {
                    $transaction = Yii::$app->db->beginTransaction();
                    $columnModels = [];

                    try {
                        $columnModels = $this->buildColumnModels($columns, (int)$model->id);

                        if (empty($columnModels)) {
                            throw new \RuntimeException('Please keep at least one valid column on the table.');
                        }

                        $columnErrors = $this->collectColumnErrors($columnModels);
                        if (!empty($columnErrors)) {
                            throw new \RuntimeException(implode('<br>', $columnErrors));
                        }

                        DbTableColumn::deleteAll(['table_id' => $model->id]);

                        foreach ($columnModels as $column) {
                            if (!$column->save(false)) {
                                throw new \RuntimeException("Failed to save column '{$column->name}'.");
                            }
                        }

                        $transaction->commit();

                        if ($wasPhysicallyCreated) {
                            try {
                                $this->syncUpdatedPhysicalTable($model, $oldTableName, $columnModels);
                                Yii::$app->session->setFlash('success', "Table updated successfully and synced to database table '{$model->name}'.");
                            } catch (\Throwable $syncError) {
                                Yii::error('Failed to sync updated table to database: ' . $syncError->getMessage(), 'app');
                                Yii::$app->session->setFlash('warning', 'Table definition was updated, but failed to sync physical database table: ' . $syncError->getMessage());
                            }
                        } else {
                            Yii::$app->session->setFlash('success', 'Table updated successfully.');
                        }

                        return $this->redirect(['view', 'id' => $model->id]);
                    } catch (\Throwable $e) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', $e->getMessage());
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save table: ' . implode(', ', $model->getErrorSummary(true)));
                }
            } catch (\yii\db\IntegrityException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $model->addError('name', 'A table with this name already exists. Please choose a different name.');
                } else {
                    $model->addError('name', 'Database error: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'savedColumns' => $savedColumns,
        ]);
    }

    public function actionExecuteSql($id)
    {
        $model = $this->findModel($id);

        if ($this->syncTableCreationState($model)) {
            Yii::$app->session->setFlash('warning', 'Table already exists in database!');
            return $this->redirect(['view', 'id' => $id]);
        }

        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if (empty($columns)) {
            Yii::$app->session->setFlash('error', 'Table must have at least one column!');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $db = Yii::$app->db;
            if (!$model->validate(['name', 'engine', 'charset', 'collation'])) {
                throw new \RuntimeException(implode(', ', $model->getErrorSummary(true)));
            }

            $sql = $this->buildCreateTableSql($model, $columns);
            $db->createCommand($sql)->execute();

            if (!$this->hasPhysicalTable($model)) {
                throw new \RuntimeException("Table '{$model->name}' was not found after SQL execution.");
            }

            $model->is_created = true;
            $model->save(false, ['is_created']);

            $dbName = Yii::$app->db->createCommand('SELECT DATABASE()')->queryScalar();
            Yii::$app->session->setFlash('success', "Table '{$model->name}' created successfully in database '{$dbName}'.");
            
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Failed to create table: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->is_created) {
            try {
                Yii::$app->db->createCommand("DROP TABLE IF EXISTS `{$model->name}`")->execute();
            } catch (\Exception $e) {
                // Ignore drop errors
            }
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Table deleted successfully!');

        return $this->redirect(['index']);
    }

    public function actionPreviewSql($id)
    {
        $model = $this->findModel($id);
        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if (empty($columns)) {
            return $this->asJson(['sql' => '-- No columns defined']);
        }

        $sql = $this->buildCreateTableSql($model, $columns);

        return $this->asJson(['sql' => $sql]);
    }

    protected function findModel($id)
    {
        $activeProjectId = $this->getActiveProjectId();
        $criteria = [
            'id' => (int)$id,
            'user_id' => Yii::$app->user->id,
        ];
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $criteria['project_id'] = $activeProjectId;
        }

        $model = DbTable::findOne($criteria);

        if ($model !== null) {
            return $model;
        }

        if (DbTable::find()->where(['id' => (int)$id])->exists()) {
            throw new ForbiddenHttpException('You are not allowed to access this table.');
        }

        throw new NotFoundHttpException('The requested table does not exist.');
    }
}
