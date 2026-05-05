<?php

namespace app\components;

use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\MasterForm;
use Yii;
use yii\base\Component;
use yii\helpers\Url;

class DynamicSidebar extends Component
{
    private $_menuCache = null;
    private $_formCache = [];

    public function getMenuTree()
    {
        if ($this->_menuCache !== null) {
            return $this->_menuCache;
        }

        $menus = MasterMenu::find()
            ->where(['is_active' => MasterMenu::STATUS_ACTIVE])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $this->_menuCache = $this->buildTree($menus);
        return $this->_menuCache;
    }

    private function buildTree($menus, $parentId = null)
    {
        $branch = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id == $parentId) {
                $children = $this->buildTree($menus, $menu->id);
                $url = $this->resolveUrl($menu);
                $forms = $this->getFormsForMenu($menu->id);

                $node = [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'icon' => $menu->icon ?: 'folder',
                    'url' => $url,
                    'page_id' => $menu->page_id,
                    'children' => !empty($children) ? $children : null,
                    'forms' => $forms,
                ];

                $branch[] = $node;
            }
        }

        return $branch;
    }

    private function resolveUrl($menu)
    {
        if ($menu->page_id) {
            return ['/page/view', 'id' => $menu->page_id];
        }
        return null;
    }

    private function getFormsForMenu($menuId)
    {
        if (isset($this->_formCache[$menuId])) {
            return $this->_formCache[$menuId];
        }

        $page = MasterPage::findOne(['master_menu' => ['id' => $menuId]]);
        if ($page) {
            $forms = MasterForm::find()
                ->where(['page_id' => $page->id, 'is_active' => 1])
                ->all();
            $this->_formCache[$menuId] = $forms;
            return $forms;
        }

        return [];
    }

    public function renderSidebar()
    {
        $tree = $this->getMenuTree();
        $currentRoute = $this->getCurrentRoute();

        return $this->renderMenuItems($tree, $currentRoute, 0);
    }

    private function renderMenuItems($items, $currentRoute, $level)
    {
        $html = '';
        $indent = str_repeat('    ', $level * 2);

        foreach ($items as $item) {
            $hasChildren = !empty($item['children']);
            $isActive = $this->isActive($item, $currentRoute);
            $childActive = $hasChildren && $this->isChildActive($item['children'], $currentRoute);
            $openClass = ($isActive || $childActive) ? ' in' : '';
            $activeClass = $isActive ? ' active' : '';

            $icon = '<span class="material-symbols-outlined">' . htmlspecialchars($item['icon']) . '</span>';
            $itemUrl = $item['url'] ? Url::to($item['url']) : '#';

            if ($hasChildren) {
                $html .= $indent . '<li class="treeview' . $openClass . '">' . "\n";
                $html .= $indent . '    <a href="#">' . $icon . '<span>' . htmlspecialchars($item['name']) . '</span>' . "\n";
                $html .= $indent . '        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>' . "\n";
                $html .= $indent . '    </a>' . "\n";
                $html .= $indent . '    <ul class="treeview-menu' . $openClass . '">' . "\n";
                $html .= $this->renderMenuItems($item['children'], $currentRoute, $level + 1);
                $html .= $indent . '    </ul>' . "\n";
                $html .= $indent . '</li>' . "\n";
            } else {
                $html .= $indent . '<li' . $activeClass . '>' . "\n";
                $html .= $indent . '    <a href="' . $itemUrl . '">' . $icon . '<span>' . htmlspecialchars($item['name']) . '</span></a>' . "\n";
                $html .= $indent . '</li>' . "\n";
            }
        }

        return $html;
    }

    private function getCurrentRoute()
    {
        $controller = Yii::$app->controller;
        if (!$controller) return '';
        return $controller->id . '/' . $controller->action->id;
    }

    private function isActive($item, $currentRoute)
    {
        if (!empty($item['url'])) {
            $route = $item['url'];
            if (is_array($route) && isset($route[0])) {
                $routeStr = trim($route[0], '/');
                if (strpos($currentRoute, $routeStr) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isChildActive($children, $currentRoute)
    {
        foreach ($children as $child) {
            if ($this->isActive($child, $currentRoute)) {
                return true;
            }
            if (!empty($child['children']) && $this->isChildActive($child['children'], $currentRoute)) {
                return true;
            }
        }
        return false;
    }

    public function getFlatMenu()
    {
        return MasterMenu::find()
            ->where(['is_active' => MasterMenu::STATUS_ACTIVE])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }

    public function getActiveMenus()
    {
        return MasterMenu::find()
            ->where(['is_active' => MasterMenu::STATUS_ACTIVE])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }
}