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
        $formOpen = $interactive ? '<form method="post" enctype="multipart/form-data" id="' . Html::encode($instanceId) . '" class="dynamic-embedded-form" data-dynamic-form-instance="' . Html::encode($instanceId) . '" data-form-id="' . (int)$form->id . '" action="/master-form/submit?id=' . (int)$form->id . '">' .
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
            if (FormRenderService::isCameraField($field)) {
                $fieldHtml .= FormRenderService::renderCameraField($field, $interactive);
                continue;
            }
            if (FormRenderService::isGpsCameraField($field)) {
                $fieldHtml .= FormRenderService::renderGpsCameraField($field, $interactive);
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

        if (!$interactive) {
            self::$renderCache[$cacheKey] = $html;
        }

        if ($interactive) {
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
