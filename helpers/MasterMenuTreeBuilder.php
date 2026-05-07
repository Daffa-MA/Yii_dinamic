<?php

namespace app\helpers;

use app\models\MasterMenu;

/**
 * Helper class untuk membangun struktur tree menu dari data flat
 * Khusus untuk halaman Master Menu (CRUD)
 */
class MasterMenuTreeBuilder
{
    /**
     * Build hierarchical tree dari flat data
     *
     * @param array $items Array of MasterMenu models
     * @return array Tree structure with root items and children
     */
    public static function buildTree($items)
    {
        $tree = [];
        $itemsById = [];

        // Create index by ID untuk quick lookup
        foreach ($items as $item) {
            $itemsById[$item->id] = $item;
        }

        // Build tree structure
        foreach ($items as $item) {
            if (empty($item->parent_id)) {
                // Root item (parent = null)
                $tree[] = [
                    'model' => $item,
                    'level' => 0,
                    'children' => [],
                ];
            }
        }

        // Attach children ke parent mereka
        foreach ($items as $item) {
            if (!empty($item->parent_id)) {
                self::attachToParent($tree, $item, $itemsById);
            }
        }

        return $tree;
    }
    /**
     * Attach item ke parent dalam tree
     *
     * @param array &$tree Reference to tree array
     * @param MasterMenu $item Item to attach
     * @param array $itemsById Index of all items
     */
    private static function attachToParent(&$tree, $item, $itemsById)
    {
        foreach ($tree as &$node) {
            if ($node['model']->id === $item->parent_id) {
                // Found parent
                $node['children'][] = [
                    'model' => $item,
                    'level' => 1,
                    'children' => [],
                ];
                return;
            }

            // Check in children recursively
            if (!empty($node['children'])) {
                self::attachToChildrenRecursive($node['children'], $item, $itemsById);
            }
        }
    }
    /**
     * Recursively attach item to nested children
     *
     * @param array &$children Reference to children array
     * @param MasterMenu $item Item to attach
     * @param array $itemsById Index of all items
     */
    private static function attachToChildrenRecursive(&$children, $item, $itemsById)
    {
        foreach ($children as &$node) {
            if ($node['model']->id === $item->parent_id) {
                // Found parent
                $node['children'][] = [
                    'model' => $item,
                    'level' => $node['level'] + 1,
                    'children' => [],
                ];
                return;
            }

            // Check in sub-children recursively
            if (!empty($node['children'])) {
                self::attachToChildrenRecursive($node['children'], $item, $itemsById);
            }
        }
    }
    /**
     * Flatten tree to array dengan info level
     * Digunakan untuk rendering template
     *
     * @param array $tree Tree structure
     * @return array Flat array dengan level info
     */
    public static function flattenTree($tree)
    {
        $result = [];

        foreach ($tree as $node) {
            $result[] = [
                'model' => $node['model'],
                'level' => $node['level'],
                'isRoot' => $node['level'] === 0,
                'hasChildren' => !empty($node['children']),
                'childCount' => count($node['children']),
            ];

            // Add children
            if (!empty($node['children'])) {
                $result = array_merge($result, self::flattenChildren($node['children']));
            }
        }

        return $result;
    }
    /**
     * Flatten children recursively
     *
     * @param array $children Children array
     * @return array Flattened children
     */
    private static function flattenChildren($children)
    {
        $result = [];

        foreach ($children as $node) {
            $result[] = [
                'model' => $node['model'],
                'level' => $node['level'],
                'isRoot' => false,
                'hasChildren' => !empty($node['children']),
                'childCount' => count($node['children']),
            ];

            if (!empty($node['children'])) {
                $result = array_merge($result, self::flattenChildren($node['children']));
            }
        }

        return $result;
    }
    /**
     * Get indent string untuk visual hierarchy
     *
     * @param int $level Nesting level
     * @param bool $isLast Apakah node ini adalah child terakhir
     * @return string HTML indent string
     */
    public static function getIndentHtml($level, $isLast = true)
    {
        if ($level === 0) {
            return '';
        }

        $indent = '';
        for ($i = 0; $i < $level; $i++) {
            $indent .= '<span class="tree-indent" style="display: inline-block; width: 24px;">';
            if ($i < $level - 1) {
                // Vertical line untuk level di atas
                $indent .= '<span style="display: inline-block; width: 100%; border-left: 1px solid #e5e7eb;"></span>';
            } else {
                // Branch line untuk level terakhir
                $indent .= '<span style="display: inline-block; width: 100%; border-left: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px;">─</span>';
            }
            $indent .= '</span>';
        }

        return $indent;
    }
    /**
     * Get indent spacing saja (lebih sederhana)
     *
     * @param int $level Nesting level
     * @return string HTML spacing
     */
    public static function getSimpleIndent($level)
    {
        if ($level === 0) {
            return '';
        }

        return str_repeat('&nbsp;&nbsp;&nbsp;', $level) . '↳&nbsp;';
    }
}
