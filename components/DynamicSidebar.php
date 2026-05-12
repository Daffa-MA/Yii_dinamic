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
                    'type' => $menu->type,
                    'icon' => $menu->icon ?: 'folder',
                    'url' => $url,
                    'page_id' => $menu->page_id,
                    'form_id' => $menu->form_id,
                    'route' => $menu->route,
                    'children' => !empty($children) ? $children : null,
                    'forms' => $forms,
                    
                    // New flexible properties
                    'target' => $menu->target ?? '_self',
                    'action_type' => $menu->action_type ?? 'link',
                    'button_text' => $menu->button_text ?? $menu->name,
                    'button_style' => $menu->button_style ?? 'primary',
                    'button_size' => $menu->button_size ?? 'md',
                    'button_icon' => $menu->button_icon,
                    'button_full_width' => (bool) ($menu->button_full_width ?? false),
                    'css_class' => $menu->css_class,
                    'css_style' => $menu->css_style,
                    'custom_html' => $menu->custom_html,
                    'badge_text' => $menu->badge_text,
                    'badge_style' => $menu->badge_style ?? 'primary',
                    'show_tooltip' => $menu->show_tooltip,
                    'tooltip_position' => $menu->tooltip_position ?? 'top',
                    'animation_type' => $menu->animation_type ?? 'none',
                    'animation_duration' => $menu->animation_duration ?? 300,
                    'icon_position' => $menu->icon_position ?? 'left',
                    'sort_priority' => $menu->sort_priority ?? 0,
                    'visibility_roles' => $menu->visibility_roles,
                    'is_button' => $menu->type === 'button',
                    'is_divider' => $menu->type === 'divider',
                    
                    // Border properties
                    'border_style' => $menu->border_style,
                    'border_width' => $menu->border_width,
                    'border_color' => $menu->border_color,
                    'border_position' => $menu->border_position ?? 'all',
                    'border_radius' => $menu->border_radius,
                    'border_radius_size' => $menu->border_radius_size,
                ];

                $branch[] = $node;
            }
        }

        // Sort by sort_priority
        usort($branch, function($a, $b) {
            return ($a['sort_priority'] ?? 0) <=> ($b['sort_priority'] ?? 0);
        });

        return $branch;
    }

    private function resolveUrl($menu)
    {
        // Debug: log menu info
        \Yii::info('resolveUrl - type: ' . ($menu->type ?? 'null') . ', form_id: ' . ($menu->form_id ?? 'null') . ', page_id: ' . ($menu->page_id ?? 'null') . ', route: ' . ($menu->route ?? 'null'), 'menu-url-debug');
        
        if ($menu->type === 'form' && !empty($menu->form_id)) {
            \Yii::info('resolveUrl - Returning form URL with id: ' . $menu->form_id, 'menu-url-debug');
            return ['/master-form/preview', 'id' => $menu->form_id];
        }
        if ($menu->type === 'page' && !empty($menu->page_id)) {
            return ['/page/view', 'id' => $menu->page_id];
        }
        if (!empty($menu->route)) {
            return '/' . ltrim($menu->route, '/');
        }
        
        // Default return # for unknown types
        return '#';
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
            // Skip divider type
            if (!empty($item['is_divider'])) {
                $html .= $indent . '<li class="divider"></li>' . "\n";
                continue;
            }

            // Check visibility based on roles
            if (!$this->isVisible($item)) {
                continue;
            }

            $hasChildren = !empty($item['children']);
            $isActive = $this->isActive($item, $currentRoute);
            $childActive = $hasChildren && $this->isChildActive($item['children'], $currentRoute);
            $openClass = ($isActive || $childActive) ? ' in' : '';
            $activeClass = $isActive ? ' active' : '';

            $iconHtml = '<span class="material-symbols-outlined">' . htmlspecialchars($item['icon']) . '</span>';
            $itemUrl = $item['url'] ? Url::to($item['url']) : '#';
            
            // Build extra attributes for the item
            $extraAttributes = $this->buildExtraAttributes($item);
            $customStyle = !empty($item['css_style']) ? ' style="' . htmlspecialchars($item['css_style']) . '"' : '';

            // Handle button type
            if (!empty($item['is_button'])) {
                $html .= $indent . '<li class="menu-button' . $activeClass . '">' . "\n";
                $html .= $indent . '    ' . $this->renderButton($item) . "\n";
                $html .= $indent . '</li>' . "\n";
                continue;
            }

            // Handle custom HTML
            if (!empty($item['custom_html'])) {
                $html .= $indent . '<li class="custom-menu-item' . $activeClass . '">' . "\n";
                $html .= $indent . '    ' . $item['custom_html'] . "\n";
                $html .= $indent . '</li>' . "\n";
                continue;
            }

            if ($hasChildren) {
                $html .= $indent . '<li class="treeview' . $openClass . '">' . "\n";
                $html .= $indent . '    <a href="#" ' . $extraAttributes . '>' . $iconHtml . '<span>' . htmlspecialchars($item['name']) . '</span>' . "\n";
                
                // Add badge if exists
                if (!empty($item['badge_text'])) {
                    $badgeClass = 'badge badge-' . ($item['badge_style'] ?? 'primary');
                    $html .= $indent . '        <span class="' . $badgeClass . '">' . htmlspecialchars($item['badge_text']) . '</span>' . "\n";
                }
                
                $html .= $indent . '        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>' . "\n";
                $html .= $indent . '    </a>' . "\n";
                $html .= $indent . '    <ul class="treeview-menu' . $openClass . '">' . "\n";
                $html .= $this->renderMenuItems($item['children'], $currentRoute, $level + 1);
                $html .= $indent . '    </ul>' . "\n";
                $html .= $indent . '</li>' . "\n";
            } else {
                $html .= $indent . '<li' . $activeClass . $customStyle . '>' . "\n";
                $html .= $indent . '    <a href="' . $itemUrl . '" ' . $extraAttributes . '>';
                
                // Handle icon position
                $iconPos = $item['icon_position'] ?? 'left';
                if ($iconPos === 'left') {
                    $html .= $iconHtml . ' ';
                }
                $html .= '<span>' . htmlspecialchars($item['name']) . '</span>';
                
                // Add badge if exists
                if (!empty($item['badge_text'])) {
                    $badgeClass = 'badge badge-' . ($item['badge_style'] ?? 'primary');
                    $html .= ' <span class="' . $badgeClass . '">' . htmlspecialchars($item['badge_text']) . '</span>';
                }
                
                if ($iconPos === 'right') {
                    $html .= ' ' . $iconHtml;
                }
                
                $html .= '</a>' . "\n";
                $html .= $indent . '</li>' . "\n";
            }
        }

        return $html;
    }

    private function renderButton($item): string
    {
        $url = $item['url'] ?? '#';
        $text = $item['button_text'] ?? $item['name'];
        $style = $item['button_style'] ?? 'primary';
        $size = $item['button_size'] ?? 'md';
        $target = $item['target'] ?? '_self';
        
        $classes = ['btn', 'btn-' . $style];
        if ($size !== 'md') {
            $classes[] = 'btn-' . $size;
        }
        if (!empty($item['button_full_width'])) {
            $classes[] = 'w-100';
        }
        if (!empty($item['css_class'])) {
            $classes[] = $item['css_class'];
        }
        
        $classStr = implode(' ', $classes);
        
        // Build button attributes
        $attrs = [
            'href' => $url,
            'class' => $classStr,
        ];
        
        // Handle target
        if ($target === '_blank') {
            $attrs['target'] = '_blank';
            $attrs['rel'] = 'noopener noreferrer';
        } elseif ($target === '_modal') {
            $attrs['data-toggle'] = 'modal';
            $attrs['data-target'] = $url;
        } elseif ($target === '_ajax') {
            $attrs['data-ajax'] = 'true';
        }
        
        // Handle action type
        if (!empty($item['action_type'])) {
            $attrs['data-action'] = $item['action_type'];
        }
        
        // Handle tooltip
        if (!empty($item['show_tooltip'])) {
            $position = $item['tooltip_position'] ?? 'top';
            $attrs['data-toggle'] = 'tooltip';
            $attrs['title'] = $item['show_tooltip'];
            $attrs['data-placement'] = $position;
        }
        
        // Handle animation
        if (!empty($item['animation_type']) && $item['animation_type'] !== 'none') {
            $duration = $item['animation_duration'] ?? 300;
            $attrs['class'] .= ' animate__animated animate__' . $item['animation_type'];
            $attrs['style'] = 'animation-duration: ' . $duration . 'ms';
        }
        
        // Handle border
        $borderCss = $this->buildBorderCss($item);
        if (!empty($borderCss)) {
            if (isset($attrs['style']) && !empty($attrs['style'])) {
                $attrs['style'] .= ' ' . $borderCss;
            } else {
                $attrs['style'] = $borderCss;
            }
        }
        
        // Build icon if exists
        $iconHtml = '';
        if (!empty($item['button_icon'])) {
            $iconHtml = '<span class="material-symbols-outlined">' . htmlspecialchars($item['button_icon']) . '</span> ';
        }
        
        // Build attributes string
        $attrStr = '';
        foreach ($attrs as $key => $value) {
            if ($key === 'style' && !empty($value)) {
                $attrStr .= ' style="' . htmlspecialchars($value) . '"';
            } elseif (!empty($value)) {
                $attrStr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        return '<a' . $attrStr . '>' . $iconHtml . htmlspecialchars($text) . '</a>';
    }

    private function buildExtraAttributes($item): string
    {
        $attrs = [];
        
        // Target
        $target = $item['target'] ?? '_self';
        if ($target === '_blank') {
            $attrs[] = 'target="_blank"';
            $attrs[] = 'rel="noopener noreferrer"';
        } elseif ($target === '_modal') {
            $attrs[] = 'data-toggle="modal"';
        } elseif ($target === '_ajax') {
            $attrs[] = 'data-ajax="true"';
        }
        
        // Action type
        if (!empty($item['action_type'])) {
            $attrs[] = 'data-action="' . htmlspecialchars($item['action_type']) . '"';
        }
        
        // Tooltip
        if (!empty($item['show_tooltip'])) {
            $position = $item['tooltip_position'] ?? 'top';
            $attrs[] = 'data-toggle="tooltip"';
            $attrs[] = 'title="' . htmlspecialchars($item['show_tooltip']) . '"';
            $attrs[] = 'data-placement="' . $position . '"';
        }
        
        // Animation
        if (!empty($item['animation_type']) && $item['animation_type'] !== 'none') {
            $duration = $item['animation_duration'] ?? 300;
            $attrs[] = 'class="animate__animated animate__' . htmlspecialchars($item['animation_type']) . '"';
            $attrs[] = 'style="animation-duration: ' . $duration . 'ms"';
        }
        
        // CSS class
        if (!empty($item['css_class'])) {
            // Append to existing class if any
            foreach ($attrs as $key => $attr) {
                if (strpos($attr, 'class="') === 0) {
                    $attrs[$key] = str_replace('class="', 'class="' . htmlspecialchars($item['css_class']) . ' ', $attr);
                    unset($item['css_class']); // Remove to avoid duplicate
                    break;
                }
            }
        }
        
        return implode(' ', $attrs);
    }

    private function buildBorderCss($item): string
    {
        $css = '';
        
        $style = $item['border_style'] ?? 'none';
        if ($style && $style !== 'none') {
            $width = $item['border_width'] ?? '1px';
            $color = $item['border_color'] ?? '#000000';
            $position = $item['border_position'] ?? 'all';
            
            $css .= $this->buildBorderProperty($position, $style, $width, $color) . ';';
        }
        
        $radius = $item['border_radius'] ?? '';
        if ($radius && $radius !== 'none') {
            $radiusValue = $this->getBorderRadiusValue($radius);
            $customSize = $item['border_radius_size'] ?? '';
            
            if (!empty($customSize)) {
                $css .= 'border-radius:' . $customSize . ';';
            } else {
                $css .= 'border-radius:' . $radiusValue . ';';
            }
        }
        
        return $css;
    }

    private function buildBorderProperty($position, $style, $width, $color): string
    {
        switch ($position) {
            case 'top':
                return 'border-top:' . $width . ' ' . $style . ' ' . $color;
            case 'right':
                return 'border-right:' . $width . ' ' . $style . ' ' . $color;
            case 'bottom':
                return 'border-bottom:' . $width . ' ' . $style . ' ' . $color;
            case 'left':
                return 'border-left:' . $width . ' ' . $style . ' ' . $color;
            case 'top-bottom':
                return 'border-top:' . $width . ' ' . $style . ' ' . $color . '; border-bottom:' . $width . ' ' . $style . ' ' . $color;
            case 'left-right':
                return 'border-left:' . $width . ' ' . $style . ' ' . $color . '; border-right:' . $width . ' ' . $style . ' ' . $color;
            default:
                return 'border:' . $width . ' ' . $style . ' ' . $color;
        }
    }

    private function getBorderRadiusValue($radius): string
    {
        $values = [
            'none' => '0',
            'sm' => '2px',
            'md' => '4px',
            'lg' => '8px',
            'xl' => '12px',
            'circle' => '50%',
            'pill' => '9999px',
        ];
        
        return $values[$radius] ?? '4px';
    }

    private function isVisible($item): bool
    {
        $roles = $item['visibility_roles'] ?? '';
        
        if (empty($roles)) {
            return true;
        }
        
        if (Yii::$app->user->isGuest) {
            return false;
        }
        
        // For now, just check if user is logged in and has matching roles
        // You can extend this with actual role checking
        return true;
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