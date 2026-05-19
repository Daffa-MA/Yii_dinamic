<?php

namespace app\services;

use app\components\CustomCodeSandbox;
use app\helpers\FormSystemFieldHelper;
use app\models\MasterForm;
use app\models\MasterFormLayout;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

class FormRenderService
{
    public function hasCustomCodePayload(array $renderPayload, ?MasterForm $form = null): bool
    {
        $customHtml = trim((string)($renderPayload['customHtml'] ?? ''));
        $customCss = trim((string)($renderPayload['customCss'] ?? ''));
        $customJs = trim((string)($renderPayload['customJs'] ?? ''));

        return !empty($renderPayload['useCustomCode']) || ($form !== null && !empty($form->custom_code_mode));
    }

    public function renderCustomCodeOnly(array $renderPayload): string
    {
        $customHtml = (string)($renderPayload['customHtml'] ?? '');
        $customCss = trim((string)($renderPayload['customCss'] ?? ''));
        $customJs = trim((string)($renderPayload['customJs'] ?? ''));
        $formId = (int)($renderPayload['formId'] ?? 0);
        if ($formId > 0) {
            $customHtml = self::prepareCustomFormSubmission($customHtml, $formId);
        }

        if ($customHtml !== '' && preg_match('/^\s*(<!doctype html|<html)\b/i', $customHtml) === 1) {
            return $customHtml;
        }

        $html = '';
        if ($customCss !== '') {
            $html .= '<style>' . $customCss . '</style>';
        }

        $html .= $customHtml;

        if ($customJs !== '') {
            $html .= '<script>(function(){try{' . $customJs . '}catch(e){console.error(e);}})();</script>';
        }

        return $html;
    }

    public function buildRenderPayload(MasterForm $form, array $fields, ?MasterFormLayout $layout): array
    {
        $formCustomHtml = $form->hasAttribute('custom_html') ? (string)$form->custom_html : '';
        $formCustomCss = $form->hasAttribute('custom_css') ? (string)$form->custom_css : '';
        $formCustomJs = $form->hasAttribute('custom_js') ? (string)$form->custom_js : '';
        $layoutCustomHtml = $layout ? (string)$layout->custom_html : '';
        $layoutCustomCss = $layout ? (string)$layout->custom_css : '';
        $layoutCustomJs = $layout ? (string)$layout->custom_js : '';
        $customHtml = CustomCodeSandbox::sanitizeHtml($formCustomHtml !== '' ? $formCustomHtml : $layoutCustomHtml);
        $customCss = CustomCodeSandbox::sanitizeCss($formCustomCss !== '' ? $formCustomCss : $layoutCustomCss);
        $customJs = CustomCodeSandbox::sanitizeJs($formCustomJs !== '' ? $formCustomJs : $layoutCustomJs);
        $useCustomCode = $layout !== null
            ? ($layout->hasAttribute('use_custom_code') && !empty($layout->use_custom_code))
                || ($form->hasAttribute('use_custom_code') && !empty($form->use_custom_code))
                || !empty($form->custom_code_mode)
            : ($form->hasAttribute('use_custom_code') && !empty($form->use_custom_code)) || !empty($form->custom_code_mode);

        $fields = array_map(static function (array $field): array {
            $field['inputType'] = FormSystemFieldHelper::resolveFieldInputType($field);
            if (in_array($field['inputType'], ['date', 'time', 'datetime-local'], true)) {
                $field['type'] = $field['inputType'];
            }
            return $field;
        }, FormSystemFieldHelper::filterFields($fields));
        $customHtml = self::resolveFormSourceTokens($customHtml, $fields);

        return [
            'fields' => $fields,
            'formId' => (int)$form->id,
            'hasOverride' => $useCustomCode,
            'useCustomCode' => $useCustomCode,
            'customHtml' => $customHtml,
            'customCss' => $customCss,
            'customJs' => $customJs,
        ];
    }

    public static function prepareCustomFormSubmission(string $html, int $formId, array $hiddenInputs = []): string
    {
        if ($formId <= 0 || trim($html) === '' || stripos($html, '<form') === false) {
            return $html;
        }

        $action = Url::to(['/master-form/submit', 'id' => $formId], true);
        $csrfParam = Yii::$app->request->csrfParam;
        $csrfToken = Yii::$app->request->getCsrfToken();
        $hidden = '<input type="hidden" name="' . Html::encode($csrfParam) . '" value="' . Html::encode($csrfToken) . '">';
        foreach ($hiddenInputs as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $hidden .= '<input type="hidden" name="' . Html::encode((string)$name) . '" value="' . Html::encode((string)$value) . '">';
        }

        $hasCsrfInput = stripos($html, 'name="' . $csrfParam . '"') !== false || stripos($html, "name='" . $csrfParam . "'") !== false;
        return preg_replace_callback('/<form\b([^>]*)>/i', static function (array $matches) use ($action, $hidden, $hasCsrfInput): string {
            $attrs = $matches[1] ?? '';
            if (!preg_match('/\bmethod\s*=/i', $attrs)) {
                $attrs .= ' method="post"';
            }
            if (!preg_match('/\baction\s*=/i', $attrs)) {
                $attrs .= ' action="' . Html::encode($action) . '"';
            }

            $openTag = '<form' . $attrs . '>';
            if ($hasCsrfInput) {
                return $openTag;
            }

            return $openTag . $hidden;
        }, $html, 1) ?? $html;
    }

    private static function resolveFormSourceTokens(string $source, array $fields): string
    {
        foreach ($fields as $index => $field) {
            $name = self::fieldTokenName($field, $index);
            $label = self::escapeTokenValue(self::fieldLabel($field, $index));
            $placeholder = self::escapeTokenValue(self::fieldPlaceholder($field, $index));
            $fieldName = self::escapeTokenValue((string)($field['name'] ?? $name));
            $fieldId = self::escapeTokenValue((string)($field['id'] ?? $name));

            $source = preg_replace('/\{' . preg_quote($name, '/') . '_label\}/', $label, $source) ?? $source;
            $source = preg_replace('/\{' . preg_quote($name, '/') . '_placeholder\}/', $placeholder, $source) ?? $source;
            $source = preg_replace('/\{' . preg_quote($name, '/') . '_name\}/', $fieldName, $source) ?? $source;
            $source = preg_replace('/\{' . preg_quote($name, '/') . '_id\}/', $fieldId, $source) ?? $source;
        }

        $labelIndex = 0;
        $source = preg_replace_callback('/\{label\}/', static function () use ($fields, &$labelIndex): string {
            $field = $fields[$labelIndex] ?? end($fields) ?: [];
            $label = self::escapeTokenValue(self::fieldLabel($field, $labelIndex));
            $labelIndex++;
            return $label;
        }, $source) ?? $source;

        $placeholderIndex = 0;
        return preg_replace_callback('/\{placeholder\}/', static function () use ($fields, &$placeholderIndex): string {
            $field = $fields[$placeholderIndex] ?? end($fields) ?: [];
            $placeholder = self::escapeTokenValue(self::fieldPlaceholder($field, $placeholderIndex));
            $placeholderIndex++;
            return $placeholder;
        }, $source) ?? $source;
    }

    private static function fieldTokenName(array $field, int $index): string
    {
        $name = (string)($field['name'] ?? $field['field_name'] ?? $field['column_name'] ?? $field['id'] ?? 'field_' . ($index + 1));
        $name = trim((string)preg_replace('/[^a-zA-Z0-9_]+/', '_', $name), '_');
        return $name !== '' ? $name : 'field_' . ($index + 1);
    }

    private static function fieldLabel(array $field, int $index): string
    {
        $label = (string)($field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? '');
        return $label !== '' ? $label : self::humanizeFieldName(self::fieldTokenName($field, $index));
    }

    private static function fieldPlaceholder(array $field, int $index): string
    {
        $placeholder = (string)($field['placeholder'] ?? '');
        if ($placeholder !== '') {
            return $placeholder;
        }

        $type = (string)($field['type'] ?? $field['inputType'] ?? 'text');
        if (in_array($type, ['date', 'time', 'datetime-local'], true)) {
            return '';
        }

        $label = strtolower(self::fieldLabel($field, $index));
        return $type === 'select' ? 'Pilih ' . $label : 'Masukkan ' . $label;
    }

    private static function humanizeFieldName(string $value): string
    {
        $value = trim((string)preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $value)));
        return ucwords(strtolower($value));
    }

    private static function escapeTokenValue(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

