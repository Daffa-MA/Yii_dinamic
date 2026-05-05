<?php

use app\models\MasterMenu;
use app\models\MasterPage;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MasterMenu */
/* @var $form yii\widgets\ActiveForm */
/* @var $menuItems app\models\MasterMenu[]|null */

$menuItems = $menuItems ?? MasterMenu::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
$parentList = ['' => 'Root / menu utama'];
foreach ($menuItems as $menuItem) {
    $attrs = $menuItem instanceof \yii\db\ActiveRecord ? $menuItem->getAttributes() : (array) $menuItem;
    $prefix = $attrs['parent_id'] ? 'Submenu - ' : '';
    $parentList[(string) $attrs['id']] = $prefix . ($attrs['name'] ?? 'Menu');
}

$pages = MasterPage::find()->orderBy(['id' => SORT_ASC])->all();
$pageList = ['' => 'Pilih Halaman...'];
foreach ($pages as $page) {
    $pageAttrs = $page instanceof \yii\db\ActiveRecord ? $page->getAttributes() : (array) $page;
    $pageList[$pageAttrs['id']] = $pageAttrs['title'] ?? 'Page ' . $pageAttrs['id'];
}

$typeList = [
    'group' => 'Group (Menu Induk/Dropdown)',
    'page' => 'Page (Link ke Halaman)',
    'route' => 'Route (URL Langsung)',
];

$iconList = [
    'dashboard' => 'Dashboard',
    'home' => 'Home',
    'settings' => 'Settings',
    'person' => 'User',
    'group' => 'Users',
    'description' => 'Form',
    'article' => 'Article',
    'folder' => 'Folder',
    'folder_open' => 'Folder Open',
    'insert_drive_file' => 'File',
    'image' => 'Image',
    'video_library' => 'Video',
    'build' => 'Tools',
    'analytics' => 'Analytics',
    'assessment' => 'Report',
    'inbox' => 'Inbox',
    'mail' => 'Mail',
    'shopping_cart' => 'Cart',
    'payment' => 'Payment',
    'inventory' => 'Inventory',
    'store' => 'Store',
    'notifications' => 'Notifications',
    'chat' => 'Chat',
    'help' => 'Help',
    'info' => 'Info',
    'check_circle' => 'Success',
    'schedule' => 'Schedule',
    'calendar_today' => 'Calendar',
    'visibility' => 'View',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'add' => 'Add',
    'search' => 'Search',
    'download' => 'Download',
    'upload' => 'Upload',
    'link' => 'Link',
    'share' => 'Share',
    'lock' => 'Lock',
    'public' => 'Public',
    'menu' => 'Menu',
    'list' => 'List',
    'grid_view' => 'Grid',
    'code' => 'Code',
    'terminal' => 'Terminal',
    'extension' => 'Extension',
    'widgets' => 'Widgets',
    'category' => 'Category',
    'pie_chart' => 'Pie Chart',
    'bar_chart' => 'Bar Chart',
    'timeline' => 'Timeline',
    'school' => 'School',
    'groups' => 'Groups',
    'event_available' => 'Attendance',
    'grade' => 'Grade',
];

$iconJson = json_encode($iconList);

// Default type
if ($model->isNewRecord && empty($model->type)) {
    $model->type = 'page';
}
?>

<?php $form = ActiveForm::begin(); ?>

<div class="space-y-6">
    <?= $form->field($model, 'type')->dropDownList($typeList, [
        'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        'id' => 'menu-type-select',
    ])->label('Tipe Menu', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-700">
        <p class="font-semibold">Ketentuan:</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            <li><strong>Group:</strong> Menu induk untuk dropdown. Tidak perlu pilih halaman atau route.</li>
            <li><strong>Page:</strong> Menu yang membuka halaman. Wajib pilih halaman.</li>
            <li><strong>Route:</strong> Menu dengan URL langsung. Wajib isi URL.</li>
        </ul>
    </div>

    <?= $form->field($model, 'name')->textInput([
        'maxlength' => true,
        'placeholder' => 'Contoh: Data Siswa, Nilai Siswa, Absensi',
        'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
    ])->label('Nama Menu', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <div class="grid gap-5 md:grid-cols-2">
        <?= $form->field($model, 'parent_id')->dropDownList($parentList, [
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('Parent Menu (Submenu)', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

        <?= $form->field($model, 'order')->textInput([
            'type' => 'number',
            'min' => 0,
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('Urutan', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    </div>

    <div class="field-mastermenu-icon">
        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Icon</label>
        <div class="relative">
            <?= Html::activeHiddenInput($model, 'icon', ['id' => 'mastermenu-icon-value']) ?>

            <div id="icon-selector-display" class="flex w-full cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-slate-300">
                <span id="icon-preview-display" class="material-symbols-outlined text-xl text-slate-400"><?= isset($model['icon']) ? $model['icon'] : 'apps' ?></span>
                <span id="icon-name-display" class="text-slate-400"><?= isset($model['icon']) ? ($iconList[$model['icon']] ?? $model['icon']) : 'Pilih icon menu' ?></span>
            </div>

            <div id="icon-picker-dropdown" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                <div class="border-b border-slate-100 p-3">
                    <input type="text" id="icon-search" placeholder="Cari icon..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                </div>
                <div id="icon-grid" class="grid max-h-64 grid-cols-6 gap-1 overflow-y-auto p-3"></div>
            </div>
        </div>
    </div>

    <!-- Page Field - Only for Page type -->
    <div id="page-field-container" class="<?= $model->type !== 'page' ? 'hidden' : '' ?>">
        <?= $form->field($model, 'page_id')->dropDownList($pageList, [
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('Pilih Halaman', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    </div>

    <!-- Route Field - Only for Route type -->
    <div id="route-field-container" class="<?= $model->type !== 'route' ? 'hidden' : '' ?>">
        <?= $form->field($model, 'route')->textInput([
            'maxlength' => true,
            'placeholder' => '/site/dashboard',
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('URL Route', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    </div>

    <?= $form->field($model, 'is_active')->dropDownList([1 => 'Tampilkan di sidebar', 0 => 'Sembunyikan dari sidebar'], [
        'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
    ])->label('Status', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
</div>

<div class="mt-8 flex items-center gap-3 border-t border-slate-100 pt-6">
    <?= Html::submitButton('Simpan Menu', [
        'class' => 'rounded-xl bg-blue-600 px-8 py-3.5 font-semibold text-white shadow-sm transition hover:bg-blue-700',
    ]) ?>
    <?= Html::a('Batal', ['index'], [
        'class' => 'rounded-xl border border-slate-200 bg-white px-8 py-3.5 font-medium text-slate-600 no-underline transition hover:border-slate-300 hover:bg-slate-50',
    ]) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
$script = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    // Type change handler
    const typeSelect = document.getElementById('menu-type-select');
    const pageContainer = document.getElementById('page-field-container');
    const routeContainer = document.getElementById('route-field-container');
    const pageSelect = document.getElementById('mastermenu-page_id');
    const routeInput = document.getElementById('mastermenu-route');
    
    // Error message elements
    const pageError = pageContainer.querySelector('.help-block');
    const routeError = routeContainer.querySelector('.help-block');
    
    function updateFields() {
        const type = typeSelect.value;
        
        // Clear validation states
        clearValidation();
        
        if (type === 'page') {
            pageContainer.classList.remove('hidden');
            routeContainer.classList.add('hidden');
            if (routeInput) routeInput.value = '';
        } else if (type === 'route') {
            pageContainer.classList.add('hidden');
            routeContainer.classList.remove('hidden');
            if (pageSelect) pageSelect.value = '';
        } else {
            // Group
            pageContainer.classList.add('hidden');
            routeContainer.classList.add('hidden');
            if (pageSelect) pageSelect.value = '';
            if (routeInput) routeInput.value = '';
        }
    }
    
    function clearValidation() {
        // Clear page field
        if (pageSelect) {
            pageSelect.classList.remove('border-red-500', 'focus:border-red-500');
            pageSelect.removeAttribute('aria-invalid');
        }
        // Clear route field  
        if (routeInput) {
            routeInput.classList.remove('border-red-500', 'focus:border-red-500');
            routeInput.removeAttribute('aria-invalid');
        }
        // Remove error messages
        document.querySelectorAll('.type-error-msg').forEach(el => el.remove());
    }
    
    function validateForm() {
        clearValidation();
        const type = typeSelect.value;
        let isValid = true;
        
        // Validate Name
        const nameInput = document.getElementById('mastermenu-name');
        if (!nameInput || !nameInput.value.trim()) {
            showFieldError(nameInput, 'Nama menu wajib diisi');
            isValid = false;
        }
        
        // Type-specific validation
        if (type === 'page') {
            if (!pageSelect || !pageSelect.value) {
                showFieldError(pageSelect, 'Pilih halaman untuk tipe menu Halaman');
                isValid = false;
            }
        } else if (type === 'route') {
            if (!routeInput || !routeInput.value.trim()) {
                showFieldError(routeInput, 'URL route wajib diisi untuk tipe Route');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    function showFieldError(input, message) {
        if (!input) return;
        input.classList.add('border-red-500', 'focus:border-red-500');
        input.setAttribute('aria-invalid', 'true');
        
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'type-error-msg help-block mt-1 text-sm text-red-600';
        errorDiv.textContent = message;
        input.parentElement.appendChild(errorDiv);
    }
    
    if (typeSelect) {
        typeSelect.addEventListener('change', updateFields);
    }
    
    // Icon picker
    const iconDisplay = document.getElementById('icon-selector-display');
    const iconDropdown = document.getElementById('icon-picker-dropdown');
    const iconSearch = document.getElementById('icon-search');
    const iconGrid = document.getElementById('icon-grid');
    const iconValue = document.getElementById('mastermenu-icon-value');
    const iconPreview = document.getElementById('icon-preview-display');
    const iconName = document.getElementById('icon-name-display');
    const icons = $iconJson;
    
    // Populate icon grid
    Object.entries(icons).forEach(([key, value]) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'flex aspect-square items-center justify-center rounded-lg p-2 text-xl transition hover:bg-slate-100';
        btn.innerHTML = key;
        btn.onclick = function() {
            iconValue.value = key;
            iconPreview.textContent = key;
            iconName.textContent = value;
            iconDropdown.classList.add('hidden');
        };
        iconGrid.appendChild(btn);
    });
    
    if (iconDisplay && iconDropdown) {
        iconDisplay.addEventListener('click', function() {
            iconDropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!iconDisplay.contains(e.target) && !iconDropdown.contains(e.target)) {
                iconDropdown.classList.add('hidden');
            }
        });
    }
    
    if (iconSearch) {
        iconSearch.addEventListener('input', function() {
            const search = this.value.toLowerCase();
            Array.from(iconGrid.children).forEach(btn => {
                btn.style.display = btn.textContent.includes(search) ? '' : 'none';
            });
        });
    }
    
    // Form submission validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                // Scroll to first error
                const firstError = document.querySelector('.type-error-msg');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});
JS;
$this->registerJs($script);