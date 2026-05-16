<?php

namespace app\components;

use app\models\MasterForm;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\ProjectUser;
use Yii;
use yii\helpers\Inflector;
use yii\db\Query;

class ProjectPermissionRegistry
{
    public function ensureTables(): bool
    {
        $db = Yii::$app->db;
        $schema = $db->schema;
        return $schema->getTableSchema('roles', true) !== null
            && $schema->getTableSchema('permissions', true) !== null
            && $schema->getTableSchema('role_permissions', true) !== null;
    }

    public function syncMenuPermissions(MasterMenu $menu): array
    {
        if (!$this->ensureTables()) {
            return [];
        }

        $baseKey = $this->getMenuBaseKey($menu);
        $definitions = [
            $this->buildDefinition('builder.global.access', 'Builder Global Access', 'builder', 'Access builder workspace'),
            $this->buildDefinition('builder.palette.access', 'Builder Palette Access', 'builder', 'Show component palette'),
            $this->buildDefinition('builder.tools.access', 'Builder Tools Access', 'builder', 'Show builder toolbar/tools'),
            $this->buildDefinition('builder.drag.access', 'Builder Drag Access', 'builder', 'Allow drag and drop'),
            $this->buildDefinition('builder.actions.access', 'Builder Actions Access', 'builder', 'Allow save/preview actions'),
            $this->buildDefinition('builder.forms.access', 'Builder Form Access', 'builder', 'Show form component and form selector'),
            $this->buildDefinition("menu.{$baseKey}.view", "Menu {$this->humanize($menu->name)} - View", 'menu', 'View menu item'),
            $this->buildDefinition("menu.{$baseKey}.create", "Menu {$this->humanize($menu->name)} - Create", 'menu', 'Create child or action'),
            $this->buildDefinition("menu.{$baseKey}.edit", "Menu {$this->humanize($menu->name)} - Edit", 'menu', 'Edit menu item'),
            $this->buildDefinition("menu.{$baseKey}.delete", "Menu {$this->humanize($menu->name)} - Delete", 'menu', 'Delete menu item'),
        ];

        if ((string)$menu->type === MasterMenu::TYPE_ROUTE && !empty($menu->route)) {
            $routeKey = $this->getRouteBaseKey((string)$menu->route);
            $definitions[] = $this->buildDefinition("route.{$routeKey}.access", "Route {$this->humanize($menu->route)} - Access", 'route', 'Access dynamic route');
        }

        return $this->upsertDefinitions($definitions);
    }

    public function syncPagePermissions(MasterPage $page): array
    {
        if (!$this->ensureTables()) {
            return [];
        }

        $baseKey = $this->getPageBaseKey($page);
        $pageLabel = (string)($page->name ?? $page->title ?? $baseKey);
        $definitions = [
            $this->buildDefinition('builder.global.access', 'Builder Global Access', 'builder', 'Access builder workspace'),
            $this->buildDefinition('builder.palette.access', 'Builder Palette Access', 'builder', 'Show component palette'),
            $this->buildDefinition('builder.tools.access', 'Builder Tools Access', 'builder', 'Show builder toolbar/tools'),
            $this->buildDefinition('builder.drag.access', 'Builder Drag Access', 'builder', 'Allow drag and drop'),
            $this->buildDefinition('builder.actions.access', 'Builder Actions Access', 'builder', 'Allow save/preview actions'),
            $this->buildDefinition('builder.forms.access', 'Builder Form Access', 'builder', 'Show form component and form selector'),
            $this->buildDefinition("page.{$baseKey}.view", "Page {$this->humanize($pageLabel)} - View", 'page', 'View page'),
            $this->buildDefinition("builder.page.{$baseKey}.access", "Builder {$this->humanize($pageLabel)} - Access", 'builder', 'Access page builder'),
        ];

        foreach ($this->extractComponentTypesFromLayout($page->layout_json ?? null) as $componentType) {
            $definitions[] = $this->buildDefinition(
                "component.page.{$baseKey}.{$componentType}.view",
                "Component {$this->humanize($componentType)} on {$this->humanize($pageLabel)} - View",
                'builder',
                'Control component visibility in page builder'
            );
        }

        return $this->upsertDefinitions($definitions);
    }

    public function syncFormPermissions(MasterForm $form): array
    {
        if (!$this->ensureTables()) {
            return [];
        }

        $baseKey = $this->getFormBaseKey($form);
        $definitions = [
            $this->buildDefinition("form.{$baseKey}.view", "Form {$this->humanize($form->form_name)} - View", 'form', 'View form'),
            $this->buildDefinition("form.{$baseKey}.submit", "Form {$this->humanize($form->form_name)} - Submit", 'form', 'Submit form'),
            $this->buildDefinition("builder.form.{$baseKey}.access", "Builder {$this->humanize($form->form_name)} - Access", 'builder', 'Access form builder'),
            $this->buildDefinition('builder.forms.access', 'Builder Form Access', 'builder', 'Show form component and form selector'),
        ];

        foreach ($this->extractFieldTypesFromForm($form) as $fieldType) {
            $definitions[] = $this->buildDefinition(
                "component.form.{$baseKey}.{$fieldType}.view",
                "Form component {$this->humanize($fieldType)} on {$this->humanize($form->form_name)} - View",
                'builder',
                'Control form component visibility'
            );
        }

        return $this->upsertDefinitions($definitions);
    }

    public function syncRoutePermissions(string $route, ?string $label = null): array
    {
        if (!$this->ensureTables()) {
            return [];
        }

        $routeKey = $this->getRouteBaseKey($route);
        return $this->upsertDefinitions([
            $this->buildDefinition(
                "route.{$routeKey}.access",
                $label ?: "Route {$this->humanize($route)} - Access",
                'route',
                'Access route'
            ),
        ]);
    }

    public function canAccessPermissionKeys(array $permissionKeys, ?int $projectId = null): bool
    {
        $permissionService = new ProjectPermissionService();
        return $permissionService->canAccessPermissionKeys($permissionKeys, $projectId);
    }

    public function canAccessMenuNode(array $menu, ?int $projectId = null): bool
    {
        $permissionService = new ProjectPermissionService();
        return $permissionService->canAccessMenu($menu, $projectId);
    }

    public function canAccessPage(MasterPage $page, ?int $projectId = null): bool
    {
        $permissionService = new ProjectPermissionService();
        return $permissionService->canAccessPage($page, $projectId);
    }

    public function canAccessForm(MasterForm $form, ?int $projectId = null): bool
    {
        $permissionService = new ProjectPermissionService();
        return $permissionService->canAccessForm($form, $projectId);
    }

    public function filterMenuTree(array $tree, ?int $projectId = null): array
    {
        $filtered = [];

        foreach ($tree as $node) {
            $children = isset($node['children']) && is_array($node['children']) ? $this->filterMenuTree($node['children'], $projectId) : [];
            $node['children'] = !empty($children) ? $children : null;
            $node['has_children'] = !empty($children);

            if ($this->canAccessMenuNode($node, $projectId) || !empty($children)) {
                $filtered[] = $node;
            }
        }

        return $filtered;
    }

    public function filterPages(array $pages, ?int $projectId = null): array
    {
        return array_values(array_filter($pages, function ($page) use ($projectId) {
            return $page instanceof MasterPage ? $this->canAccessPage($page, $projectId) : true;
        }));
    }

    public function filterForms(array $forms, ?int $projectId = null): array
    {
        return array_values(array_filter($forms, function ($form) use ($projectId) {
            return $form instanceof MasterForm ? $this->canAccessForm($form, $projectId) : true;
        }));
    }

    public function filterPageState(array $state, ?string $pageKey = null, ?int $projectId = null): array
    {
        $filtered = [];
        $normalizedPageKey = $this->normalizeSegment($pageKey ?: 'page');

        foreach ($state as $block) {
            if (!is_array($block)) {
                continue;
            }

            $filteredBlock = $this->filterPageBlock($block, $normalizedPageKey, $projectId);
            if ($filteredBlock !== null) {
                $filtered[] = $filteredBlock;
            }
        }

        return $filtered;
    }

    private function filterPageBlock(array $block, string $pageKey, ?int $projectId = null): ?array
    {
        $type = $this->normalizeSegment((string)($block['type'] ?? ''));
        $props = isset($block['props']) && is_array($block['props']) ? $block['props'] : [];

        if ($type === '') {
            return $block;
        }

        if ($type === 'form') {
            $formId = (int)($props['formId'] ?? $props['form_id'] ?? 0);
            if ($formId > 0) {
                $form = MasterForm::findByIdScoped($formId);
                if ($form instanceof MasterForm && !$this->canAccessForm($form, $projectId)) {
                    return null;
                }
            }
        }

        $permissionKeys = [
            "component.{$pageKey}.{$type}.view",
            "builder.page.{$pageKey}.access",
        ];
        if (!$this->canAccessPermissionKeys($permissionKeys, $projectId)) {
            return null;
        }

        foreach (['children', 'items', 'blocks'] as $childKey) {
            if (!isset($block[$childKey]) || !is_array($block[$childKey])) {
                continue;
            }

            $childState = [];
            foreach ($block[$childKey] as $childBlock) {
                if (!is_array($childBlock)) {
                    continue;
                }
                $filteredChild = $this->filterPageBlock($childBlock, $pageKey, $projectId);
                if ($filteredChild !== null) {
                    $childState[] = $filteredChild;
                }
            }
            $block[$childKey] = $childState;
        }

        return $block;
    }

    private function buildDefinition(string $key, string $label, string $type, ?string $description = null): array
    {
        return [
            'permission_key' => $key,
            'label' => $label,
            'permission_type' => $type,
            'description' => $description,
        ];
    }

    private function upsertDefinitions(array $definitions): array
    {
        $db = Yii::$app->db;
        $saved = [];

        foreach ($definitions as $definition) {
            $key = strtolower(trim((string)($definition['permission_key'] ?? '')));
            $label = trim((string)($definition['label'] ?? $key));
            if ($key === '' || $label === '') {
                continue;
            }

            $payload = [
                'permission_key' => $key,
                'label' => $label,
                'permission_type' => strtolower(trim((string)($definition['permission_type'] ?? 'feature'))),
                'description' => trim((string)($definition['description'] ?? '')),
            ];

            $db->createCommand()->upsert('permissions', $payload, [
                'label' => $payload['label'],
                'permission_type' => $payload['permission_type'],
                'description' => $payload['description'],
                'updated_at' => date('Y-m-d H:i:s'),
            ])->execute();

            $saved[] = $key;
        }

        return $saved;
    }

    private function getMenuBaseKey(MasterMenu $menu): string
    {
        if (!empty($menu->menu_key)) {
            return $this->normalizeSegment((string)$menu->menu_key);
        }

        if (!empty($menu->route)) {
            return $this->getRouteBaseKey((string)$menu->route);
        }

        return $this->normalizeSegment((string)$menu->name);
    }

    private function getPageBaseKey(MasterPage $page): string
    {
        return $this->normalizeSegment((string)($page->slug ?: $page->title ?: $page->name ?: $page->id));
    }

    private function getFormBaseKey(MasterForm $form): string
    {
        return $this->normalizeSegment((string)($form->slug ?: $form->form_name ?: $form->id));
    }

    private function getRouteBaseKey(string $route): string
    {
        $route = trim($route);
        $route = preg_replace('/[?#].*$/', '', $route);
        $route = trim($route, '/');
        if ($route === '') {
            return 'root';
        }

        $segments = array_filter(array_map(function ($segment) {
            return $this->normalizeSegment($segment);
        }, explode('/', $route)));

        return implode('.', $segments) ?: 'route';
    }

    private function normalizeSegment(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('/[?#].*$/', '', $value);
        $value = str_replace(['\\', '/'], '-', $value);
        $value = Inflector::slug($value, '-');
        return $value !== '' ? $value : 'item';
    }

    private function humanize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[_.-]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return ucwords(trim($value));
    }

    private function extractComponentTypesFromLayout($layoutJson): array
    {
        $decoded = $this->decodeJsonValue($layoutJson);
        if (!is_array($decoded)) {
            return [];
        }

        $types = [];
        $walker = function ($value) use (&$walker, &$types): void {
            if (!is_array($value)) {
                return;
            }

            if (isset($value['type']) && is_string($value['type'])) {
                $types[] = $this->normalizeSegment($value['type']);
            }

            if (isset($value['component_type']) && is_string($value['component_type'])) {
                $types[] = $this->normalizeSegment($value['component_type']);
            }

            foreach ($value as $child) {
                if (is_array($child)) {
                    $walker($child);
                }
            }
        };

        $walker($decoded);
        return array_values(array_unique(array_filter($types)));
    }

    private function extractFieldTypesFromForm(MasterForm $form): array
    {
        $types = [];
        $formData = $form->getFormDataArray();
        foreach ($formData as $field) {
            if (!is_array($field)) {
                continue;
            }

            $type = $field['type'] ?? $field['field_type'] ?? $field['component_type'] ?? null;
            if (is_string($type) && $type !== '') {
                $types[] = $this->normalizeSegment($type);
            }
        }

        foreach ($form->fields as $fieldModel) {
            $fieldType = (string)($fieldModel->field_type ?? '');
            if ($fieldType !== '') {
                $types[] = $this->normalizeSegment($fieldType);
            }
        }

        return array_values(array_unique(array_filter($types)));
    }

    private function decodeJsonValue($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
