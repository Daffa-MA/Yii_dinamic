<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class MasterFormActivityLog extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_form_activity_log';
    }

    public function rules()
    {
        return [
            [['form_id', 'event_type', 'status'], 'required'],
            [['form_id', 'project_id'], 'integer'],
            [['message', 'meta_json'], 'string'],
            [['database_context', 'event_type', 'status'], 'string', 'max' => 100],
            [['created_at'], 'safe'],
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert && empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }
}

