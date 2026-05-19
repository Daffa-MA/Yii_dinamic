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
        if ($formId > 0) {
            $html = self::appendCustomFormSubmitCollectorScript($html);
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
        $prepared = preg_replace_callback('/<form\b([^>]*)>/i', static function (array $matches) use ($action, $hidden, $hasCsrfInput): string {
            $attrs = $matches[1] ?? '';
            if (!preg_match('/\bmethod\s*=/i', $attrs)) {
                $attrs .= ' method="post"';
            }
            if (!preg_match('/\baction\s*=\s*([\'"])[^\'"]+\1/i', $attrs)) {
                $attrs .= ' action="' . Html::encode($action) . '"';
            }

            $openTag = '<form' . $attrs . '>';
            if ($hasCsrfInput) {
                return $openTag;
            }

            return $openTag . $hidden;
        }, $html) ?? $html;

        return self::appendCustomFormSubmitCollectorScript($prepared);
    }

    private static function appendCustomFormSubmitCollectorScript(string $html): string
    {
        $script = self::customFormSubmitCollectorScript();
        if (stripos($html, 'window.__customFormSubmitCollectorInstalled') !== false) {
            return $html;
        }
        if (stripos($html, '</body>') !== false) {
            return (string)preg_replace('/<\/body>/i', $script . '</body>', $html, 1);
        }
        return $html . $script;
    }

    private static function customFormSubmitCollectorScript(): string
    {
        return <<<'HTML'
<script>
(function(){
    if (window.__customFormSubmitCollectorInstalled) return;
    window.__customFormSubmitCollectorInstalled = true;

    function collectInto(form) {
        if (!form || form.tagName !== 'FORM') return;
        var controls = document.querySelectorAll('input[name], select[name], textarea[name]');
        controls.forEach(function(control) {
            if (control.form === form || control.disabled || !control.name) return;
            if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (control.name.charAt(0) === '_' && control.type === 'hidden') return;
            var alreadyPresent = false;
            Array.prototype.forEach.call(form.elements, function(existing) {
                if (existing.name === control.name) alreadyPresent = true;
            });
            if (alreadyPresent) return;
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = control.name;
            hidden.value = control.value;
            form.appendChild(hidden);
        });
    }

    function isEmbeddedCustomForm(form) {
        return !!(form && form.querySelector('input[name="_embedded"]'));
    }

    function showCustomFormAlert(type, message) {
        var existing = document.getElementById('custom-form-submit-alert');
        if (existing) existing.remove();

        var isSuccess = type === 'success';
        var alert = document.createElement('div');
        alert.id = 'custom-form-submit-alert';
        alert.setAttribute('role', 'status');
        alert.style.cssText = [
            'position:fixed',
            'top:22px',
            'right:22px',
            'z-index:2147483647',
            'width:min(420px,calc(100vw - 32px))',
            'background:#ffffff',
            'color:#0f172a',
            'border:1px solid ' + (isSuccess ? '#bbf7d0' : '#fecaca'),
            'border-left:5px solid ' + (isSuccess ? '#22c55e' : '#ef4444'),
            'border-radius:14px',
            'box-shadow:0 24px 60px rgba(15,23,42,.22)',
            'font-family:Inter,Segoe UI,Arial,sans-serif',
            'overflow:hidden',
            'transform:translateY(-8px)',
            'opacity:0',
            'transition:opacity .18s ease, transform .18s ease'
        ].join(';');

        alert.innerHTML =
            '<div style="display:flex;gap:12px;align-items:flex-start;padding:16px 18px;">' +
                '<div style="width:34px;height:34px;border-radius:999px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:' + (isSuccess ? '#dcfce7;color:#15803d' : '#fee2e2;color:#b91c1c') + ';font-weight:800;font-size:18px;">' + (isSuccess ? '&#10003;' : '!') + '</div>' +
                '<div style="min-width:0;flex:1;">' +
                    '<div style="font-size:15px;font-weight:800;margin-bottom:3px;">' + (isSuccess ? 'Data berhasil dikirim' : 'Gagal mengirim data') + '</div>' +
                    '<div style="font-size:13px;line-height:1.5;color:#475569;">' + escapeAlertText(message || (isSuccess ? 'Terima kasih, data sudah tersimpan.' : 'Silakan periksa kembali isian form.')) + '</div>' +
                '</div>' +
                '<button type="button" aria-label="Tutup" style="border:0;background:transparent;color:#94a3b8;font-size:22px;line-height:1;cursor:pointer;padding:0 0 0 8px;">&times;</button>' +
            '</div>';

        alert.querySelector('button').addEventListener('click', function() {
            alert.remove();
        });

        document.body.appendChild(alert);
        requestAnimationFrame(function() {
            alert.style.opacity = '1';
            alert.style.transform = 'translateY(0)';
        });

        clearTimeout(window.__customFormAlertTimer);
        window.__customFormAlertTimer = setTimeout(function() {
            if (!alert.parentNode) return;
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(function() {
                if (alert.parentNode) alert.remove();
            }, 220);
        }, isSuccess ? 4200 : 6500);
    }

    function escapeAlertText(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setSubmitting(form, submitting) {
        var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])');
        buttons.forEach(function(button) {
            if (submitting) {
                button.dataset.originalText = button.tagName === 'INPUT' ? button.value : button.innerHTML;
                if (button.tagName === 'INPUT') button.value = 'Mengirim...';
                else button.innerHTML = 'Mengirim...';
                button.disabled = true;
            } else {
                if (button.dataset.originalText !== undefined) {
                    if (button.tagName === 'INPUT') button.value = button.dataset.originalText;
                    else button.innerHTML = button.dataset.originalText;
                }
                button.disabled = false;
            }
        });
    }

    function submitEmbeddedForm(form) {
        if (!form || form.__customSubmitting) return;
        form.__customSubmitting = true;
        collectInto(form);
        setSubmitting(form, true);

        fetch(form.action || window.location.href, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function(response) {
                return response.text().then(function(text) {
                    var data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (error) {
                        data = {
                            success: response.ok,
                            message: response.ok ? 'Data berhasil dikirim.' : text
                        };
                    }
                    if (!response.ok && data.success !== true) {
                        data.success = false;
                    }
                    return data;
                });
            })
            .then(function(data) {
                showCustomFormAlert(data && data.success ? 'success' : 'error', data && data.message ? data.message : '');
            })
            .catch(function(error) {
                showCustomFormAlert('error', error && error.message ? error.message : 'Terjadi kesalahan jaringan.');
            })
            .finally(function() {
                form.__customSubmitting = false;
                setSubmitting(form, false);
            });
    }

    document.addEventListener('submit', function(event) {
        var form = event.target;
        collectInto(form);
        if (!isEmbeddedCustomForm(form)) return;
        event.preventDefault();
        submitEmbeddedForm(form);
    }, true);

    document.addEventListener('click', function(event) {
        var button = event.target && event.target.closest ? event.target.closest('button, input[type="submit"]') : null;
        if (!button) return;
        var form = button.form || button.closest('form') || document.querySelector('form');
        collectInto(form);
    }, true);

    document.addEventListener('formdata', function(event) {
        var form = event.target;
        collectInto(form);
        Array.prototype.forEach.call(form.elements, function(control) {
            if (!control.name || control.disabled) return;
            if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (!event.formData.has(control.name)) event.formData.append(control.name, control.value);
        });
    });

    if (window.HTMLFormElement && !window.__customFormSubmitPatched) {
        window.__customFormSubmitPatched = true;
        var nativeSubmit = window.HTMLFormElement.prototype.submit;
        window.HTMLFormElement.prototype.submit = function() {
            collectInto(this);
            if (isEmbeddedCustomForm(this)) {
                submitEmbeddedForm(this);
                return;
            }
            return nativeSubmit.call(this);
        };
    }
})();
</script>
HTML;
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

            $namePattern = preg_quote((string)($field['name'] ?? $name), '/');
            $source = preg_replace(
                '/(<label\b[^>]*>)\{label\}(<\/label>[\s\S]*?<(?:input|select|textarea)\b(?=[^>]*\bname=["\']' . $namePattern . '["\']))/i',
                '$1' . $label . '$2',
                $source
            ) ?? $source;
            $source = preg_replace(
                '/(<(?:input|textarea)\b(?=[^>]*\bname=["\']' . $namePattern . '["\'])(?=[^>]*\bplaceholder=["\'])[^>]*\bplaceholder=["\'])\{placeholder\}(["\'][^>]*>)/i',
                '$1' . $placeholder . '$2',
                $source
            ) ?? $source;
        }

        return $source;
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

