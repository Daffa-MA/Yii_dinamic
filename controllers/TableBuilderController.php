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
            $tablesWithColumns[] = [
                'table' => $table,
                'columns' => $columns,
            ];
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

        if ($model->load(Yii::$app->request->post())) {
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }
            
            try {
                if ($model->save()) {
                    $columnErrors = [];
                    $savedCount = 0;
                    
                    foreach ($columns as $index => $colData) {
                        if (!empty($colData['name']) && !empty($colData['type'])) {
                            $column = new DbTableColumn();
                            $column->table_id = $model->id;
                            $column->name = $colData['name'];
                            $column->label = $colData['label'] ?: $colData['name'];
                            $column->type = $colData['type'];
                            $column->length = $colData['length'] ?: null;
                            // Fix: Check actual boolean value, not just isset
                            $column->is_nullable = (bool)($colData['is_nullable'] ?? false);
                            $column->is_primary = (bool)($colData['is_primary'] ?? false);
                            $column->is_unique = (bool)($colData['is_unique'] ?? false);
                            $column->default_value = $colData['default_value'] ?: null;
                            $column->comment = $colData['comment'] ?: null;
                            $column->sort_order = $index;
                            
                            // Check if save fails and collect errors
                            if (!$column->save()) {
                                $columnErrors[] = "Column '{$colData['name']}': " . implode(', ', $column->getErrorSummary(true));
                            } else {
                                $savedCount++;
                            }
                        }
                    }
                    
                    // If there were column save errors, show them
                    if (!empty($columnErrors)) {
                        Yii::$app->session->setFlash('warning', "Table created but some columns had errors:<br>" . implode('<br>', $columnErrors));
                    } else {
                        Yii::$app->session->setFlash('success', "Table created successfully with $savedCount column(s)! Click 'Execute SQL' to create it in database.");
                    }
                    
                    return $this->redirect(['view', 'id' => $model->id]);
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

        return $this->render('create', [
            'model' => $model,
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

        if ($model->load(Yii::$app->request->post())) {
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }
            
            try {
                if ($model->save()) {
                    // Delete existing columns
                    DbTableColumn::deleteAll(['table_id' => $model->id]);
                    
                    $columnErrors = [];
                    $savedCount = 0;
                    
                    // Add new columns
                    foreach ($columns as $index => $colData) {
                        if (!empty($colData['name']) && !empty($colData['type'])) {
                            $column = new DbTableColumn();
                            $column->table_id = $model->id;
                            $column->name = $colData['name'];
                            $column->label = $colData['label'] ?: $colData['name'];
                            $column->type = $colData['type'];
                            $column->length = $colData['length'] ?: null;
                            // Fix: Check actual boolean value, not just isset
                            $column->is_nullable = (bool)($colData['is_nullable'] ?? false);
                            $column->is_primary = (bool)($colData['is_primary'] ?? false);
                            $column->is_unique = (bool)($colData['is_unique'] ?? false);
                            $column->default_value = $colData['default_value'] ?: null;
                            $column->comment = $colData['comment'] ?: null;
                            $column->sort_order = $index;
                            
                            // Check if save fails and collect errors
                            if (!$column->save()) {
                                $columnErrors[] = "Column '{$colData['name']}': " . implode(', ', $column->getErrorSummary(true));
                            } else {
                                $savedCount++;
                            }
                        }
                    }
                    
                    // If there were column save errors, show them
                    if (!empty($columnErrors)) {
                        Yii::$app->session->setFlash('warning', "Table updated but some columns had errors:<br>" . implode('<br>', $columnErrors));
                    } else {
                        Yii::$app->session->setFlash('success', "Table updated successfully with $savedCount column(s)!");
                    }
                    
                    return $this->redirect(['view', 'id' => $model->id]);
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
            
            // Build CREATE TABLE SQL
            $columnDefs = [];
            $primaryKeys = [];
            
            foreach ($columns as $col) {
                $type = $col->getMySQLType();
                // PRIMARY KEY columns must NOT be NULL
                $nullable = $col->is_primary ? 'NOT NULL' : ($col->is_nullable ? 'NULL' : 'NOT NULL');
                $default = $col->default_value !== null ? "DEFAULT '{$col->default_value}'" : '';
                $comment = $col->comment ? "COMMENT '{$col->comment}'" : '';
                
                $def = "`{$col->name}` {$type} {$nullable} {$default} {$comment}";
                $columnDefs[] = trim($def);
                
                if ($col->is_primary) {
                    $primaryKeys[] = "`{$col->name}`";
                }
            }
            
            if (!empty($primaryKeys)) {
                $columnDefs[] = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
            }
            
            // Add unique constraints
            foreach ($columns as $col) {
                if ($col->is_unique && !$col->is_primary) {
                    $columnDefs[] = "UNIQUE KEY `uk_{$col->name}` (`{$col->name}`)";
                }
            }
            
            $sql = "CREATE TABLE `{$model->name}` (\n    " . implode(",\n    ", $columnDefs) . "\n) ENGINE={$model->engine} DEFAULT CHARSET={$model->charset} COLLATE={$model->collation}";
            
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

        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($columns as $col) {
            $type = $col->getMySQLType();
            // PRIMARY KEY columns must NOT be NULL
            $nullable = $col->is_primary ? 'NOT NULL' : ($col->is_nullable ? 'NULL' : 'NOT NULL');
            $default = $col->default_value !== null ? "DEFAULT '{$col->default_value}'" : '';
            $comment = $col->comment ? "COMMENT '{$col->comment}'" : '';
            
            $def = "`{$col->name}` {$type} {$nullable} {$default} {$comment}";
            $columnDefs[] = trim($def);
            
            if ($col->is_primary) {
                $primaryKeys[] = "`{$col->name}`";
            }
        }
        
        if (!empty($primaryKeys)) {
            $columnDefs[] = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
        }
        
        foreach ($columns as $col) {
            if ($col->is_unique && !$col->is_primary) {
                $columnDefs[] = "UNIQUE KEY `uk_{$col->name}` (`{$col->name}`)";
            }
        }
        
        $sql = "CREATE TABLE `{$model->name}` (\n    " . implode(",\n    ", $columnDefs) . "\n) ENGINE={$model->engine} DEFAULT CHARSET={$model->charset} COLLATE={$model->collation}";
        
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
