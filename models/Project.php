<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Project model.
 */
class Project extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    public static function tableName()
    {
        return 'projects';
    }

    public function formName()
    {
        return 'Project';
    }

    public function rules()
    {
        return [
            [['user_id', 'name'], 'required'],
            [['user_id'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 150],
            [['name'], 'trim'],
            [['name'], 'filter', 'filter' => static function ($value) {
                return $value === '' ? null : $value;
            }],
            [['name'], 'unique', 'targetAttribute' => ['user_id', 'name'], 'message' => 'Project name already exists.'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Project Name',
            'description' => 'Description',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getForms()
    {
        return $this->hasMany(Form::class, ['project_id' => 'id']);
    }

    public function getDbTables()
    {
        return $this->hasMany(DbTable::class, ['project_id' => 'id']);
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
}
