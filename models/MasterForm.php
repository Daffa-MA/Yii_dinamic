<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "master_form".
 *
 * @property int $id
 * @property int|null $page_id
 * @property string $form_name
 * @property array $form_data
 * @property string $slug
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property MasterPage $page
 */
class MasterForm extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_form';
    }

    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

public function rules()
    {
        return [
            [['form_name', 'form_data'], 'required'],
            [['form_data'], 'safe'],
            [['form_name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 100],
            [['page_id'], 'integer', 'skipOnEmpty' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Page',
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
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['form_name' => SORT_ASC])
            ->all();
    }

    public static function getFormOptions()
    {
        return ArrayHelper::map(self::getActiveForms(), 'id', 'form_name');
    }

    public static function findBySlug($slug)
    {
        return self::findOne(['slug' => $slug, 'is_active' => 1]);
    }
}