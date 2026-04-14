<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Form model - stores form definitions
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $table_id
 * @property string $storage_type
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
    public static function tableName()
    {
        return 'forms';
    }

    public function rules()
    {
        return [
            [['user_id', 'name', 'schema_js'], 'required'],
            [['user_id', 'table_id'], 'integer'],
            [['storage_type'], 'string', 'max' => 20],
            [['schema_json', 'schema_js'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['storage_type'], 'in', 'range' => ['json', 'database']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['table_id'], 'exist', 'skipOnError' => true, 'targetClass' => DbTable::class, 'targetAttribute' => ['table_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'table_id' => 'Database Table',
            'storage_type' => 'Storage Type',
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

    public function getTable()
    {
        return $this->hasOne(DbTable::class, ['id' => 'table_id']);
    }

    public function getSubmissions()
    {
        return $this->hasMany(FormSubmission::class, ['form_id' => 'id']);
    }

    public function getSchema()
    {
        return json_decode((string)$this->schema_js, true) ?: [];
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
}
