<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $page_id
 * @property int $form_id
 * @property int $sort_order
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property MasterPage $page
 * @property Form $form
 */
class MasterPageForm extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_page_form';
    }

    public function rules()
    {
        return [
            [['page_id', 'form_id'], 'required'],
            [['page_id', 'form_id', 'sort_order', 'is_active'], 'integer'],
            [['page_id', 'form_id'], 'unique', 'targetAttribute' => ['page_id', 'form_id']],
            [['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => MasterPage::class, 'targetAttribute' => ['page_id' => 'id']],
            [['form_id'], 'exist', 'skipOnError' => true, 'targetClass' => Form::class, 'targetAttribute' => ['form_id' => 'id']],
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->is_active = $this->is_active ?? 1;
            $this->sort_order = $this->sort_order ?? 0;
        }

        $this->updated_at = date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }

    public function getPage()
    {
        return $this->hasOne(MasterPage::class, ['id' => 'page_id']);
    }

    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }
}
