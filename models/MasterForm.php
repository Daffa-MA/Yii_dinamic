<?php

namespace app\models;

use app\components\ActiveProjectContext;
use app\components\ProjectSchema;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "master_form".
 *
 * @property int $id
 * @property int|null $page_id
 * @property int|null $project_id
 * @property int|null $table_id
 * @property string $form_name
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

    public function rules()
    {
        return [
            [['form_name', 'form_data'], 'required'],
            [['form_data'], 'safe'],
            [['form_name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 100],
            [['page_id', 'table_id', 'project_id'], 'integer', 'skipOnEmpty' => true],
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
        $query = self::find()
            ->where(['is_active' => 1])
            ->orderBy(['form_name' => SORT_ASC]);

        if (ProjectSchema::supportsProjectContext() && self::getTableSchema() && isset(self::getTableSchema()->columns['project_id'])) {
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            if ($activeProjectId !== null) {
                $query->andWhere(['project_id' => $activeProjectId]);
            }
        }

        return $query->all();
    }

    public static function getFormOptions()
    {
        return ArrayHelper::map(self::getActiveForms(), 'id', 'form_name');
    }

    public static function findBySlug($slug)
    {
        $query = self::find()
            ->where(['slug' => $slug, 'is_active' => 1]);

        if (ProjectSchema::supportsProjectContext() && self::getTableSchema() && isset(self::getTableSchema()->columns['project_id'])) {
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            if ($activeProjectId !== null) {
                $query->andWhere(['project_id' => $activeProjectId]);
            }
        }

        return $query->one();
    }
}