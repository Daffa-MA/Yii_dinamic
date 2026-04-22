<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * DbTableColumn model - stores column definitions
 */
class DbTableColumn extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    public const FOREIGN_KEY_ACTIONS = ['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION'];

    // MySQL column types (phpMyAdmin order)
    public static $columnTypes = [
        'TINYINT' => 'TINYINT',
        'SMALLINT' => 'SMALLINT',
        'MEDIUMINT' => 'MEDIUMINT',
        'INT' => 'INT',
        'BIGINT' => 'BIGINT',
        'DECIMAL' => 'DECIMAL',
        'FLOAT' => 'FLOAT',
        'DOUBLE' => 'DOUBLE',
        'REAL' => 'REAL',
        'BIT' => 'BIT',
        'BOOLEAN' => 'BOOLEAN',
        'SERIAL' => 'SERIAL',
        'DATE' => 'DATE',
        'DATETIME' => 'DATETIME',
        'TIMESTAMP' => 'TIMESTAMP',
        'TIME' => 'TIME',
        'YEAR' => 'YEAR',
        'CHAR' => 'CHAR',
        'VARCHAR' => 'VARCHAR',
        'TINYTEXT' => 'TINYTEXT',
        'TEXT' => 'TEXT',
        'MEDIUMTEXT' => 'MEDIUMTEXT',
        'LONGTEXT' => 'LONGTEXT',
        'BINARY' => 'BINARY',
        'VARBINARY' => 'VARBINARY',
        'TINYBLOB' => 'TINYBLOB',
        'BLOB' => 'BLOB',
        'MEDIUMBLOB' => 'MEDIUMBLOB',
        'LONGBLOB' => 'LONGBLOB',
        'ENUM' => 'ENUM',
        'SET' => 'SET',
        'GEOMETRY' => 'GEOMETRY',
        'POINT' => 'POINT',
        'LINESTRING' => 'LINESTRING',
        'POLYGON' => 'POLYGON',
        'MULTIPOINT' => 'MULTIPOINT',
        'MULTILINESTRING' => 'MULTILINESTRING',
        'MULTIPOLYGON' => 'MULTIPOLYGON',
        'GEOMETRYCOLLECTION' => 'GEOMETRYCOLLECTION',
        'JSON' => 'JSON',
    ];

    public static function getColumnTypeGroups(): array
    {
        return [
            'Numerik' => [
                'TINYINT',
                'SMALLINT',
                'MEDIUMINT',
                'INT',
                'BIGINT',
                '-',
                'DECIMAL',
                'FLOAT',
                'DOUBLE',
                'REAL',
                '-',
                'BIT',
                'BOOLEAN',
                'SERIAL',
            ],
            'Tanggal dan waktu' => [
                'DATE',
                'DATETIME',
                'TIMESTAMP',
                'TIME',
                'YEAR',
            ],
            'String' => [
                'CHAR',
                'VARCHAR',
                '-',
                'TINYTEXT',
                'TEXT',
                'MEDIUMTEXT',
                'LONGTEXT',
                '-',
                'BINARY',
                'VARBINARY',
                '-',
                'TINYBLOB',
                'BLOB',
                'MEDIUMBLOB',
                'LONGBLOB',
                '-',
                'ENUM',
                'SET',
            ],
            'Spasial' => [
                'GEOMETRY',
                'POINT',
                'LINESTRING',
                'POLYGON',
                'MULTIPOINT',
                'MULTILINESTRING',
                'MULTIPOLYGON',
                'GEOMETRYCOLLECTION',
            ],
            'JSON' => [
                'JSON',
            ],
        ];
    }

    public static function getDefaultLengthMap(): array
    {
        return [
            'TINYINT' => 4,
            'SMALLINT' => 6,
            'MEDIUMINT' => 9,
            'INT' => 11,
            'BIGINT' => 20,
            'BIT' => 1,
            'CHAR' => 1,
            'VARCHAR' => 255,
            'BINARY' => 1,
            'VARBINARY' => 255,
            'DECIMAL' => 10,
        ];
    }

    public static function tableName()
    {
        return 'db_table_columns';
    }

    public function rules()
    {
        $rules = [
            [['table_id', 'name', 'label', 'type'], 'required'],
            [['table_id', 'length', 'sort_order'], 'integer'],
            [['is_nullable', 'is_primary', 'is_unique', 'is_auto_increment'], 'boolean'],
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

        if ($this->hasAttribute('is_foreign_key')) {
            $rules[] = [['is_foreign_key'], 'boolean'];
            $rules[] = [['referenced_table_name', 'referenced_column_name'], 'string', 'max' => 100];
            $rules[] = [['on_delete_action', 'on_update_action'], 'string', 'max' => 20];
            $rules[] = [['is_foreign_key'], 'validateForeignKeyMetadata'];
        }

        if ($this->hasAttribute('enum_values')) {
            $rules[] = [['enum_values'], 'string'];
            $rules[] = [['enum_values'], 'validateEnumValues'];
        }

        return $rules;
    }

    public static function normalizeForeignKeyAction(?string $action): string
    {
        $normalized = strtoupper(trim((string)$action));
        return in_array($normalized, self::FOREIGN_KEY_ACTIONS, true) ? $normalized : 'RESTRICT';
    }

    public function validateForeignKeyMetadata($attribute, $params)
    {
        if (!$this->hasAttribute('is_foreign_key') || !(bool)$this->getAttribute('is_foreign_key')) {
            return;
        }

        $refTable = trim((string)$this->getAttribute('referenced_table_name'));
        $refColumn = trim((string)$this->getAttribute('referenced_column_name'));
        $identifierPattern = '/^[a-z][a-z0-9_]*$/';

        if ($refTable === '') {
            $this->addError('referenced_table_name', 'Referenced table name is required when Foreign Key is enabled.');
        } elseif (!preg_match($identifierPattern, strtolower($refTable))) {
            $this->addError('referenced_table_name', 'Referenced table name must start with a letter and contain only lowercase letters, numbers, and underscores.');
        }

        if ($refColumn === '') {
            $this->addError('referenced_column_name', 'Referenced column name is required when Foreign Key is enabled.');
        } elseif (!preg_match($identifierPattern, strtolower($refColumn))) {
            $this->addError('referenced_column_name', 'Referenced column name must start with a letter and contain only lowercase letters, numbers, and underscores.');
        }

        foreach (['on_delete_action', 'on_update_action'] as $actionAttribute) {
            $action = strtoupper(trim((string)$this->getAttribute($actionAttribute)));
            if ($action === '') {
                $action = 'RESTRICT';
            }
            if (!in_array($action, self::FOREIGN_KEY_ACTIONS, true)) {
                $this->addError(
                    $actionAttribute,
                    'Invalid action. Allowed values: ' . implode(', ', self::FOREIGN_KEY_ACTIONS) . '.'
                );
            }
        }
    }

    public function validateEnumValues($attribute, $params)
    {
        $type = strtoupper((string)$this->type);
        if (!in_array($type, ['ENUM', 'SET'], true)) {
            return;
        }

        $rawValues = trim((string)$this->getAttribute('enum_values'));
        if ($rawValues === '') {
            $this->addError($attribute, "{$type} columns require at least one value (comma-separated).");
        }
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
            'BINARY' => 255,
            'VARBINARY' => 65535,
            'BIT' => 64,
            'TINYINT' => 4,
            'SMALLINT' => 6,
            'MEDIUMINT' => 9,
            'INT' => 11,
            'BIGINT' => 20,
            'DECIMAL' => 65, // MySQL max precision
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
            'is_auto_increment' => 'Auto Increment',
            'is_foreign_key' => 'Foreign Key',
            'referenced_table_name' => 'Referenced Table',
            'referenced_column_name' => 'Referenced Column',
            'on_delete_action' => 'On Delete Action',
            'on_update_action' => 'On Update Action',
            'default_value' => 'Default Value',
            'comment' => 'Comment',
            'enum_values' => 'Enum/Set Values',
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
        $timestampExpression = $this->db->driverName === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';

        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => new \yii\db\Expression($timestampExpression),
            ],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = strtolower(trim($this->name));

            if ($this->hasAttribute('is_foreign_key')) {
                $isForeignKey = (bool)$this->getAttribute('is_foreign_key');
                if ($isForeignKey) {
                    $this->setAttribute('referenced_table_name', strtolower(trim((string)$this->getAttribute('referenced_table_name'))) ?: null);
                    $this->setAttribute('referenced_column_name', strtolower(trim((string)$this->getAttribute('referenced_column_name'))) ?: null);
                    $this->setAttribute('on_delete_action', self::normalizeForeignKeyAction((string)$this->getAttribute('on_delete_action')));
                    $this->setAttribute('on_update_action', self::normalizeForeignKeyAction((string)$this->getAttribute('on_update_action')));
                } else {
                    $this->setAttribute('referenced_table_name', null);
                    $this->setAttribute('referenced_column_name', null);
                    $this->setAttribute('on_delete_action', 'RESTRICT');
                    $this->setAttribute('on_update_action', 'RESTRICT');
                }
            }

            if ($this->hasAttribute('enum_values')) {
                $rawValues = trim((string)$this->getAttribute('enum_values'));
                $this->setAttribute('enum_values', $rawValues !== '' ? $rawValues : null);
            }

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
                $len = $this->length ?: 1;
                $len = min($len, 255); // MySQL CHAR max
                return "CHAR($len)";
            case 'BINARY':
                $len = $this->length ?: 1;
                $len = min($len, 255); // MySQL BINARY max
                return "BINARY($len)";
            case 'VARBINARY':
                $len = $this->length ?: 255;
                $len = min($len, 65535); // MySQL VARBINARY max
                return "VARBINARY($len)";
            case 'TINYTEXT':
            case 'TEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
            case 'TINYBLOB':
            case 'BLOB':
            case 'MEDIUMBLOB':
            case 'LONGBLOB':
            case 'JSON':
            case 'GEOMETRY':
            case 'POINT':
            case 'LINESTRING':
            case 'POLYGON':
            case 'MULTIPOINT':
            case 'MULTILINESTRING':
            case 'MULTIPOLYGON':
            case 'GEOMETRYCOLLECTION':
                return $this->type;
            case 'ENUM':
            case 'SET':
                return $this->type;
            case 'INT':
            case 'BIGINT':
            case 'TINYINT':
            case 'SMALLINT':
            case 'MEDIUMINT':
                return $this->type . ($this->length ? "({$this->length})" : '');
            case 'BIT':
                $len = $this->length ?: 1;
                $len = min(max($len, 1), 64);
                return "BIT($len)";
            case 'BOOLEAN':
                return 'BOOLEAN';
            case 'SERIAL':
                return 'SERIAL';
            case 'DECIMAL':
                // DECIMAL(precision, scale) - max precision is 65
                // If length provided, treat it as precision, otherwise use 10
                $precision = $this->length ?: 10;
                $precision = min($precision, 65); // Limit to MySQL max
                $scale = 2; // Default to 2 decimal places
                return "DECIMAL($precision,$scale)";
            case 'FLOAT':
            case 'DOUBLE':
            case 'REAL':
                return $this->type;
            case 'DATE':
            case 'DATETIME':
            case 'TIMESTAMP':
            case 'TIME':
            case 'YEAR':
                return $this->type;
            default:
                return $this->type;
        }
    }
}
