<?php

namespace app\models;

use app\components\ProjectSchema;
use Yii;
use yii\db\ActiveRecord;

/**
 * DbTable model - stores database table definitions
 */
class DbTable extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    public const ALLOWED_ENGINES = ['InnoDB', 'MyISAM'];
    public const ALLOWED_CHARSETS = ['utf8mb4', 'utf8'];
    public const ALLOWED_COLLATIONS = ['utf8mb4_unicode_ci', 'utf8mb4_general_ci'];

    public static function tableName()
    {
        return 'db_tables';
    }

    public function rules()
    {
        $requiresProject = $this->hasAttribute('project_id') && ProjectSchema::supportsProjectContext();
        $requiredAttributes = ['user_id', 'name', 'label'];
        $integerAttributes = ['user_id'];
        if ($requiresProject) {
            $requiredAttributes[] = 'project_id';
            $integerAttributes[] = 'project_id';
        }

        $uniqueTarget = $requiresProject ? ['user_id', 'project_id', 'name'] : ['user_id', 'name'];
        $uniqueMessage = $requiresProject
            ? 'You already have a table with this name in this project.'
            : 'You already have a table with this name.';

        $rules = [
            [$requiredAttributes, 'required'],
            [$integerAttributes, 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['label'], 'string', 'max' => 255],
            [['engine'], 'string', 'max' => 20],
            [['charset'], 'string', 'max' => 20],
            [['collation'], 'string', 'max' => 50],
            [['engine'], 'in', 'range' => self::ALLOWED_ENGINES],
            [['charset'], 'in', 'range' => self::ALLOWED_CHARSETS],
            [['collation'], 'in', 'range' => self::ALLOWED_COLLATIONS],
            [['name'], 'unique', 'targetAttribute' => $uniqueTarget, 'message' => $uniqueMessage],
            [['name'], 'match', 'pattern' => '/^[a-z][a-z0-9_]*$/', 'message' => 'Table name must start with a letter and contain only lowercase letters, numbers, and underscores.'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];

        if ($requiresProject) {
            $rules[] = [['project_id'], 'exist', 'skipOnError' => true, 'targetClass' => Project::class, 'targetAttribute' => ['project_id' => 'id']];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'project_id' => 'Project',
            'name' => 'Table Name',
            'label' => 'Display Label',
            'description' => 'Description',
            'engine' => 'Storage Engine',
            'charset' => 'Character Set',
            'collation' => 'Collation',
            'is_created' => 'Created in Database',
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

    public function getColumns()
    {
        return $this->hasMany(DbTableColumn::class, ['table_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = strtolower(trim($this->name));
            return true;
        }
        return false;
    }
}
