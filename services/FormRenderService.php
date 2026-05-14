<?php

namespace app\services;

use app\components\CustomCodeSandbox;
use app\models\MasterForm;
use app\models\MasterFormLayout;

class FormRenderService
{
    public function buildRenderPayload(MasterForm $form, array $fields, ?MasterFormLayout $layout): array
    {
        $customHtml = $layout ? CustomCodeSandbox::sanitizeHtml($layout->custom_html) : '';
        $customCss = $layout ? CustomCodeSandbox::sanitizeCss($layout->custom_css) : '';
        $customJs = $layout ? CustomCodeSandbox::sanitizeJs($layout->custom_js) : '';
        $hasOverride = trim($customHtml) !== '' || trim($customCss) !== '' || trim($customJs) !== '';

        return [
            'fields' => $fields,
            'hasOverride' => $hasOverride,
            'customHtml' => $customHtml,
            'customCss' => $customCss,
            'customJs' => $customJs,
        ];
    }
}

