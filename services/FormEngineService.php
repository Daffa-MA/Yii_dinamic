<?php

namespace app\services;

use app\models\MasterForm;
use app\models\DbTableColumn;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
use app\helpers\FormSystemFieldHelper;
use app\components\SystemFieldService;
use yii\helpers\Json;

class FormEngineService
{
    public function getResolvedFormSchema(MasterForm $form): array
    {
        $fields = $form->getFields()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $layout = $form->getActiveLayout()->one();
        $autoSynced = false;

        if (empty($fields)) {
            $this->syncLegacyToRelational($form);
            $fields = $form->getFields()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
            $layout = $form->getActiveLayout()->one();
            $autoSynced = true;
        }

        $fieldRows = [];
        foreach ($fields as $field) {
            $fieldName = $field->field_name ?: $field->field_key;
            $settings = $this->decodeJson($field->field_settings);
            if ($this->isSystemFieldForForm(array_merge($settings, ['name' => $fieldName]), $form)) {
                $field->delete();
                $autoSynced = true;
                continue;
            }

            $fieldRows[] = array_merge($settings, [
                'id' => $field->id,
                'name' => $fieldName,
                'label' => $field->field_label,
                'type' => $field->field_type,
                'required' => (bool)$field->is_required,
                'placeholder' => $field->placeholder,
                'default_value' => $field->default_value,
                'is_foreign_key' => !empty($field->foreign_key_table),
                'fk_referenced_table' => $field->foreign_key_table,
                'fk_display_column' => $field->foreign_key_column,
            ]);
        }

        return [
            'fields' => $fieldRows,
            'layout' => $layout,
            'autoSynced' => $autoSynced,
        ];
    }

    public function syncLegacyToRelational(MasterForm $form): void
    {
        $formData = FormSystemFieldHelper::filterBuilderData($form->getFormDataArray());
        $fields = isset($formData['fields']) && is_array($formData['fields']) ? $formData['fields'] : (is_array($formData) ? $formData : []);
        $fields = array_values(array_filter($fields, fn($field) => is_array($field) && !$this->isSystemFieldForForm($field, $form)));
        if (empty($fields)) {
            return;
        }

        MasterFormField::deleteAll(['form_id' => $form->id]);
        MasterFormLayout::deleteAll(['form_id' => $form->id]);

        foreach ($fields as $index => $fieldData) {
            if (!is_array($fieldData)) {
                continue;
            }

            $fieldName = trim((string)($fieldData['name'] ?? $fieldData['field_name'] ?? ''));
            if ($fieldName === '') {
                $fieldName = 'field_' . ($index + 1);
            }
            if ($this->isSystemFieldForForm($fieldData, $form)) {
                continue;
            }

            $fieldType = (string)($fieldData['type'] ?? $fieldData['field_type'] ?? 'text');

            $field = new MasterFormField();
            $field->form_id = (int)$form->id;
            $field->field_key = $fieldName;
            $field->field_name = $fieldName;
            $field->field_label = (string)($fieldData['label'] ?? ucfirst(str_replace('_', ' ', $fieldName)));
            $field->field_type = $fieldType;
            $field->component_type = (string)($fieldData['component_type'] ?? $fieldType);
            $field->is_required = !empty($fieldData['required']) ? 1 : 0;
            $field->placeholder = (string)($fieldData['placeholder'] ?? '');
            $field->default_value = isset($fieldData['default_value']) ? (string)$fieldData['default_value'] : null;
            $field->dropdown_source = (string)($fieldData['dropdown_source'] ?? '');
            $field->foreign_key_table = isset($fieldData['fk_referenced_table']) ? (string)$fieldData['fk_referenced_table'] : null;
            $field->foreign_key_column = isset($fieldData['fk_display_column']) ? (string)$fieldData['fk_display_column'] : null;
            $field->validation_rules = Json::encode(['required' => !empty($fieldData['required'])]);
            $field->field_config = Json::encode($fieldData);
            $field->field_settings = Json::encode($fieldData);
            $field->sort_order = (int)$index;
            $field->save(false);
        }

        $layout = new MasterFormLayout();
        $layout->form_id = (int)$form->id;
        $layout->layout_name = $form->form_name . ' Layout';
        $layout->layout_type = (string)($form->form_type ?: 'builder');
        $layout->layout_json = Json::encode(['builder' => $formData]);
        $layout->custom_html = '';
        $layout->custom_css = '';
        $layout->custom_js = '';
        if ($layout->hasAttribute('use_custom_code')) {
            $layout->use_custom_code = 0;
        }
        $layout->builder_state = Json::encode($formData);
        $layout->is_default = 1;
        $layout->sort_order = 0;
        $layout->save(false);

        $form->custom_code_mode = 0;
        $form->save(false, ['custom_code_mode']);
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = Json::decode($value);
        return is_array($decoded) ? $decoded : [];
    }

    private function isSystemFieldForForm(array $fieldData, MasterForm $form): bool
    {
        if (FormSystemFieldHelper::isSystemFieldData($fieldData)) {
            return true;
        }

        $sourceColumnId = (int)($fieldData['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = DbTableColumn::findOne($sourceColumnId);
            if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                return true;
            }
        }

        if (!empty($form->table_id)) {
            $fieldName = $fieldData['name'] ?? $fieldData['field_name'] ?? $fieldData['field_key'] ?? '';
            if ($fieldName !== '') {
                $sourceColumn = DbTableColumn::find()
                    ->where(['table_id' => (int)$form->table_id, 'name' => (string)$fieldName])
                    ->one();
                if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                    return true;
                }
            }
        }

        return false;
    }
}

