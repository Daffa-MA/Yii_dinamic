<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\models\DbTable;
use app\models\DbTableColumn;

class TableBuilderController extends Controller
{
    private function buildCreateTableSql(DbTable $model, array $columns): string
    {
        $db = Yii::$app->db;
        $columnDefs = [];
        $primaryKeys = [];

        foreach ($columns as $col) {
            $type = $col->getMySQLType();
            $nullable = $col->is_primary ? 'NOT NULL' : ($col->is_nullable ? 'NULL' : 'NOT NULL');
            $default = $col->default_value !== null ? 'DEFAULT ' . $db->quoteValue($col->default_value) : '';
            $comment = $col->comment ? 'COMMENT ' . $db->quoteValue($col->comment) : '';

            $def = "`{$col->name}` {$type} {$nullable} {$default} {$comment}";
            $columnDefs[] = trim($def);

            if ($col->is_primary) {
                $primaryKeys[] = "`{$col->name}`";
            }
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
            $column->default_value = $colData['default_value'] !== '' ? (string)$colData['default_value'] : null;
            $column->comment = $colData['comment'] !== '' ? (string)$colData['comment'] : null;
            $column->sort_order = $index;

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

    public function actionIndex()
    {
        $tables = DbTable::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Build array with tables and their columns
        $tablesWithColumns = [];
        foreach ($tables as $table) {
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
        $model->engine = 'InnoDB';
        $model->charset = 'utf8mb4';
        $model->collation = 'utf8mb4_unicode_ci';

        // Preserve column data for re-rendering on validation failure
        $savedColumns = [];

        if ($model->load(Yii::$app->request->post())) {
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
                        Yii::$app->session->setFlash('success', "Table '{$model->name}' created successfully.");

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

        // Fetch actual data from the database table if created
        $tableData = [];
        if ($model->is_created) {
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
        $savedColumns = array_map(static function (DbTableColumn $column) {
            return [
                'name' => $column->name,
                'label' => $column->label,
                'type' => $column->type,
                'length' => $column->length,
                'is_nullable' => (bool)$column->is_nullable,
                'is_primary' => (bool)$column->is_primary,
                'is_unique' => (bool)$column->is_unique,
                'default_value' => $column->default_value,
                'comment' => $column->comment,
            ];
        }, $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all());

        if ($model->load(Yii::$app->request->post())) {
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }
            $savedColumns = $columns;
            
            try {
                if ($model->save()) {
                    $transaction = Yii::$app->db->beginTransaction();

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
                        Yii::$app->session->setFlash('success', 'Table updated successfully.');

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
        
        if ($model->is_created) {
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
            
            $model->is_created = true;
            $model->save(false);
            
            Yii::$app->session->setFlash('success', "Table '{$model->name}' created successfully in database!");
            
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
        if (($model = DbTable::findOne($id)) !== null) {
            if ($model->user_id != Yii::$app->user->id) {
                throw new ForbiddenHttpException('You are not allowed to access this table.');
            }
            return $model;
        }
        throw new NotFoundHttpException('The requested table does not exist.');
    }
}
