<?php

namespace app\models;

use app\components\ActiveDatabaseContext;
use app\components\DatabaseSchemaInitializer;
use Yii;
use yii\db\ActiveRecord;

class MasterFormLayout extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_form_layouts';
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

    public function rules()
    {
        return [
            [['form_id', 'layout_name'], 'required'],
            [['form_id', 'is_default', 'sort_order'], 'integer'],
            [['layout_json', 'custom_html', 'custom_css', 'custom_js', 'builder_state'], 'safe'],
            [['layout_name', 'layout_type'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'form_id' => 'Form',
            'layout_name' => 'Layout Name',
            'layout_type' => 'Layout Type',
            'layout_json' => 'Layout JSON',
            'custom_html' => 'Custom HTML',
            'custom_css' => 'Custom CSS',
            'custom_js' => 'Custom JS',
            'builder_state' => 'Builder State',
            'is_default' => 'Default Layout',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        $this->updated_at = date('Y-m-d H:i:s');

        return parent::beforeSave($insert);
    }

    public function getForm()
    {
        return $this->hasOne(MasterForm::class, ['id' => 'form_id']);
    }
}
