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
        $customHtml = (string)($payload['customHtml'] ?? '');
        $customCss = (string)($payload['customCss'] ?? '');
        $customJs = (string)($payload['customJs'] ?? '');
        $hasOverride = !empty($payload['useCustomCode']) || !empty($payload['hasOverride']);

        $titleHtml = $showTitle ? '<div style="font-weight:700;font-size:16px;color:#0f172a;margin-bottom:12px;">' . Html::encode((string)$form->form_name) . '</div>' : '';
        $formOpen = $interactive ? '<form method="post" class="dynamic-embedded-form" action="/master-form/submit?id=' . (int)$form->id . '">' .
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
        foreach ($fields as $field) {
            if (FormSystemFieldHelper::isSystemFieldData($field)) {
                continue;
            }

            $name = Html::encode((string)($field['name'] ?? 'field'));
            $label = Html::encode((string)($field['label'] ?? ucfirst($name)));
            $type = FormSystemFieldHelper::resolveFieldInputType($field);
            $placeholder = Html::encode((string)($field['placeholder'] ?? ''));
            $required = !empty($field['required']) ? ' *' : '';

            if ($type === 'textarea') {
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><textarea ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" placeholder="' . $placeholder . '" style="width:100%;min-height:70px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"></textarea></div>';
                continue;
            }
            if ($type === 'select') {
                $fieldHtml .= '<div style="margin-bottom:10px;"><label style="display:block;font-size:12px;color:#334155;margin-bottom:4px;">' . $label . $required . '</label><select ' . ($interactive ? '' : 'disabled') . ' name="' . $name . '" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;"><option value="">Pilih...</option></select></div>';
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
