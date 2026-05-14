<?php

namespace app\models;

use app\components\ActiveProjectContext;
use app\components\ActiveDatabaseContext;
use app\components\DatabaseSchemaInitializer;
use app\components\ProjectSchema;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "master_form".
 *
 * @property int $id
 * @property int|null $page_id
 * @property int|null $project_id
 * @property int|null $table_id
 * @property string $form_name
 * @property string|null $form_type
 * @property string|null $database_context
 * @property int|null $custom_code_mode
 * @property array $form_data
 * @property string $slug
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property MasterPage $page
 * @property DbTable $table
 */
class MasterForm extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_form';
    }

    public static function getDb()
    {
        return Yii::$app->get('db', false) ?: parent::getDb();
    }

    private static function ensureActiveDatabaseContext(): void
    {
        if (!Yii::$app->has('db', false)) {
            return;
        }

        (new ActiveDatabaseContext())->resolveAndApply();
        DatabaseSchemaInitializer::ensureMasterFormStructure(Yii::$app->db);
        Yii::$app->db->schema->refresh();
    }

    public static function findScoped(): ActiveQuery
    {
        self::ensureActiveDatabaseContext();
        return self::applyActiveProjectScope(self::find());
    }

    public static function findByIdScoped($id): ?self
    {
        return self::findScoped()
            ->where(['id' => (int)$id])
            ->one();
    }

    private static function applyActiveProjectScope(ActiveQuery $query): ActiveQuery
    {
        $schema = Yii::$app->db->getSchema()->getTableSchema(self::tableName(), true);

        if (ProjectSchema::supportsProjectContext() && $schema !== null && isset($schema->columns['project_id'])) {
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            if ($activeProjectId !== null) {
                $query->andWhere(['project_id' => (int)$activeProjectId]);
            }
        }

        return $query;
    }

    public function rules()
    {
        return [
            [['form_name', 'form_data'], 'required'],
            [['form_data'], 'safe'],
            [['form_name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 100],
            [['form_type', 'database_context'], 'string', 'max' => 100],
            [['page_id', 'table_id', 'project_id', 'custom_code_mode', 'is_active'], 'integer', 'skipOnEmpty' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Page',
            'project_id' => 'Project',
            'table_id' => 'Target Table',
            'form_name' => 'Form Name',
            'form_type' => 'Form Type',
            'database_context' => 'Database Context',
            'custom_code_mode' => 'Custom Code Mode',
            'form_data' => 'Form Data',
            'slug' => 'Slug',
            'is_active' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->is_active = $this->is_active ?? 1;
        }
        $this->updated_at = date('Y-m-d H:i:s');

        if (empty($this->database_context) && Yii::$app->has('db', false)) {
            $this->database_context = (string)preg_replace('/^.*dbname=([^;]+).*$/i', '$1', Yii::$app->db->dsn);
        }
        if (empty($this->form_type)) {
            $this->form_type = 'dynamic';
        }
        if ($this->custom_code_mode === null) {
            $this->custom_code_mode = 0;
        }
        
        // Auto-generate slug if not provided
        if (empty($this->slug) && !empty($this->form_name)) {
            $this->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $this->form_name)));
        }
        
        return parent::beforeSave($insert);
    }

    public function getPage()
    {
        return $this->hasOne(MasterPage::class, ['id' => 'page_id']);
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }
    
    public function getTable()
    {
        return $this->hasOne(DbTable::class, ['id' => 'table_id']);
    }
    
    public function getTableName()
    {
        return $this->table ? $this->table->name : null;
    }

    public function getFields()
    {
        return $this->hasMany(MasterFormField::class, ['form_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getLayouts()
    {
        return $this->hasMany(MasterFormLayout::class, ['form_id' => 'id'])->orderBy(['is_default' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getActiveLayout()
    {
        return $this->hasOne(MasterFormLayout::class, ['form_id' => 'id'])->andOnCondition(['is_default' => 1]);
    }

    public function isActive()
    {
        return $this->is_active == 1;
    }

    public function toggleStatus()
    {
        $this->is_active = $this->is_active == 1 ? 0 : 1;
        return $this->save(false);
    }

    public static function getActiveForms()
    {
        $query = self::findScoped()
            ->where(['is_active' => 1])
            ->orderBy(['form_name' => SORT_ASC]);

        return $query->all();
    }

    public static function getFormOptions()
    {
        return ArrayHelper::map(self::getActiveForms(), 'id', 'form_name');
    }

    public static function findBySlug($slug)
    {
        $query = self::findScoped()
            ->where(['slug' => $slug, 'is_active' => 1]);

        return $query->one();
    }

    public function getFormDataArray(): array
    {
        $formData = $this->form_data;
        if (is_string($formData)) {
            $decoded = json_decode($formData, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($formData) ? $formData : [];
    }
}