<?php

namespace app\services;

use app\models\MasterForm;
use app\components\ActiveProjectContext;
use app\components\FormFlowDebugLogger;
use app\components\ProjectAuthContext;
use app\components\ProjectPermissionService;
use app\helpers\FormSystemFieldHelper;
use yii\helpers\Html;

class DynamicFormPreviewService
{
    /** @var array<string, string> */
    private static array $renderCache = [];

    public function renderByScopedId(?int $formId, bool $showTitle = true, bool $interactive = false, array $context = []): string
    {
        if (empty($formId)) {
            FormFlowDebugLogger::logRender([
                'host' => \Yii::$app->request->hostInfo,
                'project_id' => (new ActiveProjectContext())->getActiveProjectId(),
                'role' => $this->getWorkspaceRole(),
                'page_id' => (int)($context['page_id'] ?? 0),
                'menu_id' => (int)($context['menu_id'] ?? 0),
                'form_id' => 0,
                'render_context' => (string)($context['render_context'] ?? ''),
                'page_authorized' => false,
                'form_authorized' => false,
                'reason' => 'form_not_selected',
            ]);
            return $this->renderInfo('Form belum dipilih.');
        }

        $form = MasterForm::findByIdScoped((int)$formId);
        if ($form === null) {
            FormFlowDebugLogger::logRender([
                'host' => \Yii::$app->request->hostInfo,
                'project_id' => (new ActiveProjectContext())->getActiveProjectId(),
                'role' => $this->getWorkspaceRole(),
                'page_id' => (int)($context['page_id'] ?? 0),
                'menu_id' => (int)($context['menu_id'] ?? 0),
                'form_id' => (int)$formId,
                'render_context' => (string)($context['render_context'] ?? ''),
                'page_authorized' => false,
                'form_authorized' => false,
                'reason' => 'form_not_found',
            ]);
            return $this->renderInfo('Form tidak ditemukan pada workspace/project aktif.');
        }

        $pageId = (int)($context['page_id'] ?? 0);
        $renderContext = (string)($context['render_context'] ?? '');
        $cacheKey = implode('|', [
            (int)$form->id,
            $showTitle ? '1' : '0',
            $interactive ? '1' : '0',
            $renderContext,
            $pageId,
            (int)($context['menu_id'] ?? 0),
            (string)($context['component_id'] ?? $context['componentId'] ?? ''),
            (string)($context['workspace_role'] ?? ''),
        ]);
        if (!$interactive && isset(self::$renderCache[$cacheKey])) {
            return self::$renderCache[$cacheKey];
        }

        $engine = new FormEngineService();
        $renderer = new FormRenderService();
        $schema = $engine->getResolvedFormSchema($form);
        $payload = $renderer->buildRenderPayload($form, $schema['fields'], $schema['layout']);
        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        $pageAuthorized = $pageId > 0 && $renderContext === 'page_content'
            ? (new ProjectPermissionService())->canUseFormAsPageContent((int)$form->id, $pageId, $projectId)
            : false;
        $formAuthorized = true;

        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $customHtml = (string)($payload['customHtml'] ?? '');
        $customCss = (string)($payload['customCss'] ?? '');
        $customJs = (string)($payload['customJs'] ?? '');
        $hasOverride = !empty($payload['useCustomCode']) || !empty($payload['hasOverride']);

        $componentId = trim((string)($context['component_id'] ?? $context['componentId'] ?? 'component'));
        $componentId = preg_replace('/[^A-Za-z0-9_-]+/', '-', $componentId) ?: 'component';
        $instanceId = 'dynamic-form-' . (int)$form->id . '-' . ($pageId > 0 ? $pageId : 'preview') . '-' . $componentId;

        $titleHtml = $showTitle ? '<div style="font-weight:700;font-size:16px;color:#0f172a;margin-bottom:12px;">' . Html::encode((string)$form->form_name) . '</div>' : '';
        $formSchemaAttr = '';
        if ($interactive && !empty($fields)) {
            $formSchemaData = [
                'fields' => $fields,
                'form_id' => (int)$form->id,
                'title' => (string)$form->form_name,
            ];
            $formSchemaAttr = ' data-form-schema="' . Html::encode(json_encode($formSchemaData)) . '"';
        }
        $formOpen = $interactive ? '<form method="post" enctype="multipart/form-data" id="' . Html::encode($instanceId) . '" class="dynamic-embedded-form" data-dynamic-form-instance="' . Html::encode($instanceId) . '" data-form-id="' . (int)$form->id . '" action="/master-form/submit?id=' . (int)$form->id . '"' . $formSchemaAttr . '>' .
            '<input type="hidden" name="' . Html::encode(\Yii::$app->request->csrfParam) . '" value="' . Html::encode(\Yii::$app->request->getCsrfToken()) . '">' : '';
        $formClose = $interactive ? '</form>' : '';
        $embeddedFlag = '';
        if ($interactive) {
            $embeddedFlag = '<input type="hidden" name="_embedded" value="1">';
            if ($renderContext !== '') {
                $embeddedFlag .= '<input type="hidden" name="render_context" value="' . Html::encode($renderContext) . '">';
            }
            if ($pageId > 0) {
                $embeddedFlag .= '<input type="hidden" name="page_id" value="' . $pageId . '">';
            }
            if ((int)($context['menu_id'] ?? 0) > 0) {
                $embeddedFlag .= '<input type="hidden" name="menu_id" value="' . (int)$context['menu_id'] . '">';
            }
            if ($projectId !== null) {
                $embeddedFlag .= '<input type="hidden" name="project_id" value="' . (int)$projectId . '">';
            }
            $workspaceRole = $this->getWorkspaceRole();
            if ($workspaceRole !== '') {
                $embeddedFlag .= '<input type="hidden" name="workspace_role" value="' . Html::encode($workspaceRole) . '">';
            }
        }

        if ($hasOverride) {
            $schemaScript = '';
            if (!empty($fields)) {
                $schemaScript = '<script>window.__dynamicFormSchema = ' . json_encode(['fields' => $fields, 'form_id' => (int)$form->id, 'title' => (string)$form->form_name]) . ';</script>';
            }
            // PERBAIKAN BUG 2 (runtime): Isi otomatis tanggal/waktu + readonly/min/max di mode
            // custom code page source. Preview memakai render server-side (nilai terisi dari
            // Asia/Jakarta), sedangkan override mengembalikan custom HTML tersimpan dengan input
            // kosong. Script ini menjamin perilaku runtime sama seperti preview, tanpa bergantung
            // pada engine binding (schema global/container :has()) yang rapuh di iframe sandbox.
            $dateRuntimeFields = [];
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $inputType = (string)($field['inputType'] ?? $field['type'] ?? $field['field_type'] ?? '');
                if (!in_array($inputType, ['date', 'time', 'datetime', 'datetime-local'], true)) {
                    continue;
                }
                $fieldName = (string)($field['field_name'] ?? $field['name'] ?? $field['field_key'] ?? $field['resolved_name'] ?? '');
                if ($fieldName === '') {
                    continue;
                }
                $dateRuntimeFields[] = [
                    'name' => $fieldName,
                    'auto_fill_today' => !empty($field['auto_fill_today']),
                    'date_readonly' => !empty($field['date_readonly']),
                    'min_date' => (string)($field['min_date'] ?? ''),
                    'max_date' => (string)($field['max_date'] ?? ''),
                    'disable_past_dates' => !empty($field['disable_past_dates']),
                    'disable_future_dates' => !empty($field['disable_future_dates']),
                ];
            }
            $dateRuntimeScript = '';
            if (!empty($dateRuntimeFields)) {
                $dateRuntimeScript = '<script>(function(){var fields=' . json_encode($dateRuntimeFields) . ';function apply(){var now=new Date(Date.now()+7*3600*1000);var y=now.getUTCFullYear();var mo=String(now.getUTCMonth()+1).padStart(2,"0");var d=String(now.getUTCDate()).padStart(2,"0");var h=String(now.getUTCHours()).padStart(2,"0");var mi=String(now.getUTCMinutes()).padStart(2,"0");var today=y+"-"+mo+"-"+d;for(var i=0;i<fields.length;i++){var f=fields[i];var els=document.getElementsByName(f.name);for(var j=0;j<els.length;j++){var el=els[j];if(el.type!=="date"&&el.type!=="time"&&el.type!=="datetime-local")continue;if(f.auto_fill_today&&!el.value){if(el.type==="date")el.value=today;else if(el.type==="time")el.value=h+":"+mi;else el.value=today+"T"+h+":"+mi;}if(f.min_date)el.setAttribute("min",f.min_date);if(f.max_date)el.setAttribute("max",f.max_date);if(f.disable_past_dates)el.setAttribute("min",today);if(f.disable_future_dates)el.setAttribute("max",today);if(f.date_readonly){el.readOnly=true;el.setAttribute("data-date-readonly","1");}}}}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",apply);}else{apply();}})();</script>';
            }
            $scriptHtml = $customJs !== '' ? '<script>(function(){try{' . $customJs . '}catch(e){console.error(e);}})();</script>' : '';
            $customHtml = FormRenderService::prepareCustomFormSubmission($customHtml, (int)$form->id, [
                '_embedded' => $interactive ? '1' : '',
                'render_context' => $renderContext,
                'page_id' => $pageId > 0 ? (string)$pageId : '',
                'menu_id' => (int)($context['menu_id'] ?? 0) > 0 ? (string)(int)$context['menu_id'] : '',
                'project_id' => $projectId !== null ? (string)$projectId : '',
                'workspace_role' => $interactive ? $this->getWorkspaceRole() : '',
            ]);
            $customHtml = FormRenderService::injectCameraHandler($customHtml, $fields);
            $customHtml = FormRenderService::injectGpsCameraHandler($customHtml, $fields);
            $customHtml = FormRenderService::injectInteractivePickerRuntime($customHtml, $fields, (int)$form->id);
            $customHtml = FormRenderService::injectAutoFillRuntime($customHtml, $fields, (int)$form->id);
            FormFlowDebugLogger::logRender([
                'host' => \Yii::$app->request->hostInfo,
                'project_id' => $projectId,
                'role' => $this->getWorkspaceRole(),
                'page_id' => $pageId,
                'menu_id' => (int)($context['menu_id'] ?? 0),
                'form_id' => (int)$form->id,
                'render_context' => $renderContext,
                'page_authorized' => $pageAuthorized,
                'form_authorized' => $formAuthorized,
                'reason' => 'rendered_override',
                'field_count' => count($fields),
                'has_override' => $hasOverride,
            ]);
            return '<div style="padding:14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">'
                . $titleHtml
                . ($customCss !== '' ? '<style>' . $customCss . '</style>' : '')
                . $customHtml
                . $schemaScript
                . $dateRuntimeScript
                . $scriptHtml
                . '</div>';
        }

        $fieldHtml = '';
        foreach ($fields as $index => $field) {
            if (FormSystemFieldHelper::isSystemFieldData($field)) {
                continue;
            }

            $field = FormRenderService::resolveDynamicChoiceOptions(FormRenderService::normalizeFieldForRender((array)$field, (int)$index));
            $name = Html::encode((string)($field['name'] ?? 'field'));
            $label = Html::encode((string)($field['label'] ?? ucfirst($name)));
            $type = FormSystemFieldHelper::resolveFieldInputType($field);
            $placeholder = Html::encode((string)($field['placeholder'] ?? ''));
            $required = !empty($field['required']) ? ' *' : '';

            if ($type === 'hidden') {
                $fieldHtml .= '<input type="hidden" name="' . $name . '" value="' . Html::encode((string)($field['default_value'] ?? '')) . '">';
                continue;
            }
            if ($type === 'textarea') {
                $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' readonly data-readonly-locked="1"' : '';
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><textarea ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '" placeholder="' . $placeholder . '" style="width:100%;min-height:70px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"></textarea></div>';
                continue;
            }
            if ($type === 'select' || $type === 'dropdown') {
                $isFk = !empty($field['is_foreign_key']) || FormRenderService::isRelationField($field);
                $pickerMode = $isFk ? strtolower(trim((string)($field['picker_mode'] ?? 'dropdown'))) : 'dropdown';
                if (!in_array($pickerMode, ['dropdown', 'autocomplete', 'modal_picker', 'autocomplete_with_modal'], true)) {
                    $pickerMode = 'dropdown';
                }
                $autoFillSource = (string)($field['auto_fill'] ?? '');
                $autoFilled = $autoFillSource !== '' && $autoFillSource !== 'none';
                $autoFillValue = '';
                if ($autoFilled && $projectId !== null && \Yii::$app->has('currentIdentity')) {
                    $resolvedIdentity = \Yii::$app->currentIdentity->get($projectId);
                    if ($autoFillSource === 'current_identity' && is_array($resolvedIdentity)) {
                        $autoFillValue = (string)($resolvedIdentity['identity_record_id'] ?? '');
                    } elseif ($autoFillSource === 'current_user' && is_array($resolvedIdentity)) {
                        $autoFillValue = (string)($resolvedIdentity['user_id'] ?? '');
                    }
                }
                if ($autoFillValue !== '') {
                    $field['default_value'] = $autoFillValue;
                }
                $optionHtml = '<option value="">Pilih...</option>';
                $defaultValue = (string)($field['default_value'] ?? '');
                $selectedLabel = '';
                foreach ((array)($field['options'] ?? []) as $option) {
                    if (!is_array($option)) {
                        continue;
                    }
                    $value = (string)($option['value'] ?? '');
                    if ($value === '') {
                        continue;
                    }
                    $labelOption = (string)($option['label'] ?? $value);
                    if ($defaultValue !== '' && $defaultValue === $value) {
                        $selectedLabel = $labelOption;
                    }
                    $optionHtml .= '<option value="' . Html::encode($value) . '"' . ($defaultValue === $value ? ' selected' : '') . '>' . Html::encode($labelOption) . '</option>';
                }
                if ($isFk && $pickerMode !== 'dropdown' && $interactive && !$autoFilled) {
                    $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;">'
                        . '<label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label>'
                        . '<div class="relation-picker-wrapper" data-form-id="' . (int)$form->id . '" data-field-name="' . $name . '" data-picker-mode="' . Html::encode($pickerMode) . '">'
                        . '<div class="relation-picker-input-group relation-picker-row" style="display:flex;align-items:stretch;gap:8px;width:100%;">'
                        . '<input type="text" class="dynamic-form-input relation-picker-display" data-form-id="' . (int)$form->id . '" data-field-name="' . $name . '" data-picker-mode="' . Html::encode($pickerMode) . '" name="__fk_display_' . $name . '" value="' . Html::encode($selectedLabel) . '" placeholder="' . ($placeholder !== '' ? $placeholder : 'Cari ' . $label . '...') . '"' . (!empty($field['readonly']) || !empty($field['readOnly']) ? ' readonly' : '') . ' style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">'
                        . '<input type="hidden" class="relation-picker-value" data-relation-picker-value="' . $name . '" data-form-id="' . (int)$form->id . '" name="' . $name . '" value="' . Html::encode($defaultValue) . '">'
                        . (($pickerMode === 'modal_picker' || $pickerMode === 'autocomplete_with_modal') ? '<button type="button" class="relation-picker-btn relation-picker-button" data-relation-picker-open="' . $name . '" data-field-name="' . $name . '" data-picker-field="' . $name . '" style="display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 14px;border:1px solid #dbe3ef;background:#fff;color:#334155;border-radius:10px;font-weight:700;cursor:pointer;white-space:nowrap;">Pilih</button>' : '')
                        . '</div>'
                        . '</div>'
                        . '<div class="relation-picker-status" data-relation-picker-status="' . $name . '">Tekan Enter untuk mencari data.</div>'
                        . '<div class="relation-picker-detail" data-relation-picker-detail="' . $name . '" hidden></div>'
                        . '</div>';
                    continue;
                }
                $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' disabled data-readonly-locked="1"' : '';
                $selectHtml = '<select ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ($autoFilled ? ' disabled data-auto-fill-identity="1"' : '') . ($isFk ? ' data-dynamic-fk="1" data-fk-submit-name="' . $name . '"' : '') . ' name="' . $name . '" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">' . $optionHtml . '</select>';
                if (!empty($field['readonly']) || !empty($field['readOnly'])) {
                    $selectHtml .= '<input type="hidden" name="' . $name . '" value="' . Html::encode($defaultValue) . '" data-readonly-mirror="1">';
                }
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label>' . $selectHtml . '</div>';
                continue;
            }
            if ($type === 'checkboxes') {
                $options = (array)($field['options'] ?? []);
                $items = '';
                foreach ($options as $option) {
                    if (!is_array($option)) {
                        continue;
                    }
                    $value = trim((string)($option['value'] ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $optionLabel = (string)($option['label'] ?? $value);
                    $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' disabled data-readonly-locked="1"' : '';
                $items .= '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;margin-bottom:8px;">'
                    . '<input type="checkbox" ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '[]" value="' . Html::encode($value) . '" style="width:16px;height:16px;">'
                    . Html::encode($optionLabel)
                    . '</label>';
                }
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;">'
                    . '<label style="display:block;font-size:12px;color:#334155;margin-bottom:6px;">' . $label . $required . '</label>'
                    . ($items !== '' ? $items : '<div style="font-size:12px;color:#94a3b8;">Tidak ada opsi.</div>')
                    . '</div>';
                continue;
            }
            if ($type === 'radio') {
                $options = (array)($field['options'] ?? []);
                $items = '';
                foreach ($options as $option) {
                    if (!is_array($option)) {
                        continue;
                    }
                    $value = trim((string)($option['value'] ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $optionLabel = (string)($option['label'] ?? $value);
                    $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' disabled data-readonly-locked="1"' : '';
                $items .= '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;margin-bottom:8px;">'
                    . '<input type="radio" ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '" value="' . Html::encode($value) . '" style="width:16px;height:16px;">'
                    . Html::encode($optionLabel)
                    . '</label>';
                }
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;">'
                    . '<label style="display:block;font-size:12px;color:#334155;margin-bottom:6px;">' . $label . $required . '</label>'
                    . ($items !== '' ? $items : '<div style="font-size:12px;color:#94a3b8;">Tidak ada opsi.</div>')
                    . '</div>';
                continue;
            }
            if ($type === 'boolean') {
                $checked = !empty($field['default_value']) && ((string)$field['default_value'] === '1' || strtolower((string)$field['default_value']) === 'true');
                $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' disabled data-readonly-locked="1"' : '';
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;"><label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;" class="form-check form-switch">'
                    . ($interactive ? '<input type="hidden" name="' . $name . '" value="0">' : '')
                    . '<input type="checkbox" ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '" value="1" ' . ($checked ? 'checked' : '') . ' style="margin-right:8px;" class="form-check-input">'
                    . '<span>' . $label . $required . '</span>'
                    . '</label></div>';
                continue;
            }
            if ($type === 'checkboxes') {
                $optionHtml = '';
                foreach ((array)($field['options'] ?? []) as $option) {
                    if (!is_array($option)) {
                        continue;
                    }
                    $value = (string)($option['value'] ?? '');
                    if ($value === '') {
                        continue;
                    }
                    $optionHtml .= '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;margin:4px 0;">'
                        . '<input type="checkbox" ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '[]" value="' . Html::encode($value) . '" style="margin-right:8px;">'
                        . '<span>' . Html::encode((string)($option['label'] ?? $value)) . '</span>'
                        . '</label>';
                }
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">'
                    . $label . $required
                    . '</label><div style="padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">'
                    . ($optionHtml !== '' ? $optionHtml : '<div style="font-size:12px;color:#64748b;">Tidak ada opsi.</div>')
                    . '</div></div>';
                continue;
            }
            if ($type === 'checkbox') {
                $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' disabled data-readonly-locked="1"' : '';
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;"><label style="font-size:12px;color:#334155;"><input type="checkbox" ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '" value="1" style="margin-right:8px;">' . $label . '</label></div>';
                continue;
            }
            if (FormRenderService::isCameraField($field)) {
                $fieldHtml .= FormRenderService::renderCameraField($field, $interactive);
                continue;
            }
            if (FormRenderService::isGpsCameraField($field)) {
                $fieldHtml .= FormRenderService::renderGpsCameraField($field, $interactive);
                continue;
            }
            $isFileUpload = in_array($type, ['file', 'file_upload'], true);
            if ($isFileUpload) {
                $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' disabled data-readonly-locked="1"' : '';
                $fieldHtml .= '<div data-field-container="' . $name . '" style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><input type="file" ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"></div>';
                continue;
            }
            $inputType = in_array($type, ['email', 'number', 'password', 'tel', 'url', 'date', 'time', 'datetime-local', 'file'], true) ? $type : 'text';
            $dateAttrs = '';
            if (in_array($type, ['date', 'time', 'datetime-local'], true)) {
                if (!empty($field['min_date'])) $dateAttrs .= ' min="' . Html::encode($field['min_date']) . '"';
                if (!empty($field['max_date'])) $dateAttrs .= ' max="' . Html::encode($field['max_date']) . '"';
                if (!empty($field['disable_past_dates'])) $dateAttrs .= ' data-disable-past-dates="1"';
                if (!empty($field['disable_future_dates'])) $dateAttrs .= ' data-disable-future-dates="1"';
                if (!empty($field['auto_fill_today'])) {
                    $dateAttrs .= ' data-auto-fill-today="1"';
                    $defaultValueFromField = (string)($field['default_value'] ?? '');
                    if ($defaultValueFromField === '') {
                        $now = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
                        if ($type === 'date') {
                            $defaultValueFromField = $now->format('Y-m-d');
                        } elseif ($type === 'time') {
                            $defaultValueFromField = $now->format('H:i');
                        } elseif ($type === 'datetime' || $type === 'datetime-local') {
                            $defaultValueFromField = $now->format('Y-m-d\TH:i');
                        }
                    }
                    $field['default_value'] = $defaultValueFromField;
                }
                if (!empty($field['date_readonly'])) {
                    $dateAttrs .= ' readonly data-date-readonly="1"';
                }
            }
            $defaultValue = Html::encode((string)($field['default_value'] ?? ''));
            $requiredAttr = !empty($field['required']) ? ' required' : '';
            $readonlyAttr = (!empty($field['readonly']) || !empty($field['readOnly'])) ? ' readonly data-readonly-locked="1"' : '';
            $fieldHtml .= '<div style="margin-bottom:10px;" data-field-container="' . $name . '"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><input type="' . $inputType . '" ' . ($interactive ? '' : 'disabled') . $readonlyAttr . ' name="' . $name . '" value="' . $defaultValue . '" placeholder="' . $placeholder . '" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"' . $dateAttrs . $requiredAttr . '></div>';
        }

        $submitHtml = '<div style="margin-top:6px;"><button type="' . ($interactive ? 'submit' : 'button') . '" ' . ($interactive ? '' : 'disabled') . ' style="padding:9px 14px;background:#0f172a;color:#fff;border:none;border-radius:8px;opacity:.85;">Submit</button></div>';

        $html = '<div style="padding:14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">'
            . $titleHtml
            . $formOpen
            . $embeddedFlag
            . ($fieldHtml !== '' ? $fieldHtml : '<div style="font-size:12px;color:#64748b;">Form tidak memiliki field.</div>')
            . ($interactive ? '<div class="dynamic-form-submit-message" style="display:none;margin-bottom:10px;padding:10px;border-radius:8px;font-size:12px;"></div>' : '')
            . $submitHtml
            . $formClose
            . '</div>';

        if ($interactive) {
            $html = FormRenderService::attachAjaxSubmitHandler($html);
        }
        $html = FormRenderService::injectAutoFillRuntime($html, $fields, (int)$form->id);

        if (!$interactive) {
            self::$renderCache[$cacheKey] = $html;
        }

        FormFlowDebugLogger::logRender([
            'host' => \Yii::$app->request->hostInfo,
            'project_id' => $projectId,
            'role' => $this->getWorkspaceRole(),
            'page_id' => $pageId,
            'menu_id' => (int)($context['menu_id'] ?? 0),
            'form_id' => (int)$form->id,
            'render_context' => $renderContext,
            'page_authorized' => $pageAuthorized,
            'form_authorized' => $formAuthorized,
            'reason' => 'rendered',
            'field_count' => count($fields),
            'has_override' => $hasOverride,
        ]);

        return $html;
    }

    private function renderInfo(string $message): string
    {
        return '<div style="padding:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;font-size:12px;">' . Html::encode($message) . '</div>';
    }

    private function getWorkspaceRole(): string
    {
        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        if ($projectId === null) {
            return '';
        }

        $user = (new ProjectAuthContext())->getAuthenticatedUser($projectId);
        if ($user === null) {
            return '';
        }

        return strtolower(trim((string)$user->role));
    }
}
