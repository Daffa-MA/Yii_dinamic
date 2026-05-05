<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class PageForms extends ActiveRecord
{
    public static function tableName()
    {
        return 'page_forms';
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }

    public function rules()
    {
        return [
            [['page_id', 'form_id'], 'required'],
            [['page_id', 'form_id', 'order'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Halaman',
            'form_id' => 'Form',
            'order' => 'Urutan',
            'created_at' => 'Dibuat',
        ];
    }

    public function getPage()
    {
        return $this->hasOne(MasterPage::class, ['id' => 'page_id']);
    }

    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }
    
    public static function getFormsForPage($pageId)
    {
        return self::find()
            ->where(['page_id' => $pageId])
            ->orderBy(['order' => SORT_ASC])
            ->all();
    }
    
    public static function addFormToPage($pageId, $formId, $order = 0)
    {
        $existing = self::find()
            ->where(['page_id' => $pageId, 'form_id' => $formId])
            ->one();
            
        if ($existing) {
            return $existing;
        }
        
        $model = new self();
        $model->page_id = $pageId;
        $model->form_id = $formId;
        $model->order = $order;
        $model->save();
        
        return $model;
    }
    
    public static function removeFormFromPage($pageId, $formId)
    {
        return self::deleteAll(['page_id' => $pageId, 'form_id' => $formId]);
    }
}