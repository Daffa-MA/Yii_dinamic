<?php

namespace app\services;

use app\models\MasterForm;
use app\components\ActiveProjectContext;
use app\components\FormFlowDebugLogger;
use app\components\ProjectAuthContext;
use app\components\ProjectPermissionService;
use app\helpers\FormSystemFieldHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

class DynamicFormPreviewService
{
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

        $engine = new FormEngineService();
        $renderer = new FormRenderService();
        $schema = $engine->getResolvedFormSchema($form);
        $payload = $renderer->buildRenderPayload($form, $schema['fields'], $schema['layout']);
        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        $pageId = (int)($context['page_id'] ?? 0);
        $renderContext = (string)($context['render_context'] ?? '');
        $pageAuthorized = $pageId > 0 && $renderContext === 'page_content'
            ? (new ProjectPermissionService())->canUseFormAsPageContent((int)$form->id, $pageId, $projectId)
            : false;
        $formAuthorized = true;

        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $behaviorConfig = $this->extractDynamicFormBehaviorConfig($form, $fields);
        $customHtml = (string)($payload['customHtml'] ?? '');
        $customCss = (string)($payload['customCss'] ?? '');
        $customJs = (string)($payload['customJs'] ?? '');
        $hasOverride = !empty($payload['useCustomCode']) || !empty($payload['hasOverride']);

        $componentId = trim((string)($context['component_id'] ?? $context['componentId'] ?? 'component'));
        $componentId = preg_replace('/[^A-Za-z0-9_-]+/', '-', $componentId) ?: 'component';
        $instanceId = 'dynamic-form-' . (int)$form->id . '-' . ($pageId > 0 ? $pageId : 'preview') . '-' . $componentId;

        $titleHtml = $showTitle ? '<div style="font-weight:700;font-size:16px;color:#0f172a;margin-bottom:12px;">' . Html::encode((string)$form->form_name) . '</div>' : '';
        $formOpen = $interactive ? '<form method="post" id="' . Html::encode($instanceId) . '" class="dynamic-embedded-form" data-dynamic-form-instance="' . Html::encode($instanceId) . '" data-form-id="' . (int)$form->id . '" action="/master-form/submit?id=' . (int)$form->id . '">' .
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
            $scriptHtml = $customJs !== '' ? '<script>(function(){try{' . $customJs . '}catch(e){console.error(e);}})();</script>' : '';
            $customHtml = FormRenderService::prepareCustomFormSubmission($customHtml, (int)$form->id, [
                '_embedded' => $interactive ? '1' : '',
                'render_context' => $renderContext,
                'page_id' => $pageId > 0 ? (string)$pageId : '',
                'menu_id' => (int)($context['menu_id'] ?? 0) > 0 ? (string)(int)$context['menu_id'] : '',
                'project_id' => $projectId !== null ? (string)$projectId : '',
                'workspace_role' => $interactive ? $this->getWorkspaceRole() : '',
            ]);
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
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><textarea ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" placeholder="' . $placeholder . '" style="width:100%;min-height:70px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"></textarea></div>';
                continue;
            }
            if ($type === 'select') {
                $isFk = !empty($field['is_foreign_key']) || FormRenderService::isRelationField($field);
                $pickerMode = $isFk ? strtolower(trim((string)($field['picker_mode'] ?? 'dropdown'))) : 'dropdown';
                if (!in_array($pickerMode, ['dropdown', 'autocomplete', 'modal_picker', 'autocomplete_with_modal'], true)) {
                    $pickerMode = 'dropdown';
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
                if ($isFk && $pickerMode !== 'dropdown' && $interactive) {
                    $fieldHtml .= '<div style="margin-bottom:10px;">'
                        . '<label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label>'
                        . '<div class="relation-picker-wrapper" data-form-id="' . (int)$form->id . '" data-field-name="' . $name . '" data-picker-mode="' . Html::encode($pickerMode) . '">'
                        . '<div class="relation-picker-input-group relation-picker-row" style="display:flex;align-items:stretch;gap:8px;width:100%;">'
                        . '<input type="text" class="dynamic-form-input relation-picker-display" data-form-id="' . (int)$form->id . '" data-field-name="' . $name . '" data-picker-mode="' . Html::encode($pickerMode) . '" name="__fk_display_' . $name . '" value="' . Html::encode($selectedLabel) . '" placeholder="' . ($placeholder !== '' ? $placeholder : 'Cari ' . $label . '...') . '"' . ($pickerMode === 'modal_picker' ? ' readonly' : '') . ' style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">'
                        . '<input type="hidden" class="relation-picker-value" data-relation-picker-value="' . $name . '" name="' . $name . '" value="' . Html::encode($defaultValue) . '">'
                        . (($pickerMode === 'modal_picker' || $pickerMode === 'autocomplete_with_modal') ? '<button type="button" class="relation-picker-btn relation-picker-button" data-relation-picker-open="' . $name . '" data-field-name="' . $name . '" data-picker-field="' . $name . '" style="display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 14px;border:1px solid #dbe3ef;background:#fff;color:#334155;border-radius:10px;font-weight:700;cursor:pointer;white-space:nowrap;">Pilih</button>' : '')
                        . '</div>'
                        . '</div>'
                        . '<div class="relation-picker-status" data-relation-picker-status="' . $name . '">Tekan Enter untuk mencari data.</div>'
                        . '</div>';
                    continue;
                }
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><select ' . ($interactive ? '' : 'disabled') . ($isFk ? ' data-dynamic-fk="1" data-fk-submit-name="' . $name . '"' : '') . ' name="' . $name . '" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">' . $optionHtml . '</select></div>';
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
                    $items .= '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;margin-bottom:8px;">'
                        . '<input type="checkbox" ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '[]" value="' . Html::encode($value) . '" style="width:16px;height:16px;">'
                        . Html::encode($optionLabel)
                        . '</label>';
                }
                $fieldHtml .= '<div style="margin-bottom:10px;">'
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
                    $items .= '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;margin-bottom:8px;">'
                        . '<input type="radio" ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" value="' . Html::encode($value) . '" style="width:16px;height:16px;">'
                        . Html::encode($optionLabel)
                        . '</label>';
                }
                $fieldHtml .= '<div style="margin-bottom:10px;">'
                    . '<label style="display:block;font-size:12px;color:#334155;margin-bottom:6px;">' . $label . $required . '</label>'
                    . ($items !== '' ? $items : '<div style="font-size:12px;color:#94a3b8;">Tidak ada opsi.</div>')
                    . '</div>';
                continue;
            }
            if ($type === 'boolean') {
                $checked = !empty($field['default_value']) && ((string)$field['default_value'] === '1' || strtolower((string)$field['default_value']) === 'true');
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#334155;" class="form-check form-switch">'
                    . ($interactive ? '<input type="hidden" name="' . $name . '" value="0">' : '')
                    . '<input type="checkbox" ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" value="1" ' . ($checked ? 'checked' : '') . ' style="margin-right:8px;" class="form-check-input">'
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
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">'
                    . $label . $required
                    . '</label><div style="padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">'
                    . ($optionHtml !== '' ? $optionHtml : '<div style="font-size:12px;color:#64748b;">Tidak ada opsi.</div>')
                    . '</div></div>';
                continue;
            }
            if ($type === 'checkbox') {
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="font-size:12px;color:#334155;"><input type="checkbox" ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" value="1" style="margin-right:8px;">' . $label . '</label></div>';
                continue;
            }
            $inputType = in_array($type, ['email', 'number', 'password', 'tel', 'url', 'date', 'time', 'datetime-local', 'file'], true) ? $type : 'text';
            $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><input type="' . $inputType . '" ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" placeholder="' . $placeholder . '" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"></div>';
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
            if ($this->hasDynamicFormBehaviorConfig($behaviorConfig)) {
                $html .= $this->buildDynamicFormBehaviorScript((int)$form->id, $behaviorConfig);
            }
            $html = FormRenderService::attachAjaxSubmitHandler($html);
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

    private function extractDynamicFormBehaviorConfig(MasterForm $form, array $fields): array
    {
        $formData = $form->getFormDataArray();
        $detected = (new DynamicFormBehaviorDetector())->detect($form, $fields);
        if (isset($formData['behavior']) && is_array($formData['behavior']) && $this->hasDynamicFormBehaviorConfig($formData['behavior'])) {
            return $this->mergeDynamicBehaviorConfig($detected, $formData['behavior']);
        }
        if (isset($formData['form_behavior']) && is_array($formData['form_behavior']) && $this->hasDynamicFormBehaviorConfig($formData['form_behavior'])) {
            return $this->mergeDynamicBehaviorConfig($detected, $formData['form_behavior']);
        }
        return $detected;
    }

    private function mergeDynamicBehaviorConfig(array $detected, array $manual): array
    {
        $merged = $detected;
        foreach ($manual as $key => $value) {
            if (in_array($key, ['auto_fill_rules', 'unique_validation_rules'], true)) {
                if (!empty($value)) {
                    $merged[$key] = $key === 'auto_fill_rules'
                        ? $this->enrichManualAutoFillRules((array)$value, (array)($detected[$key] ?? []))
                        : $value;
                }
                continue;
            }

            if (in_array($key, ['detail_card', 'calculated_summary'], true) && is_array($value)) {
                if (empty($value['enabled']) && empty($value['items'])) {
                    continue;
                }
                $base = is_array($merged[$key] ?? null) ? $merged[$key] : [];
                $merged[$key] = array_replace_recursive($base, $value);
                continue;
            }

            if ($value !== null && $value !== '') {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private function enrichManualAutoFillRules(array $manualRules, array $detectedRules): array
    {
        $detectedByKey = [];
        foreach ($detectedRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $key = ($rule['trigger_field'] ?? '') . '|' . ($rule['target_field'] ?? '') . '|' . ($rule['source_path'] ?? '');
            $detectedByKey[$key] = $rule;
        }

        foreach ($manualRules as $index => $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $key = ($rule['trigger_field'] ?? '') . '|' . ($rule['target_field'] ?? '') . '|' . ($rule['source_path'] ?? '');
            if (isset($detectedByKey[$key])) {
                $manualRules[$index] = array_replace($detectedByKey[$key], $rule);
            }
        }

        return $manualRules;
    }

    private function hasDynamicFormBehaviorConfig(array $config): bool
    {
        return !empty($config['auto_fill_rules'])
            || !empty($config['detail_card']['enabled'])
            || !empty($config['calculated_summary']['enabled'])
            || (($config['submit_mode'] ?? '') === 'multiple_row_insert');
    }

    private function buildDynamicFormBehaviorScript(int $formId, array $config): string
    {
        $lookupUrl = Json::encode(Url::to(['/master-form/resolve-autofill']));
        $configJson = Json::encode($config);

        return '<script>(function(){'
            . 'var lookupUrl=' . $lookupUrl . ';'
            . 'var behavior=' . $configJson . ';'
            . 'var formId=' . (int)$formId . ';'
            . 'function byName(form,name){return form ? form.querySelector("[name=\"" + name + "\"], [name=\"" + name + "[]\"]") : null;}'
            . 'function allByName(form,name){return form ? Array.prototype.slice.call(form.querySelectorAll("[name=\"" + name + "\"], [name=\"" + name + "[]\"]")) : [];}'
            . 'function money(value){var n=Number(String(value||"0").replace(/[^0-9.-]/g,"")); if(!isFinite(n)) n=0; return new Intl.NumberFormat("id-ID",{style:"currency",currency:"IDR",maximumFractionDigits:0}).format(n);}'
            . 'function formatValue(value,format){return format==="currency_idr"?money(value):String(value==null?"":value);}'
            . 'function esc(value){return String(value==null?"":value).replace(/[&<>"\']/g,function(ch){return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","\'":"&#039;"}[ch];});}'
            . 'function cssIdent(value){if(window.CSS&&CSS.escape)return CSS.escape(value); return String(value).replace(/["\\\\]/g,"\\\\$&");}'
            . 'function ensureReadonly(control){if(!control||control.type==="hidden"||control.tagName==="SELECT")return; control.readOnly=true; control.setAttribute("aria-readonly","true"); control.style.background="#f8fafc"; control.style.cursor="default";}'
            . 'function ensureCurrencyDisplay(control,format){if(!control||format!=="currency_idr"||control.tagName==="SELECT")return null; var name=control.getAttribute("name")||""; var proxy=control.parentNode?control.parentNode.querySelector("[data-autofill-display-for=\""+cssIdent(name)+"\"]"):null; if(!proxy){proxy=document.createElement("input"); proxy.type="text"; proxy.readOnly=true; proxy.setAttribute("aria-readonly","true"); proxy.dataset.autofillDisplayFor=name; proxy.style.cssText=control.style.cssText||""; proxy.style.background="#f8fafc"; proxy.style.cursor="default"; proxy.style.fontWeight="600"; control.parentNode.insertBefore(proxy, control.nextSibling);} control.type="hidden"; return proxy;}'
            . 'function setControlValue(control,value,label,rule){if(!control||value===undefined||value===null)return; var normalized=String(value); var format=(rule&&rule.format)||""; if(control.tagName==="SELECT"){var found=false; Array.prototype.forEach.call(control.options||[],function(option){if(String(option.value)===normalized) found=true;}); if(!found&&normalized!==""){var option=document.createElement("option"); option.value=normalized; option.textContent=label||normalized; control.appendChild(option);}} control.value=normalized; ensureReadonly(control); var proxy=ensureCurrencyDisplay(control,format); if(proxy){proxy.value=formatValue(normalized,format);} control.dispatchEvent(new Event("change",{bubbles:true}));}'
            . 'function ensureDetailCard(form){var card=form.querySelector(".dynamic-autofill-detail-card"); if(card)return card; card=document.createElement("div"); card.className="dynamic-autofill-detail-card"; card.style.cssText="display:none;margin:10px 0;padding:12px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:10px;color:#0f172a;font-size:12px;line-height:1.6;"; form.insertBefore(card,form.firstChild.nextSibling); return card;}'
            . 'function ensureSummary(form){var summary=form.querySelector(".dynamic-calculated-summary"); if(summary)return summary; summary=document.createElement("div"); summary.className="dynamic-calculated-summary"; summary.style.cssText="margin:10px 0 12px;padding:12px;border:1px solid #dbeafe;background:#f8fafc;border-radius:10px;color:#0f172a;font-size:12px;line-height:1.8;"; form.insertBefore(summary,form.querySelector("button[type=submit]")||null); return summary;}'
            . 'function selectedValues(form,field){var controls=allByName(form,field); if(controls.length===1&&controls[0].tagName==="SELECT"&&controls[0].multiple){return Array.prototype.slice.call(controls[0].selectedOptions||[]).map(function(option){return String(option.value||"");}).filter(Boolean);} return controls.filter(function(input){return input.checked||input.selected;}).map(function(input){return String(input.value||"");}).filter(Boolean);}'
            . 'function rawFieldValue(form,field){var control=byName(form,field); return control?control.value:"";}'
            . 'function calcValue(form,node){if(!node)return 0; if(node.type==="count_selected")return selectedValues(form,node.field).length; if(node.type==="field_value")return Number(String(rawFieldValue(form,node.field)||"0").replace(/[^0-9.-]/g,""))||0; if(node.type==="multiply")return calcValue(form,node.left)*calcValue(form,node.right); return 0;}'
            . 'function updateSummary(form){var cfg=behavior.calculated_summary||{}; if(!cfg.enabled)return; var summary=ensureSummary(form); var html="<strong>"+esc(cfg.title||"Ringkasan Pembayaran")+"</strong>"; (cfg.items||[]).forEach(function(item){var value=calcValue(form,item); if(item.type==="field_value") value=rawFieldValue(form,item.field); var display=formatValue(value,item.format||""); html+="<br>"+(item.highlight?"<strong>":"")+esc(item.label||"Nilai")+" : "+esc(display)+(item.highlight?"</strong>":"");}); summary.innerHTML=html;}'
            . 'function showHint(form,message,isError){var box=form.querySelector(".dynamic-behavior-hint"); if(!box){box=document.createElement("div"); box.className="dynamic-behavior-hint"; box.style.cssText="margin:6px 0 10px;padding:8px 10px;border-radius:8px;font-size:12px;"; form.insertBefore(box, form.firstChild.nextSibling);} box.textContent=message||""; box.style.display=message?"block":"none"; box.style.background=isError?"#fef2f2":"#eff6ff"; box.style.color=isError?"#991b1b":"#1e3a8a"; box.style.border=isError?"1px solid #fecaca":"1px solid #bfdbfe";}'
            . 'function ruleFor(trigger,field){return (behavior.auto_fill_rules||[]).find(function(item){return item&&item.target_field===field&&item.trigger_field===trigger;})||{};}'
            . 'function attach(form){if(!form||form.dataset.dynamicBehaviorBound==="1")return; form.dataset.dynamicBehaviorBound="1"; var triggers={}; (behavior.auto_fill_rules||[]).forEach(function(rule){if(rule.trigger_field)triggers[rule.trigger_field]=true;}); if(behavior.detail_card&&behavior.detail_card.enabled&&behavior.detail_card.trigger_field)triggers[behavior.detail_card.trigger_field]=true;'
            . '(behavior.auto_fill_rules||[]).forEach(function(rule){var target=byName(form,rule.target_field); if(target){ensureReadonly(target); if(rule.format==="currency_idr"){ensureCurrencyDisplay(target,rule.format);}}});'
            . 'Object.keys(triggers).forEach(function(trigger){var control=byName(form,trigger); if(!control)return; control.addEventListener("change",function(){var val=String(control.value||"").trim(); if(!val){showHint(form,"",false);return;} fetch(lookupUrl+"?form_id="+encodeURIComponent(formId)+"&trigger_field="+encodeURIComponent(trigger)+"&trigger_value="+encodeURIComponent(val),{headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(response){if(!response.ok){throw new Error("HTTP "+response.status);} return response.json();}).then(function(result){console.info("[dynamic-autofill] response success:", !!(result&&result.success)); console.info("[dynamic-autofill] values:", result&&result.values); console.info("[dynamic-autofill] display:", result&&result.display); if(!result||!result.success){showHint(form,(result&&result.message)||"Data tidak ditemukan.",true); return;} var valuesApplied=false; try{Object.keys(result.values||{}).forEach(function(field){var target=byName(form,field); var rule=ruleFor(trigger,field); if(rule.fill_when_empty&&target&&String(target.value||"").trim()!=="")return; setControlValue(target,result.values[field],null,rule); valuesApplied=true;});}catch(valueError){console.error("[dynamic-autofill] exception message:", valueError&&valueError.message ? valueError.message : valueError); showHint(form,"Gagal memproses data otomatis.",true); return;} try{var display=result.display||{}; if(display.enabled&&display.items&&display.items.length){var card=ensureDetailCard(form); card.style.display="block"; var html="<strong>"+esc(display.detail_title||"Detail")+"</strong>"; display.items.forEach(function(item){html+="<br>"+esc(item.label||"")+": "+esc(item.value||"");}); card.innerHTML=html;}}catch(displayError){console.warn("[dynamic-autofill] exception message:", displayError&&displayError.message ? displayError.message : displayError);} try{updateSummary(form);}catch(summaryError){console.warn("[dynamic-autofill] exception message:", summaryError&&summaryError.message ? summaryError.message : summaryError);} if(valuesApplied){showHint(form,"Data berhasil diisi otomatis.",false);}}).catch(function(error){console.error("[dynamic-autofill] exception message:", error&&error.message ? error.message : error); showHint(form,"Gagal memuat data otomatis.",true);});});});'
            . 'var summaryFields={}; (behavior.calculated_summary&&behavior.calculated_summary.items||[]).forEach(function(item){[item.field,item.left&&item.left.field,item.right&&item.right.field].forEach(function(f){if(f)summaryFields[f]=true;});}); Object.keys(summaryFields).forEach(function(field){allByName(form,field).forEach(function(input){input.addEventListener("change",function(){updateSummary(form);}); input.addEventListener("input",function(){updateSummary(form);});});}); updateSummary(form);'
            . 'form.addEventListener("submit",function(e){if(behavior.submit_mode==="multiple_row_insert"&&behavior.multiple_row_field){if(selectedValues(form,behavior.multiple_row_field).length<1){e.preventDefault(); e.stopImmediatePropagation(); showHint(form,"Pilih minimal 1 data untuk "+behavior.multiple_row_field+".",true); return false;}}},true);}'
            . 'document.querySelectorAll("form.dynamic-embedded-form").forEach(attach);'
            . '})();</script>';
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
