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
/* @var $pages app\models\MasterPage[] */

// Get menu items for parent dropdown
$menuItems = $menuItems ?? MasterMenu::find()->where(['is_active' => 1])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
$parentList = ['' => 'Root / menu utama'];
foreach ($menuItems as $menuItem) {
    $attrs = $menuItem instanceof \yii\db\ActiveRecord ? $menuItem->getAttributes() : (array) $menuItem;
    $prefix = isset($attrs['parent_id']) && $attrs['parent_id'] ? 'Submenu - ' : '';
    $parentList[(string) $attrs['id']] = $prefix . ($attrs['name'] ?? 'Menu');
}

// Get active pages for page selection
$pages = $pages ?? MasterPage::find()->where(['is_active' => 1])->orderBy(['id' => SORT_ASC])->all();
$pageList = ['' => 'Pilih Halaman...'];
foreach ($pages as $page) {
    $pageAttrs = $page instanceof \yii\db\ActiveRecord ? $page->getAttributes() : (array) $page;
    $pageList[(int)$pageAttrs['id']] = $pageAttrs['title'] ?? $pageAttrs['name'] ?? 'Page ' . $pageAttrs['id'];
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

// Ensure model has default type
if ($model->isNewRecord && empty($model->type)) {
    $model->type = 'page';
}
?>

<?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

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

        <?= $form->field($model, 'sort_order')->textInput([
            'type' => 'number',
            'min' => 0,
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('Urutan', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    </div>

    <div class="field-mastermenu-icon">
        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Icon</label>
        <?= Html::activeHiddenInput($model, 'icon') ?>

        <!-- Custom Icon Picker -->
        <div class="relative" id="icon-picker-container">
            <!-- Icon Display Button -->
            <button type="button" id="icon-picker-btn" class="w-full flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-slate-900 transition hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" style="cursor: pointer;">
                <span id="icon-display" class="material-symbols-outlined text-2xl text-slate-600"><?= $model->icon && isset($iconList[$model->icon]) ? $model->icon : 'apps' ?></span>
                <div class="flex-1 text-left">
                    <span id="icon-name-display" class="block text-sm font-medium text-slate-700"><?= $model->icon && isset($iconList[$model->icon]) ? $iconList[$model->icon] : 'Pilih icon menu...' ?></span>
                    <span class="block text-xs text-slate-500"><?= $model->icon ?: 'Klik untuk memilih' ?></span>
                </div>
                <span class="material-symbols-outlined text-slate-400" style="transition: transform 0.3s ease;">expand_more</span>
            </button>

            <!-- Icon Picker Dropdown -->
            <div id="icon-picker-dropdown" class="absolute top-full left-0 right-0 mt-2 z-50 hidden bg-white rounded-2xl border border-slate-200 shadow-lg">                <!-- Search Bar -->
                <div class="border-b border-slate-200 p-4">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" id="icon-search" placeholder="Cari icon..." class="w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-4 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white">
                    </div>
                </div>

                <!-- Icon Grid -->
                <div class="max-h-96 overflow-y-auto p-4">
                    <div id="icon-grid" class="grid grid-cols-4 gap-2">
                        <?php foreach ($iconList as $key => $name): ?>
                            <button type="button" onclick="selectIcon('<?= $key ?>', '<?= addslashes($name) ?>')" class="icon-option group flex flex-col items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 text-center transition hover:border-blue-500 hover:bg-blue-50" data-icon="<?= $key ?>" data-name="<?= strtolower($name) ?>" title="<?= $name ?>">
                                <span class="material-symbols-outlined text-3xl text-slate-600 group-hover:text-blue-600"><?= $key ?></span>
                                <span class="text-xs font-medium text-slate-700 group-hover:text-blue-700 line-clamp-2"><?= $name ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
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
        
        // Check if page dropdown has options (excluding the "choose" option)
        const pageOptions = pageSelect ? pageSelect.querySelectorAll('option:not([value=""])') : [];
        const hasPages = pageOptions.length > 0;
        
        // If type is page but no pages available, switch to route type
        if (type === 'page' && !hasPages) {
            typeSelect.value = 'route';
            alert('Tidak ada halaman aktif. Menu type diubah menjadi Route.');
            return true;
        }
        
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
    
    // Form submission - allow default submission
});
JS;
$this->registerJs($script);
?>

<?php
// Icon Picker Inline Scripts
$iconScript = <<<ICONJS
(function() {
    let isPickerOpen = false;

    // Toggle dropdown visibility
    function toggleDropdown() {
        const dropdown = document.getElementById('icon-picker-dropdown');
        if (!dropdown) return;
        
        isPickerOpen = !isPickerOpen;
        if (isPickerOpen) {
            dropdown.classList.remove('hidden');
            setTimeout(() => {
                const search = document.getElementById('icon-search');
                if (search) search.focus();
            }, 50);
        } else {
            dropdown.classList.add('hidden');
        }
    }

    // Filter icons based on search
    function filterIcons(searchTerm) {
        const search = (searchTerm || '').toLowerCase();
        const options = document.querySelectorAll('#icon-grid .icon-option');
        let visibleCount = 0;

        options.forEach(opt => {
            const name = opt.dataset.name || '';
            const icon = opt.dataset.icon || '';
            const match = name.includes(search) || icon.includes(search);
            
            opt.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
    }

    // Select icon
    function selectIconFn(iconKey, iconName) {
        // Update hidden input
        const input = document.getElementById('mastermenu-icon');
        if (input) input.value = iconKey;

        // Update display
        const display = document.getElementById('icon-display');
        if (display) display.textContent = iconKey;

        const nameDisplay = document.getElementById('icon-name-display');
        if (nameDisplay) nameDisplay.textContent = iconName;

        // Close dropdown
        const dropdown = document.getElementById('icon-picker-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
        
        isPickerOpen = false;

        // Clear search
        const search = document.getElementById('icon-search');
        if (search) {
            search.value = '';
            filterIcons('');
        }
    }

    // Wait for DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const btn = document.getElementById('icon-picker-btn');
        const dropdown = document.getElementById('icon-picker-dropdown');
        const search = document.getElementById('icon-search');
        const container = document.getElementById('icon-picker-container');

        if (!btn || !dropdown || !container) return;

        // Button click handler
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown();
        });

        // Search input handler
        if (search) {
            search.addEventListener('keyup', (e) => {
                e.stopPropagation();
                filterIcons(this.value);
            });

            search.addEventListener('input', (e) => {
                e.stopPropagation();
                filterIcons(e.target.value);
            });
        }

        // Prevent dropdown close on inside click
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target) && isPickerOpen) {
                dropdown.classList.add('hidden');
                if (search) {
                    search.value = '';
                    filterIcons('');
                }
                isPickerOpen = false;
            }
        });

        // Make icon option buttons clickable
        const iconButtons = document.querySelectorAll('#icon-grid .icon-option');
        iconButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const iconKey = btn.dataset.icon;
                const iconName = btn.dataset.name.charAt(0).toUpperCase() + btn.dataset.name.slice(1);
                selectIconFn(iconKey, iconName);
            });
        });
    }

    // Expose global function for onclick handlers
    window.selectIcon = function(iconKey, iconName) {
        selectIconFn(iconKey, iconName);
    };
})();
ICONJS;
$this->registerJs($iconScript);
?>

<?php
// Add custom CSS for icon picker
$css = <<<CSS
#icon-picker-dropdown {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#icon-grid .icon-option {
    transition: all 0.2s ease;
    cursor: pointer;
}

#icon-grid .icon-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

#icon-grid .icon-option:active {
    transform: translateY(0);
}

#icon-grid .icon-option.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

#icon-grid .icon-option .material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

#icon-picker-btn {
    cursor: pointer !important;
    user-select: none;
}

#icon-picker-btn:hover {
    border-color: #cbd5e1 !important;
    background-color: #f8fafc !important;
}

#icon-picker-btn:active {
    background-color: #f1f5f9 !important;
}

#icon-picker-btn:focus {
    border-color: #3b82f6 !important;
    outline: none !important;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
}

#icon-search {
    cursor: text;
}

#icon-search::placeholder {
    color: #94a3b8;
}

#icon-search:focus {
    background-color: #ffffff;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    font-size: 24px;
    display: inline-block;
    line-height: 1;
    text-transform: none;
    letter-spacing: normal;
    word-wrap: normal;
    white-space: nowrap;
    direction: ltr;
}
CSS;
$this->registerCss($css);
?>