<?php

use yii\helpers\Html;

/* @var $items array */
/* @var $project_id int */

foreach ($items as $item):
    $hasChildren = isset($item['children']) && count($item['children']) > 0;
    $isGroup = $item['type'] === 'group';
    
    if ($isGroup && $hasChildren):
        // Group with children - render as dropdown
?>
        <div class="menu-group">
            <button type="button" class="menu-toggle w-full flex items-center justify-between px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg group">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-gray-600">
                        <?= $item['icon'] ?: 'folder' ?>
                    </span>
                    <span class="font-medium"><?= Html::encode($item['name']) ?></span>
                </div>
                <span class="material-symbols-outlined text-gray-400 menu-arrow transition-transform">
                    chevron_right
                </span>
            </button>
            <div class="submenu hidden pl-6 mt-1 space-y-1">
                <?= $this->render('_menu_items', ['items' => $item['children'], 'project_id' => $project_id]) ?>
            </div>
        </div>
<?php 
    elseif ($isGroup):
        // Group without children - render as clickable (but will show message)
?>
        <a href="javascript:void(0)" 
           onclick="handleMenuClick(<?= $item['id'] ?>, 'group', '<?= Html::encode($item['name']) ?>')"
           class="flex items-center gap-3 px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
            <span class="material-symbols-outlined text-gray-400">
                <?= $item['icon'] ?: 'folder' ?>
            </span>
            <span class="font-medium"><?= Html::encode($item['name']) ?></span>
        </a>
<?php 
    elseif ($item['type'] === 'route'):
        // Route - render as actual link
?>
        <a href="<?= Html::encode($item['url']) ?>" 
           class="flex items-center gap-3 px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
            <span class="material-symbols-outlined text-gray-400">
                <?= $item['icon'] ?: 'link' ?>
            </span>
            <span class="font-medium"><?= Html::encode($item['name']) ?></span>
        </a>
<?php 
    elseif ($item['type'] === 'form'):
        // Form - resolve by dashboard handler, then redirect to dynamic form renderer
?>
        <a href="javascript:void(0)" 
           onclick="handleMenuClick(<?= $item['id'] ?>, 'form', '<?= Html::encode($item['name']) ?>')"
           class="flex items-center gap-3 px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
            <span class="material-symbols-outlined text-gray-400">
                <?= $item['icon'] ?: 'description' ?>
            </span>
            <span class="font-medium"><?= Html::encode($item['name']) ?></span>
        </a>
<?php 
    else:
        // Page - render as clickable via AJAX
?>
        <a href="javascript:void(0)" 
           onclick="handleMenuClick(<?= $item['id'] ?>, 'page', '<?= Html::encode($item['name']) ?>')"
           class="flex items-center gap-3 px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
            <span class="material-symbols-outlined text-gray-400">
                <?= $item['icon'] ?: 'description' ?>
            </span>
            <span class="font-medium"><?= Html::encode($item['name']) ?></span>
        </a>
<?php 
    endif;
    
endforeach;
