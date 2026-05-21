<?php

namespace app\models;

use app\components\ActiveDatabaseContext;
use app\components\DatabaseSchemaInitializer;
use Yii;
use yii\db\ActiveRecord;

class MasterFormField extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_form_fields';
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
        if (DatabaseSchemaInitializer::ensureMasterFormStructure(Yii::$app->db)) {
            Yii::$app->db->schema->refresh();
        }
    }

    public function rules()
    {
        return [
            [['form_id', 'field_key', 'field_label', 'field_type'], 'required'],
            [['form_id', 'sort_order', 'is_required'], 'integer'],
            [['field_config', 'field_settings', 'validation_rules'], 'safe'],
            [['field_key', 'field_name', 'field_label', 'field_type', 'component_type', 'dropdown_source', 'foreign_key_table', 'foreign_key_column'], 'string', 'max' => 255],
            [['placeholder', 'default_value'], 'string', 'max' => 500],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'form_id' => 'Form',
            'field_key' => 'Field Key',
            'field_name' => 'Field Name',
            'field_label' => 'Field Label',
            'field_type' => 'Field Type',
            'component_type' => 'Component Type',
            'is_required' => 'Required',
            'placeholder' => 'Placeholder',
            'default_value' => 'Default Value',
            'dropdown_source' => 'Dropdown Source',
            'foreign_key_table' => 'Foreign Key Table',
            'foreign_key_column' => 'Foreign Key Column',
            'validation_rules' => 'Validation Rules',
            'field_config' => 'Field Config',
            'field_settings' => 'Field Settings',
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
