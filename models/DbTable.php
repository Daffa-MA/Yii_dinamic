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

        if ($this->hasAttribute('is_system')) {
            $rules[] = [['is_system'], 'boolean'];
        }
        if ($this->hasAttribute('is_visible_in_builder')) {
            $rules[] = [['is_visible_in_builder'], 'boolean'];
        }

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
            'table_status' => 'Table Status',
            'last_error_message' => 'Last Error Message',
            'is_system' => 'System Table',
            'is_visible_in_builder' => 'Visible in Table Builder',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * PERBAIKAN: Identifikasi tabel sistem secara dinamis tanpa hardcoding yang kaku.
     * Tabel sistem adalah tabel yang menunjang sistem / bawaan saat project dibuat.
     */
    public static function isSystemTable(string $tableName): bool
    {
        $name = strtolower(trim($tableName));
        if ($name === '') {
            return false;
        }

        // Framework / Library Patterns (Yii2, Gii, Auth, Debug, Migration, RBAC, Cache, Queue)
        $systemPatterns = [
            '/^yii_/',
            '/^gii_/',
            '/^auth_/',
            '/^migration/',
            '/^debug_/',
            '/^rbac_/',
            '/^cache_/',
            '/^queue_/',
            '/^session_/',
            '/^audit_/',
            '/^log_/',
            '/^oauth_/',
        ];

        foreach ($systemPatterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }

        // Core Meta-Tables and Internal Application Tables (Bawaan Sistem)
        $systemTables = [
            // Metadata & Config
            'db_tables',
            'db_table_columns',
            'system_settings',
            'settings',
            'internal_metadata',
            'internal_metadata_columns',
            'table_metadata',
            
            // App Builders & Masters
            'master_form',
            'master_form_fields',
            'master_form_layouts',
            'master_form_activity_log',
            'master_datatable',
            'master_menu',
            'master_page',
            'master_page_form',
            'master_page_chart',
            'master_report',
            'master_dashboard',
            'master_api',
            'master_automation',
            'forms', // Bawaan database_setup.sql
            'page_forms',
            
            // Auth, Users & Projects
            'user',
            'users', // Bawaan database_setup.sql
            'project',
            'projects',
            'project_user',
            'published_forms',
            'roles',
            'permissions',
            'role_permissions',
            'user_roles',
            'role_access',
            'auth_assignment',
            'auth_item',
            'auth_item_child',
            'auth_rule',
            
            // Operations & Features
            'notification',
            'notifications', // Bawaan database_setup.sql
            'form_submissions', // Bawaan database_setup.sql
            'form_responses',
            'submissions',
            'audit_logs',
            'audit',
            'failed_jobs',
            'migrations',
            'cache',
            'queue',
            'session',
            'logs',
            'log',
            'workspace_settings',
            
            // Internal Database (e.g. SQLite)
            'sqlite_sequence',
            'sqlite_master',
            'sqlite_stat1',
        ];

        if (in_array($name, $systemTables, true)) {
            return true;
        }

        // Cek metadata jika tabel terdaftar di db_tables dan ditandai sebagai sistem
        try {
            $meta = self::find()->where(['name' => $tableName])->asArray()->one();
            if ($meta !== null && !empty($meta['is_system'])) {
                return true;
            }
        } catch (\Throwable $e) {
            // Silently ignore if table doesn't exist in metadata
        }

        return false;
    }

    /**
     * PERBAIKAN: Ambil hanya tabel pengguna untuk dropdown global (Source of Truth).
     */
    public static function getUserTables(?int $userId = null, ?int $projectId = null): array
    {
        return \app\services\TableService::getUserTables($userId, $projectId);
    }

    /**
     * PERBAIKAN: Ambil tabel sistem secara dinamis (Source of Truth).
     */
    public static function getSystemTables(?int $userId = null, ?int $projectId = null): array
    {
        return \app\services\TableService::getSystemTables($userId, $projectId);
    }

    /**
     * PERBAIKAN: Ambil semua tabel (User + System) (Source of Truth).
     */
    public static function getAllTables(?int $userId = null, ?int $projectId = null): array
    {
        return \app\services\TableService::getAllTables($userId, $projectId);
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
