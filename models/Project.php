<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Project model.
 * 
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 */
class Project extends ActiveRecord
{
    public $custom_domain_prefix;

    public static function ensureProjectStructure(): void
    {
        $db = Yii::$app->get('metadataDb', false) ?: parent::getDb();
        $schema = $db->schema->getTableSchema(static::tableName(), true);
        if ($schema === null) {
            return;
        }

        $columns = [
            'slug' => $db->schema->createColumnSchemaBuilder('string', 190)->null(),
            'custom_domain' => $db->schema->createColumnSchemaBuilder('string', 190)->null(),
            'domain_status' => $db->schema->createColumnSchemaBuilder('string', 20)->null(),
            'domain_verified_at' => $db->schema->createColumnSchemaBuilder('datetime')->null(),
        ];

        foreach ($columns as $columnName => $columnSchema) {
            if (!isset($schema->columns[$columnName])) {
                $db->createCommand()->addColumn(static::tableName(), $columnName, $columnSchema)->execute();
                $db->schema->refreshTableSchema(static::tableName());
                $schema = $db->schema->getTableSchema(static::tableName(), true);
            }
        }
    }

    public static function normalizeCustomDomain(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $value = strtolower($value);
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;
        $value = trim($value);
        $value = trim($value, '.');

        return $value === '' ? null : $value;
    }

    public static function normalizeDomainPrefix(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $value = strtolower($value);
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;
        $suffix = self::getProjectDomainSuffix();
        if ($suffix !== '' && $value === $suffix) {
            return '';
        }

        if ($suffix !== '' && substr($value, -strlen('.' . $suffix)) === '.' . $suffix) {
            $value = substr($value, 0, -strlen('.' . $suffix));
        }

        $value = trim($value, '.');
        $value = preg_replace('/[^a-z0-9.-]+/i', '-', $value) ?? $value;
        $value = preg_replace('/-+/', '-', $value) ?? $value;
        $value = preg_replace('/\.+/', '.', $value) ?? $value;
        $value = trim($value, '.-');

        if ($value === '') {
            return '';
        }

        $parts = explode('.', $value);
        $firstLabel = trim((string)array_shift($parts), '-');
        $firstLabel = preg_replace('/[^a-z0-9-]+/i', '-', $firstLabel) ?? $firstLabel;
        $firstLabel = preg_replace('/-+/', '-', $firstLabel) ?? $firstLabel;
        $firstLabel = trim($firstLabel, '-');

        if ($firstLabel === '') {
            return '';
        }

        if (preg_match('/^[0-9]/', $firstLabel) === 1) {
            $firstLabel = 'project-' . $firstLabel;
        }

        return strlen($firstLabel) > 63 ? substr($firstLabel, 0, 63) : $firstLabel;
    }

    public static function normalizeSlug(?string $value): string
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
        $value = preg_replace('/-+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        if ($value === '') {
            $value = 'project';
        }

        if (preg_match('/^[0-9]/', $value) === 1) {
            $value = 'project-' . $value;
        }

        return $value;
    }

    public static function getProjectDomainSuffix(): string
    {
        $configured = getenv('APP_PROJECT_DOMAIN_SUFFIX');
        if ($configured === false || trim($configured) === '') {
            $configured = (string)(Yii::$app->params['projectDomainSuffix'] ?? 'appforge.web.id');
        }

        $suffix = trim((string)$configured);
        $suffix = trim($suffix, '.');
        return $suffix !== '' ? $suffix : 'appforge.web.id';
    }

    public static function buildProjectSlug(string $name, ?int $ignoreProjectId = null): string
    {
        $baseSlug = self::normalizeSlug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (self::slugExists($slug, $ignoreProjectId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public static function buildProjectDomainFromSlug(string $slug): string
    {
        $slug = self::normalizeSlug($slug);
        return $slug . '.' . self::getProjectDomainSuffix();
    }

    public static function buildProjectDomainFromPrefix(string $prefix): string
    {
        $prefix = self::normalizeDomainPrefix($prefix);
        if ($prefix === '') {
            $prefix = 'project';
        }

        return $prefix . '.' . self::getProjectDomainSuffix();
    }

    public static function extractProjectDomainPrefix(?string $domain): string
    {
        $domain = self::normalizeCustomDomain($domain) ?? '';
        if ($domain === '') {
            return '';
        }

        $suffix = self::getProjectDomainSuffix();
        if ($suffix !== '' && substr($domain, -strlen('.' . $suffix)) === '.' . $suffix) {
            return self::normalizeDomainPrefix(substr($domain, 0, -strlen('.' . $suffix)));
        }

        return self::normalizeDomainPrefix($domain);
    }

    private static function slugExists(string $slug, ?int $ignoreProjectId = null): bool
    {
        $query = static::find()->where(['slug' => $slug]);
        if ($ignoreProjectId !== null && $ignoreProjectId > 0) {
            $query->andWhere(['<>', 'id', $ignoreProjectId]);
        }

        return $query->exists();
    }

    public static function findByCustomDomain(string $host): ?self
    {
        self::ensureProjectStructure();

        $host = self::normalizeCustomDomain($host) ?? '';
        if ($host === '') {
            return null;
        }

        $project = static::find()
            ->where(['custom_domain' => $host])
            ->andWhere(['or',
                ['domain_status' => 'active'],
                ['domain_status' => null],
                ['domain_status' => ''],
            ])
            ->one();

        if ($project !== null) {
            return $project;
        }

        $hostPrefix = self::extractProjectDomainPrefix($host);
        if ($hostPrefix === '') {
            return null;
        }

        $projects = static::find()
            ->andWhere(['or',
                ['domain_status' => 'active'],
                ['domain_status' => null],
                ['domain_status' => ''],
            ])
            ->all();

        foreach ($projects as $candidate) {
            $candidatePrefix = self::extractProjectDomainPrefix((string)($candidate->custom_domain ?? ''));
            if ($candidatePrefix === '') {
                $candidatePrefix = self::normalizeSlug((string)($candidate->slug ?? ''));
            }

            if ($candidatePrefix !== '' && $candidatePrefix === $hostPrefix) {
                return $candidate;
            }
        }

        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        } else {
            $host = 'www.' . $host;
        }

        return static::find()
            ->where(['custom_domain' => $host])
            ->andWhere(['or',
                ['domain_status' => 'active'],
                ['domain_status' => null],
                ['domain_status' => ''],
            ])
            ->one();
    }

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
            [['slug'], 'string', 'max' => 190],
            [['custom_domain'], 'string', 'max' => 190],
            [['custom_domain_prefix'], 'string', 'max' => 63],
            [['domain_status'], 'string', 'max' => 20],
            [['domain_verified_at'], 'safe'],
            [['name'], 'string', 'max' => 150],
            [['name'], 'trim'],
            [['name'], 'filter', 'filter' => static function ($value) {
                return $value === '' ? null : $value;
            }],
            [['custom_domain'], 'filter', 'filter' => static function ($value) {
                return self::normalizeCustomDomain(is_string($value) ? $value : (string)$value);
            }],
            [['custom_domain_prefix'], 'filter', 'filter' => static function ($value) {
                return self::normalizeDomainPrefix(is_string($value) ? $value : (string)$value);
            }],
            [['slug'], 'filter', 'filter' => static function ($value) {
                return self::normalizeSlug(is_string($value) ? $value : (string)$value);
            }],
            [['domain_status'], 'filter', 'filter' => static function ($value) {
                $value = strtolower(trim((string)$value));
                return $value === '' ? null : $value;
            }],
            [['domain_status'], 'in', 'range' => ['active', 'pending', 'error', null, ''], 'skipOnEmpty' => true],
            [['name'], 'unique', 'targetAttribute' => ['user_id', 'name'], 'message' => 'Project name already exists.'],
            [['slug'], 'unique', 'message' => 'Slug already used by another project.', 'skipOnEmpty' => true],
            [['custom_domain'], 'unique', 'targetAttribute' => ['custom_domain'], 'message' => 'Domain already used by another project.', 'skipOnEmpty' => true],
            [['custom_domain_prefix'], 'validateCustomDomainPrefixUnique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Project Name',
            'slug' => 'Slug',
            'description' => 'Description',
            'custom_domain' => 'Custom Domain',
            'custom_domain_prefix' => 'Custom Domain Prefix',
            'domain_status' => 'Domain Status',
            'domain_verified_at' => 'Domain Verified At',
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

    public function validateCustomDomainPrefixUnique(string $attribute): void
    {
        $prefix = self::normalizeDomainPrefix((string)$this->$attribute);
        if ($prefix === '') {
            return;
        }

        $query = static::find()
            ->andWhere(['or',
                ['domain_status' => 'active'],
                ['domain_status' => null],
                ['domain_status' => ''],
            ]);

        if (!$this->getIsNewRecord() && (int)$this->id > 0) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }

        foreach ($query->all() as $project) {
            $candidatePrefix = self::extractProjectDomainPrefix((string)($project->custom_domain ?? ''));
            if ($candidatePrefix === '') {
                $candidatePrefix = self::normalizeSlug((string)($project->slug ?? ''));
            }

            if ($candidatePrefix === $prefix) {
                $this->addError($attribute, 'Domain prefix already used by another project.');
                return;
            }
        }

        if (static::find()->where(['custom_domain' => self::buildProjectDomainFromPrefix($prefix)])->andFilterWhere(['<>', 'id', (int)$this->id])->exists()) {
            $this->addError($attribute, 'Domain prefix already used by another project.');
        }
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
