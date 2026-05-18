<?php

namespace app\services;

use app\components\CustomCodeSandbox;
use app\helpers\FormSystemFieldHelper;
use app\models\MasterForm;
use app\models\MasterFormLayout;

class FormRenderService
{
    public function hasCustomCodePayload(array $renderPayload, ?MasterForm $form = null): bool
    {
        $customHtml = trim((string)($renderPayload['customHtml'] ?? ''));
        $customCss = trim((string)($renderPayload['customCss'] ?? ''));
        $customJs = trim((string)($renderPayload['customJs'] ?? ''));

        if ($customHtml !== '' || $customCss !== '' || $customJs !== '') {
            return true;
        }

        return $form !== null && !empty($form->custom_code_mode);
    }

    public function renderCustomCodeOnly(array $renderPayload): string
    {
        $customHtml = (string)($renderPayload['customHtml'] ?? '');
        $customCss = trim((string)($renderPayload['customCss'] ?? ''));
        $customJs = trim((string)($renderPayload['customJs'] ?? ''));

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
        $customHtml = $layout ? CustomCodeSandbox::sanitizeHtml($layout->custom_html) : '';
        $customCss = $layout ? CustomCodeSandbox::sanitizeCss($layout->custom_css) : '';
        $customJs = $layout ? CustomCodeSandbox::sanitizeJs($layout->custom_js) : '';
        $hasOverride = trim($customHtml) !== '' || trim($customCss) !== '' || trim($customJs) !== '';

        $fields = array_map(static function (array $field): array {
            $field['inputType'] = FormSystemFieldHelper::resolveFieldInputType($field);
            if (in_array($field['inputType'], ['date', 'time', 'datetime-local'], true)) {
                $field['type'] = $field['inputType'];
            }
            return $field;
        }, FormSystemFieldHelper::filterFields($fields));

        return [
            'fields' => $fields,
            'hasOverride' => $hasOverride,
            'customHtml' => $customHtml,
            'customCss' => $customCss,
            'customJs' => $customJs,
        ];
    }
}

