<?php

namespace app\models;

use app\components\ProjectSchema;
use Yii;
use yii\db\ActiveRecord;

/**
 * Form model - stores form definitions
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $project_id
 * @property integer $table_id
 * @property integer|null $db_table_id
 * @property string $storage_type
 * @property integer|bool $insert_to_table
 * @property string $name
 * @property string $schema_js
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property DbTable $table
 * @property FormSubmission[] $submissions
 */
class Form extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    /** @var string|null */
    private static $schemaStorageColumn;

    public static function tableName()
    {
        return 'forms';
    }

    /**
     * Resolve physical schema column name on current database.
     * Supports both legacy (schema_json) and newer (schema_js) schemas.
     *
     * @return string
     */
    public static function getSchemaStorageColumn()
    {
        if (self::$schemaStorageColumn !== null) {
            return self::$schemaStorageColumn;
        }

        $tableSchema = static::getTableSchema();
        if ($tableSchema && isset($tableSchema->columns['schema_js'])) {
            self::$schemaStorageColumn = 'schema_js';
            return self::$schemaStorageColumn;
        }

        self::$schemaStorageColumn = 'schema_json';
        return self::$schemaStorageColumn;
    }

    public function rules()
    {
        $requiresProject = $this->hasAttribute('project_id') && ProjectSchema::supportsProjectContext();
        $requiredAttributes = ['user_id', 'name', 'schema_js'];
        $integerAttributes = ['user_id', 'table_id'];
        if ($this->hasAttribute('db_table_id')) {
            $integerAttributes[] = 'db_table_id';
        }
        if ($requiresProject) {
            $requiredAttributes[] = 'project_id';
            $integerAttributes[] = 'project_id';
        }

        $rules = [
            [$requiredAttributes, 'required'],
            [$integerAttributes, 'integer'],
            [['storage_type'], 'string', 'max' => 20],
            [['schema_json', 'schema_js'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['storage_type'], 'in', 'range' => ['json', 'database']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['table_id'], 'exist', 'skipOnError' => true, 'targetClass' => DbTable::class, 'targetAttribute' => ['table_id' => 'id']],
            [['table_id'], 'validateTableBelongsToProject'],
        ];
        if ($this->hasAttribute('insert_to_table')) {
            $rules[] = [['insert_to_table'], 'boolean'];
        }
        if ($this->hasAttribute('db_table_id')) {
            $rules[] = [['db_table_id'], 'exist', 'skipOnError' => true, 'targetClass' => DbTable::class, 'targetAttribute' => ['db_table_id' => 'id']];
            $rules[] = [['db_table_id'], 'validateTableBelongsToProject'];
        }

        if ($requiresProject) {
            $rules[] = [['project_id'], 'exist', 'skipOnError' => true, 'targetClass' => Project::class, 'targetAttribute' => ['project_id' => 'id']];
        }

        return $rules;
    }

    public function validateTableBelongsToProject($attribute): void
    {
        if (empty($this->$attribute)) {
            return;
        }

        $table = DbTable::findOne([
            'id' => (int)$this->$attribute,
            'user_id' => (int)$this->user_id,
        ]);
        if (ProjectSchema::supportsProjectContext() && $this->hasAttribute('project_id') && DbTable::getTableSchema() && isset(DbTable::getTableSchema()->columns['project_id'])) {
            $table = DbTable::findOne([
                'id' => (int)$this->$attribute,
                'user_id' => (int)$this->user_id,
                'project_id' => (int)$this->project_id,
            ]);
        }

        if ($table === null) {
            $this->addError($attribute, 'Selected table is not available in the active project.');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'project_id' => 'Project',
            'table_id' => 'Database Table',
            'db_table_id' => 'Database Table (New)',
            'storage_type' => 'Storage Type',
            'insert_to_table' => 'Insert To Database Table',
            'name' => 'Form Name',
            'schema_js' => 'Form Schema',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }

    public function getTable()
    {
        return $this->hasOne(DbTable::class, ['id' => 'table_id']);
    }

    public function getSubmissions()
    {
        return $this->hasMany(FormSubmission::class, ['form_id' => 'id']);
    }

    public function getPublishedForms()
    {
        return $this->hasMany(PublishedForm::class, ['form_id' => 'id']);
    }

    public function getSchema()
    {
        $schemaColumn = self::getSchemaStorageColumn();
        $schemaJs = $this->hasAttribute($schemaColumn) ? (string)$this->getAttribute($schemaColumn) : '';
        $schemaData = json_decode($schemaJs, true) ?: [];
        
        // If schema has 'pages' structure, extract blocks from all pages
        if (isset($schemaData['pages']) && is_array($schemaData['pages'])) {
            $allBlocks = [];
            foreach ($schemaData['pages'] as $page) {
                if (isset($page['blocks']) && is_array($page['blocks'])) {
                    $allBlocks = array_merge($allBlocks, $page['blocks']);
                }
            }
            return $allBlocks;
        }
        
        // If schema has 'blocks' key directly
        if (isset($schemaData['blocks']) && is_array($schemaData['blocks'])) {
            return $schemaData['blocks'];
        }
        
        // Old format: just an array of blocks
        return is_array($schemaData) ? $schemaData : [];
    }

    public function setSchema($schema)
    {
        $this->schema_js = json_encode($schema, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Preferred alias:
     * - if table has schema_js, use it
     * - if table has legacy schema_json, map schema_js access to it
     */
    public function getSchema_js()
    {
        if ($this->hasAttribute('schema_js')) {
            return $this->getAttribute('schema_js');
        }

        if ($this->hasAttribute('schema_json')) {
            return $this->getAttribute('schema_json');
        }

        return null;
    }

    public function setSchema_js($value)
    {
        if ($this->hasAttribute('schema_js')) {
            $this->setAttribute('schema_js', $value);
            return;
        }

        if ($this->hasAttribute('schema_json')) {
            $this->setAttribute('schema_json', $value);
        }
    }

    /**
     * Backward-compatible alias for older code paths.
     */
    public function getSchema_json()
    {
        return $this->schema_js;
    }

    public function setSchema_json($value)
    {
        $this->schema_js = $value;
    }

    public function behaviors()
    {
        $timestampExpression = $this->db->driverName === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';

        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new \yii\db\Expression($timestampExpression),
            ],
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->syncStorageCompatibility();
        return true;
    }

    public function beforeSave($insert)
    {
        $this->syncStorageCompatibility();
        return parent::beforeSave($insert);
    }

    public function getEffectiveTableId(): ?int
    {
        $newTableId = $this->hasAttribute('db_table_id') ? (int)$this->getAttribute('db_table_id') : 0;
        if ($newTableId > 0) {
            return $newTableId;
        }

        $legacyTableId = (int)$this->table_id;
        return $legacyTableId > 0 ? $legacyTableId : null;
    }

    public function shouldInsertToTargetTable(): bool
    {
        if ($this->hasAttribute('insert_to_table')) {
            return (int)$this->getAttribute('insert_to_table') === 1;
        }

        return $this->storage_type === 'database';
    }

    private function syncStorageCompatibility(): void
    {
        $legacyTableId = (int)$this->table_id;
        $newTableId = $this->hasAttribute('db_table_id') ? (int)$this->getAttribute('db_table_id') : 0;

        if ($this->hasAttribute('db_table_id')) {
            if ($newTableId > 0) {
                $this->table_id = $newTableId;
            } elseif ($legacyTableId > 0) {
                $this->setAttribute('db_table_id', $legacyTableId);
            }
        }

        $insertToTable = $this->storage_type === 'database';
        if ($this->hasAttribute('insert_to_table')) {
            $rawInsert = $this->getAttribute('insert_to_table');
            if ($rawInsert !== null && $rawInsert !== '') {
                $insertToTable = (int)$rawInsert === 1 || $rawInsert === true || $rawInsert === '1';
            }
            $this->setAttribute('insert_to_table', $insertToTable ? 1 : 0);
        }
        $this->storage_type = $insertToTable ? 'database' : 'json';
    }
}
