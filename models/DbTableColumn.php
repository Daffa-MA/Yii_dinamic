<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * DbTableColumn model - stores column definitions
 */
class DbTableColumn extends ActiveRecord
{
    // MySQL column types
    public static $columnTypes = [
        'VARCHAR' => 'Text (Variable Length)',
        'CHAR' => 'Text (Fixed Length)',
        'TEXT' => 'Long Text',
        'INT' => 'Integer',
        'BIGINT' => 'Big Integer',
        'TINYINT' => 'Tiny Integer (0-1)',
        'DECIMAL' => 'Decimal Number',
        'FLOAT' => 'Float Number',
        'DATE' => 'Date',
        'DATETIME' => 'Date & Time',
        'TIMESTAMP' => 'Timestamp',
        'TIME' => 'Time',
        'BOOLEAN' => 'Boolean (TINYINT)',
        'JSON' => 'JSON',
        'BLOB' => 'Binary Data',
    ];

    public static function tableName()
    {
        return 'db_table_columns';
    }

    public function rules()
    {
        return [
            [['table_id', 'name', 'label', 'type'], 'required'],
            [['table_id', 'length', 'sort_order'], 'integer'],
            [['is_nullable', 'is_primary', 'is_unique'], 'boolean'],
            [['default_value'], 'string', 'max' => 255],
            [['comment'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['label'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['type'], 'in', 'range' => array_keys(self::$columnTypes)],
            [['table_id'], 'exist', 'skipOnError' => true, 'targetClass' => DbTable::class, 'targetAttribute' => ['table_id' => 'id']],
            // Validate length based on column type
            [['length'], 'validateLengthByType'],
            // PRIMARY KEY columns cannot be NULL
            [['is_nullable'], 'validatePrimaryKeyNullable'],
        ];
    }

    /**
     * Validate that length is appropriate for the column type
     */
    public function validateLengthByType($attribute, $params)
    {
        if (!$this->length) {
            return; // Length is optional
        }

        $maxLengths = [
            'VARCHAR' => 65535,
            'CHAR' => 255,
            'INT' => 11,
            'BIGINT' => 20,
            'TINYINT' => 4,
            'DECIMAL' => 65, // MySQL max precision
            'FLOAT' => 24,
        ];

        if (isset($maxLengths[$this->type]) && $this->length > $maxLengths[$this->type]) {
            $this->addError($attribute, "{$this->type} column cannot exceed {$maxLengths[$this->type]} characters.");
        }
    }

    /**
     * Validate that PRIMARY KEY columns are not nullable
     */
    public function validatePrimaryKeyNullable($attribute, $params)
    {
        if ($this->is_primary && $this->is_nullable) {
            $this->addError($attribute, 'PRIMARY KEY columns cannot be nullable (NULL).');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'table_id' => 'Table ID',
            'name' => 'Column Name',
            'label' => 'Display Label',
            'type' => 'Data Type',
            'length' => 'Length/Precision',
            'is_nullable' => 'Allow NULL',
            'is_primary' => 'Primary Key',
            'is_unique' => 'Unique',
            'default_value' => 'Default Value',
            'comment' => 'Comment',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created At',
        ];
    }

    public function getTable()
    {
        return $this->hasOne(DbTable::class, ['id' => 'table_id']);
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = strtolower(trim($this->name));
            if ($insert && $this->sort_order === null) {
                $maxOrder = static::find()->where(['table_id' => $this->table_id])->max('sort_order');
                $this->sort_order = ($maxOrder ?? 0) + 1;
            }
            return true;
        }
        return false;
    }

    public function getMySQLType()
    {
        switch ($this->type) {
            case 'VARCHAR':
                $len = $this->length ?: 255;
                $len = min($len, 65535); // MySQL VARCHAR max
                return "VARCHAR($len)";
            case 'CHAR':
                $len = $this->length ?: 255;
                $len = min($len, 255); // MySQL CHAR max
                return "CHAR($len)";
            case 'TEXT':
            case 'BLOB':
            case 'JSON':
                return $this->type;
            case 'INT':
            case 'BIGINT':
            case 'TINYINT':
            case 'BOOLEAN':
                return $this->type . ($this->length ? "({$this->length})" : '');
            case 'DECIMAL':
                // DECIMAL(precision, scale) - max precision is 65
                // If length provided, treat it as precision, otherwise use 10
                $precision = $this->length ?: 10;
                $precision = min($precision, 65); // Limit to MySQL max
                $scale = 2; // Default to 2 decimal places
                return "DECIMAL($precision,$scale)";
            case 'FLOAT':
                // FLOAT doesn't use length in modern MySQL
                return "FLOAT";
            case 'DATE':
            case 'DATETIME':
            case 'TIMESTAMP':
            case 'TIME':
                return $this->type;
            default:
                return $this->type;
        }
    }
}
