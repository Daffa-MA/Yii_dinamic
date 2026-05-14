<?php

namespace app\services;

use app\models\MasterForm;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
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
            $settings = $this->decodeJson($field->field_settings);
            $fieldRows[] = array_merge($settings, [
                'id' => $field->id,
                'name' => $field->field_name ?: $field->field_key,
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
        $formData = $form->getFormDataArray();
        $fields = isset($formData['fields']) && is_array($formData['fields']) ? $formData['fields'] : (is_array($formData) ? $formData : []);
        if (empty($fields)) {
            return;
        }

        MasterFormField::deleteAll(['form_id' => $form->id]);
        MasterFormLayout::deleteAll(['form_id' => $form->id]);

        $customHtml = [];
        $customCss = [];
        $customJs = [];
        $customCodeMode = 0;

        foreach ($fields as $index => $fieldData) {
            if (!is_array($fieldData)) {
                continue;
            }

            $fieldName = trim((string)($fieldData['name'] ?? $fieldData['field_name'] ?? ''));
            if ($fieldName === '') {
                $fieldName = 'field_' . ($index + 1);
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

            if (!empty($fieldData['customHtml'])) {
                $customHtml[] = (string)$fieldData['customHtml'];
                $customCodeMode = 1;
            }
            if (!empty($fieldData['customCss'])) {
                $customCss[] = (string)$fieldData['customCss'];
                $customCodeMode = 1;
            }
            if (!empty($fieldData['customJs'])) {
                $customJs[] = (string)$fieldData['customJs'];
                $customCodeMode = 1;
            }
        }

        $layout = new MasterFormLayout();
        $layout->form_id = (int)$form->id;
        $layout->layout_name = $form->form_name . ' Layout';
        $layout->layout_type = (string)($form->form_type ?: 'builder');
        $layout->layout_json = Json::encode(['builder' => $formData]);
        $layout->custom_html = implode("\n\n", $customHtml);
        $layout->custom_css = implode("\n\n", $customCss);
        $layout->custom_js = implode("\n\n", $customJs);
        $layout->builder_state = Json::encode($formData);
        $layout->is_default = 1;
        $layout->sort_order = 0;
        $layout->save(false);

        $form->custom_code_mode = $customCodeMode;
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
}

