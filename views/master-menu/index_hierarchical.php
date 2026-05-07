<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $treeData array Flattened tree data */

$this->title = 'Master Menu';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);

// Register CSS untuk tree hierarchy
$this->registerCss(
    <<<CSS
.menu-hierarchy-table tbody tr {
    transition: background-color 0.2s ease;
}

.menu-hierarchy-table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.menu-item-root {
    background-color: rgba(249, 250, 251, 0.8);
    font-weight: 500;
}

.menu-item-child {
    font-size: 0.95rem;
    background-color: rgba(249, 250, 251, 0.3);
}

.tree-indent {
    color: #d1d5db;
    font-family: 'Courier New', monospace;
    line-height: 1.6;
}

.submenu-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background-color: #dbeafe;
    color: #0369a1;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}
CSS
);
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-blue-600">Master Data</p>
            <h1 class="text-2xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola struktur menu hierarki, urutan, dan tautan ke halaman.
                <span class="ml-2 inline-block text-xs text-blue-600">
                    <span class="material-symbols-outlined text-sm align-middle">info</span>
                    Menu induk (root) ditampilkan normal, submenu terindentasi di bawahnya
                </span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <?= Html::a(
                '<span class="material-symbols-outlined text-lg">add</span> Tambah Menu',
                ['create'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-gray-800']
            ) ?>
            <?= Html::a(
                '<span class="material-symbols-outlined text-lg">article</span> Master Halaman',
                ['/master-page/index'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 no-underline hover:border-gray-300 hover:bg-gray-50']
            ) ?>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="menu-hierarchy-table w-full">
                <!-- Header -->
                <thead class="bg-gray-50">
                    <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3 text-left">Urutan</th>
                        <th class="px-4 py-3 text-left">Menu (Hierarki)</th>
                        <th class="px-4 py-3 text-left">Parent</th>
                        <th class="px-4 py-3 text-left">Tipe</th>
                        <th class="px-4 py-3 text-left">Halaman</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody>
                    <?php if (empty($treeData)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">menu</span>
                                    <p class="text-lg font-semibold text-gray-600 mb-1">Belum ada menu</p>
                                    <p class="text-sm text-gray-500 mb-4">Buat menu pertama untuk menampilkan di sidebar.</p>
                                    <?= Html::a(
                                        '<span class="material-symbols-outlined text-lg">add</span> Tambah Menu',
                                        ['create'],
                                        ['class' => 'inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-gray-800']
                                    ) ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($treeData as $item): ?>
                            <?php
                            $model = $item['model'];
                            $level = $item['level'];
                            $isRoot = $item['isRoot'];
                            $hasChildren = $item['hasChildren'];
                            $childCount = $item['childCount'];
                            $icon = $model->icon ?? 'folder';
                            $statusClass = $model->is_active ? '' : 'opacity-50 line-through';
                            $rowClass = $isRoot ? 'menu-item-root' : 'menu-item-child';
                            ?>
                            <tr class="border-b border-gray-50 <?= $rowClass ?> <?= $statusClass ?>"
                                data-menu-id="<?= Html::encode($model->id) ?>"
                                data-level="<?= $level ?>">

                                <!-- Urutan -->
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">
                                        <?= Html::encode($model->sort_order) ?>
                                    </span>
                                </td>

                                <!-- Menu Name dengan Hierarchy -->
                                <td class="px-4 py-3">
                                    <div style="padding-left: <?= $level * 20 ?>px;" class="flex items-center gap-2">
                                        <!-- Tree line untuk children -->
                                        <?php if ($level > 0): ?>
                                            <span class="tree-indent" title="Submenu Level <?= $level ?>">
                                                <?php for ($i = 0; $i < $level; $i++): ?>
                                                    <span style="display: inline-block; width: 16px; text-align: center;">
                                                        <?= $i === $level - 1 ? '└' : '│' ?>
                                                    </span>
                                                <?php endfor; ?>
                                            </span>
                                        <?php endif; ?>

                                        <!-- Icon -->
                                        <span class="material-symbols-outlined text-base text-gray-400">
                                            <?= Html::encode($icon) ?>
                                        </span>

                                        <!-- Name -->
                                        <span class="font-medium text-gray-900">
                                            <?= Html::encode($model->name) ?>
                                        </span>

                                        <!-- Submenu count badge -->
                                        <?php if ($hasChildren && $isRoot): ?>
                                            <span class="submenu-badge">
                                                <span class="material-symbols-outlined text-xs">subdirectory_arrow_right</span>
                                                <?= $childCount ?> submenu
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Parent Info -->
                                <td class="px-4 py-3">
                                    <?php if ($isRoot): ?>
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-600">
                                            <span class="material-symbols-outlined text-xs mr-1">check_circle</span>
                                            Root Menu
                                        </span>
                                    <?php else: ?>
                                        <?php
                                        $parent = $model->parent;
                                        if ($parent):
                                        ?>
                                            <div class="inline-flex items-center gap-1 text-sm">
                                                <span class="material-symbols-outlined text-xs text-gray-400">subdirectory_arrow_right</span>
                                                <span class="text-gray-600"><?= Html::encode($parent->name) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-300 text-sm">-</span>
                                        <?php endif; ?>
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
                                            ['class' => 'inline-flex rounded-lg p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition', 'title' => 'Edit', 'data-title' => 'Edit: ' . $model->name]
                                        ) ?>
                                        <?= Html::a(
                                            '<span class="material-symbols-outlined">delete</span>',
                                            ['delete', 'id' => $model->id],
                                            [
                                                'class' => 'inline-flex rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 transition',
                                                'title' => 'Hapus',
                                                'data-confirm' => 'Hapus menu "' . $model->name . '"?',
                                                'data-method' => 'post',
                                            ]
                                        ) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Summary -->
        <?php if (!empty($treeData)): ?>
            <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3 text-xs text-gray-500">
                <span>
                    Total:
                    <strong class="text-gray-900">
                        <?= count($treeData) ?>
                    </strong>
                    menu
                    (
                    <strong class="text-gray-900">
                        <?= count(array_filter($treeData, function($item) { return $item['isRoot']; })) ?>
                    </strong>
                    root,
                    <strong class="text-gray-900">
                        <?= count(array_filter($treeData, function($item) { return !$item['isRoot']; })) ?>
                    </strong>
                    submenu
                    )
                </span>
                <span class="text-gray-400">Aktif:
                    <strong class="text-gray-900">
                        <?= count(array_filter($treeData, function($item) { return $item['model']->is_active; })) ?>
                    </strong>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Box -->
    <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="flex gap-3">
            <span class="material-symbols-outlined text-blue-600 flex-shrink-0">info</span>
            <div class="text-sm text-blue-900">
                <p class="font-semibold mb-1">Cara Membuat Hierarki Menu:</p>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    <li>Buat menu utama dengan <strong>parent = kosong</strong> (akan menjadi Root Menu)</li>
                    <li>Buat submenu dengan <strong>parent = pilih menu utama</strong> (akan tampil di bawah menu induk dengan indentasi)</li>
                    <li>Submenu akan otomatis ditampilkan di halaman ini dengan visual tree hierarchy</li>
                    <li>Gunakan field "Urutan" untuk mengatur posisi dalam grup yang sama</li>
                </ul>
            </div>
        </div>
    </div>
</div>