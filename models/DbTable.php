<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * DbTable model - stores database table definitions
 */
class DbTable extends ActiveRecord
{
    public static function tableName()
    {
        return 'db_tables';
    }

    public function rules()
    {
        return [
            [['user_id', 'name', 'label'], 'required'],
            [['user_id'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['label'], 'string', 'max' => 255],
            [['engine'], 'string', 'max' => 20],
            [['charset'], 'string', 'max' => 20],
            [['collation'], 'string', 'max' => 50],
            [['name'], 'unique', 'targetAttribute' => ['user_id', 'name'], 'message' => 'You already have a table with this name.'],
            [['name'], 'match', 'pattern' => '/^[a-z][a-z0-9_]*$/', 'message' => 'Table name must start with a letter and contain only lowercase letters, numbers, and underscores.'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
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

    public function getColumns()
    {
        return $this->hasMany(DbTableColumn::class, ['table_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new \yii\db\Expression('NOW()'),
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
