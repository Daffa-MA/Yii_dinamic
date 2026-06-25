<?php

namespace app\models;

use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectSchema;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class MasterDatatable extends ActiveRecord
{
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    public static function tableName(): string
    {
        return 'master_datatable';
    }

    public static function ensureStructure(): void
    {
        $db = static::getDb();
        if ($db->schema->getTableSchema(static::tableName(), true) === null) {
            $db->createCommand()->createTable(static::tableName(), [
                'id' => $db->schema->createColumnSchemaBuilder('pk'),
                'user_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
                'project_id' => $db->schema->createColumnSchemaBuilder('integer')->null(),
                'name' => $db->schema->createColumnSchemaBuilder('string', 160)->notNull(),
                'table_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
                'columns_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
                'actions_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
                'filters_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
                'stats_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
                'workflow_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
                'search_enabled' => $db->schema->createColumnSchemaBuilder('tinyint', 1)->notNull()->defaultValue(1),
                'pagination_enabled' => $db->schema->createColumnSchemaBuilder('tinyint', 1)->notNull()->defaultValue(1),
                'is_active' => $db->schema->createColumnSchemaBuilder('tinyint', 1)->notNull()->defaultValue(1),
                'created_at' => $db->schema->createColumnSchemaBuilder('datetime')->null(),
                'updated_at' => $db->schema->createColumnSchemaBuilder('datetime')->null(),
            ])->execute();

            $db->createCommand()->createIndex('idx-master_datatable-project', static::tableName(), ['project_id'])->execute();
            $db->createCommand()->createIndex('idx-master_datatable-table', static::tableName(), ['table_id'])->execute();
            $db->schema->refreshTableSchema(static::tableName());
        }

        $schema = $db->schema->getTableSchema(static::tableName(), true);
        if ($schema === null) {
            return;
        }

        $columnsToAdd = [
            'filters_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
            'stats_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
            'workflow_config' => $db->schema->createColumnSchemaBuilder('text')->null(),
        ];

        foreach ($columnsToAdd as $column => $definition) {
            if (!isset($schema->columns[$column])) {
                $db->createCommand()->addColumn(static::tableName(), $column, $definition)->execute();
                $db->schema->refreshTableSchema(static::tableName());
                $schema = $db->schema->getTableSchema(static::tableName(), true);
            }
        }
    }

    public function rules(): array
    {
        return [
            [['user_id', 'name', 'table_id'], 'required'],
            [['user_id', 'project_id', 'table_id', 'search_enabled', 'pagination_enabled', 'is_active'], 'integer'],
            [['columns_config', 'actions_config', 'filters_config', 'stats_config', 'workflow_config'], 'string'],
            [['name'], 'string', 'max' => 160],
        ];
    }

    public function behaviors(): array
    {
        $timestampExpression = static::getDb()->driverName === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new \yii\db\Expression($timestampExpression),
            ],
        ];
    }

    public static function findScoped(): ActiveQuery
    {
        static::ensureStructure();
        $query = static::find()->orderBy(['id' => SORT_DESC]);
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $query->andWhere(['project_id' => $activeProjectId]);
        }
        if (!(new CommanderAuthContext())->isSuperAdmin() && !Yii::$app->user->isGuest) {
            $query->andWhere(['user_id' => Yii::$app->user->id]);
        }
        return $query;
    }

    public function getTable(): ActiveQuery
    {
        return $this->hasOne(DbTable::class, ['id' => 'table_id']);
    }

    public function getColumnsConfigArray(): array
    {
        $decoded = json_decode((string)$this->columns_config, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getActionsConfigArray(): array
    {
        $decoded = json_decode((string)$this->actions_config, true);
        $decoded = is_array($decoded) ? $decoded : [];
        return array_merge([
            'view' => true,
            'edit' => true,
            'delete' => true,
            'edit_mode' => 'custom',
            'edit_form_id' => '',
        ], $decoded);
    }

    public function getFiltersConfigArray(): array
    {
        $decoded = json_decode((string)$this->filters_config, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getStatsConfigArray(): array
    {
        $decoded = json_decode((string)$this->stats_config, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getWorkflowConfigArray(): array
    {
        $decoded = json_decode((string)$this->workflow_config, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function toComponentConfig(): array
    {
        return [
            'datatableId' => (int)$this->id,
            'tableId' => (int)$this->table_id,
            'columns' => $this->getColumnsConfigArray(),
            'actions' => $this->getActionsConfigArray(),
            'filters' => $this->getFiltersConfigArray(),
            'stats' => $this->getStatsConfigArray(),
            'workflow' => $this->getWorkflowConfigArray(),
            'search' => (bool)$this->search_enabled,
            'pagination' => (bool)$this->pagination_enabled,
        ];
    }
}
