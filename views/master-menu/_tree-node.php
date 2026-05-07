<?php

use yii\helpers\Html;
use app\helpers\MasterMenuTreeBuilder;

/**
 * Tree node renderer untuk Master Menu list
 * 
 * @var $this yii\web\View
 * @var $item array Node item dengan model, level, hasChildren
 * @var $index int Index di dalam list
 */

$model = $item['model'];
$level = $item['level'];
$isRoot = $item['isRoot'];
$hasChildren = $item['hasChildren'];
$childCount = $item['childCount'];

// Indent calculation
$paddingLeft = $level * 24;
$indent = MasterMenuTreeBuilder::getSimpleIndent($level);
$icon = $model->icon ?: 'folder';
$statusClass = $model->is_active ? 'opacity-100' : 'opacity-50 line-through';
?>

<tr class="border-b border-gray-50 hover:bg-gray-50/50 transition <?= $statusClass ?>"
    data-menu-id="<?= Html::encode($model->id) ?>"
    data-level="<?= $level ?>">

    <!-- Sort Order -->
    <td class="px-4 py-3 text-gray-600">
        <span class="text-sm font-medium"><?= Html::encode($model->sort_order) ?></span>
    </td>

    <!-- Menu Name dengan hierarchy visual -->
    <td class="px-4 py-3">
        <div style="padding-left: <?= $paddingLeft ?>px;">
            <!-- Visual tree line untuk child items -->
            <?php if ($level > 0): ?>
                <span class="text-gray-300 mr-1" style="font-family: monospace;">
                    <?= $indent ?>
                </span>
            <?php endif; ?>

            <!-- Icon -->
            <span class="material-symbols-outlined inline-block mr-2 align-middle text-base text-gray-400">
                <?= Html::encode($icon) ?>
            </span>

            <!-- Name -->
            <span class="font-medium text-gray-900">
                <?= Html::encode($model->name) ?>
            </span>

            <!-- Child count badge jika punya children -->
            <?php if ($hasChildren && $isRoot): ?>
                <span class="inline-flex ml-2 items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600">
                    <?= $childCount ?> submenu
                </span>
            <?php endif; ?>
        </div>
    </td>

    <!-- Parent Info -->
    <td class="px-4 py-3 text-gray-600">
        <?php if (!$isRoot): ?>
            <?php
            $parent = $model->parent;
            if ($parent):
            ?>
                <div class="inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-400">subdirectory_arrow_right</span>
                    <span class="text-sm"><?= Html::encode($parent->name) ?></span>
                </div>
            <?php else: ?>
                <span class="text-gray-300">-</span>
            <?php endif; ?>
        <?php else: ?>
            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-600">
                Root Menu
            </span>
        <?php endif; ?>
    </td>

    <!-- Type Badge -->
    <td class="px-4 py-3">
        <?php
        $type = $model->type ?? 'group';
        $typeConfig = [
            'group' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Group'],
            'page' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Page'],
            'route' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Route'],
        ];
        $config = $typeConfig[$type] ?? $typeConfig['group'];
        ?>
        <span class="inline-flex items-center rounded-full <?= $config['bg'] ?> px-2.5 py-0.5 text-xs font-medium <?= $config['text'] ?>">
            <?= Html::encode($config['label']) ?>
        </span>
    </td>

    <!-- Halaman -->
    <td class="px-4 py-3">
        <?php
        $page = $model->page;
        if ($page):
        ?>
            <?= Html::a(
                Html::encode($page->title),
                ['/page/view', 'id' => $page->id],
                ['class' => 'font-medium text-blue-600 no-underline hover:underline text-sm', 'target' => '_blank']
            ) ?>
        <?php else: ?>
            <span class="text-gray-300 text-sm">-</span>
        <?php endif; ?>
    </td>

    <!-- Status Toggle -->
    <td class="px-4 py-3">
        <?= Html::beginForm(['toggle', 'id' => $model->id], 'post', ['style' => 'display:inline']) ?>
        <?= Html::submitButton(
            '<span class="material-symbols-outlined text-xl">' . ($model->is_active ? 'toggle_on' : 'toggle_off') . '</span>',
            ['class' => 'rounded-lg px-2 py-1 text-xs font-bold transition ' . ($model->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')]
        ) ?>
        <?= Html::endForm() ?>
    </td>

    <!-- Actions -->
    <td class="px-4 py-3 text-right">
        <div class="flex items-center justify-end gap-2">
            <?= Html::a(
                '<span class="material-symbols-outlined">edit</span>',
                ['update', 'id' => $model->id],
                ['class' => 'inline-flex rounded-lg p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition', 'title' => 'Edit']
            ) ?>
            <?= Html::a(
                '<span class="material-symbols-outlined">delete</span>',
                ['delete', 'id' => $model->id],
                [
                    'class' => 'inline-flex rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 transition',
                    'title' => 'Hapus',
                    'data-confirm' => 'Hapus menu ini?',
                    'data-method' => 'post',
                ]
            ) ?>
        </div>
    </td>
</tr>