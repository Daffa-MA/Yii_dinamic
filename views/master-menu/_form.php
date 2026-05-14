<?php

use app\models\Form;
use app\models\MasterMenu;
use app\models\MasterForm;
use app\models\MasterPage;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;
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

// Get pages that are already assigned to other menus (for this project)
$assignedPageIds = [];
$assignedPageMenus = [];
try {
    $assignedMenus = \app\models\MasterMenu::find()
        ->select(['page_id', 'name'])
        ->where(['type' => 'page', 'is_active' => 1])
        ->andWhere(['!=', 'id', $model->id ?? 0])
        ->andWhere(['not', ['page_id' => null]])
        ->all();
    foreach ($assignedMenus as $am) {
        if ($am->page_id) {
            $assignedPageIds[] = $am->page_id;
            $assignedPageMenus[$am->page_id] = $am->name;
        }
    }
} catch (\Exception $e) {
    // Ignore
}

$pageList = ['' => 'Pilih Halaman...'];
foreach ($pages as $page) {
    $pageAttrs = $page instanceof \yii\db\ActiveRecord ? $page->getAttributes() : (array) $page;
    $pageId = (int)$pageAttrs['id'];
    $pageTitle = $pageAttrs['title'] ?? $pageAttrs['name'] ?? 'Page ' . $pageId;
    
    // Mark pages that are already assigned to another menu
    if (in_array($pageId, $assignedPageIds) && $pageId !== (int)$model->page_id) {
        $pageList[$pageId] = $pageTitle . ' (Sudah dipakai: ' . ($assignedPageMenus[$pageId] ?? 'menu lain') . ')';
    } else {
        $pageList[$pageId] = $pageTitle;
    }
}

// Get forms that are already assigned
$assignedFormIds = [];
$assignedFormMenus = [];
try {
    $assignedFormMenusQuery = \app\models\MasterMenu::find()
        ->alias('m')
        ->select(['m.form_id', 'm.name'])
        ->innerJoin(['f' => Form::tableName()], 'f.id = m.form_id')
        ->where(['m.type' => 'form', 'm.is_active' => 1])
        ->andWhere(['!=', 'm.id', $model->id ?? 0])
        ->andWhere(['not', ['m.form_id' => null]]);

    if (ProjectSchema::supportsProjectContext()) {
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        if ($activeProjectId !== null) {
            $assignedFormMenusQuery->andWhere(['f.project_id' => $activeProjectId]);
        }
    }

    $assignedFormMenusList = $assignedFormMenusQuery->all();
    foreach ($assignedFormMenusList as $am) {
        if ($am->form_id) {
            $assignedFormIds[] = $am->form_id;
            $assignedFormMenus[$am->form_id] = $am->name;
        }
    }
} catch (\Exception $e) {
    // Ignore
}

$typeList = [
    'group' => 'Group (Menu Induk/Dropdown)',
    'page' => 'Page (Link ke Halaman)',
    'form' => 'Form (Formulir Dinamis)',
    'route' => 'Route (URL Langsung)',
    'button' => 'Button (Tombol Aksi)',
    'divider' => 'Divider (Pemisah)',
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

<?php $form = ActiveForm::begin([
    'enableClientValidation' => false,
    'enableAjaxValidation' => false,
    'validateOnSubmit' => false,
]); ?>

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
            <li><strong>Form:</strong> Menu yang membuka formulir. Wajib pilih formulir.</li>
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
        <div class="relative">
            <!-- Icon Display Button -->
            <button type="button" id="icon-picker-btn" onclick="toggleIconPicker(event)" class="w-full flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-slate-900 transition hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                <span id="icon-display" class="material-symbols-outlined text-2xl text-slate-600"><?= $model->icon && isset($iconList[$model->icon]) ? $model->icon : 'apps' ?></span>
                <div class="flex-1 text-left">
                    <span id="icon-name-display" class="block text-sm font-medium text-slate-700"><?= $model->icon && isset($iconList[$model->icon]) ? $iconList[$model->icon] : 'Pilih icon menu...' ?></span>
                    <span class="block text-xs text-slate-500"><?= $model->icon ?: 'Klik untuk memilih' ?></span>
                </div>
                <span class="material-symbols-outlined text-slate-400">expand_more</span>
            </button>
            
            <!-- Icon Picker Dropdown -->
            <div id="icon-picker-dropdown" class="absolute top-full left-0 right-0 mt-2 z-50 hidden bg-white rounded-2xl border border-slate-200 shadow-lg">
                <!-- Search Bar -->
                <div class="border-b border-slate-200 p-4">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" id="icon-search" placeholder="Cari icon..." class="w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-4 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white" onkeyup="filterIconPicker(this.value)">
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

<style id="menu-type-styles">
/* Menu type field containers - highest specificity */
#page-field-container,
#form-field-container,
#route-field-container {
    transition: opacity 0.2s ease, max-height 0.3s ease;
    overflow: hidden;
}

#page-field-container.menu-hidden,
#form-field-container.menu-hidden,
#route-field-container.menu-hidden {
    display: none !important;
    max-height: 0;
    opacity: 0;
}

/* Info box for assigned items */
.assigned-info {
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 12px;
    font-size: 13px;
    color: #92400e;
}

.assigned-info strong {
    color: #b45309;
}
</style>

<!-- Page Field - Only for Page type -->
    <div id="page-field-container" class="<?= $model->type !== 'page' ? 'menu-hidden' : '' ?>">
        <?php if (!empty($assignedPageMenus)): ?>
        <div class="assigned-info">
            <strong>Info:</strong> Halaman yang sudah dipakai menu lain akan menampilkan nama menu pemiliknya.
            <br>Setiap halaman hanya bisa dipakai oleh SATU menu.
        </div>
        <?php endif; ?>
        <?= $form->field($model, 'page_id')->dropDownList($pageList, [
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('Pilih Halaman', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    </div>

    <!-- Form Field - Only for Form type -->
    <?php
    $formList = ['' => 'Pilih Formulir...'];
    try {
        $forms = MasterForm::getActiveForms();
        foreach ($forms as $f) {
            $formId = (int)$f->id;
            // Mark forms that are already assigned
            if (in_array($formId, $assignedFormIds) && $formId !== (int)$model->form_id) {
                $formList[$formId] = $f->form_name . ' (Sudah dipakai: ' . ($assignedFormMenus[$formId] ?? 'menu lain') . ')';
            } else {
                $formList[$formId] = $f->form_name;
            }
        }
    } catch (\Exception $e) {
        Yii::warning('Error loading MasterForm (try 1): ' . $e->getMessage());
        try {
            $forms = \Yii::$app->db->createCommand("SELECT id, form_name FROM master_form WHERE is_active = 1 ORDER BY form_name")->queryAll();
            foreach ($forms as $f) {
                $formList[(int)$f['id']] = $f['form_name'];
            }
        } catch (\Exception $e2) {
            Yii::warning('Error loading MasterForm (try 2): ' . $e2->getMessage());
        }
    }
    ?>
    <div id="form-field-container" class="<?= $model->type !== 'form' ? 'menu-hidden' : '' ?>">
        <?php if (!empty($assignedFormMenus)): ?>
        <div class="assigned-info">
            <strong>Info:</strong> Formulir yang sudah dipakai menu lain akan menampilkan nama menu pemiliknya.
            <br>Setiap formulir hanya bisa dipakai oleh SATU menu.
        </div>
        <?php endif; ?>
        <?= $form->field($model, 'form_id', [
            'options' => ['id' => 'form-field-wrapper']
        ])->dropDownList($formList, [
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('Pilih Formulir', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
        
        <!-- Debug: Show current form_id value and field name -->
        <div class="mt-2 text-xs text-slate-500">Debug: form_id = "<?= $model->form_id ?? 'null' ?>", type = "<?= $model->type ?? 'null' ?>", POST name = "MasterMenu[form_id]"</div>
        
        <!-- Also add a visible hidden input to ensure form_id is sent -->
        <?= Html::hiddenInput('MasterMenu[form_id_debug]', $model->form_id, ['id' => 'form-id-debug']) ?>
    </div>

    <!-- Route Field - Only for Route type -->
    <div id="route-field-container" class="<?= $model->type !== 'route' ? 'menu-hidden' : '' ?>">
        <?= $form->field($model, 'route')->textInput([
            'maxlength' => true,
            'placeholder' => '/site/dashboard',
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
        ])->label('URL Route', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    </div>

    <?= $form->field($model, 'is_active')->dropDownList([1 => 'Tampilkan di sidebar', 0 => 'Sembunyikan dari sidebar'], [
        'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
    ])->label('Status', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>
    
    <!-- Advanced Properties Section -->
    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <button type="button" onclick="toggleAdvancedProps()" class="flex w-full items-center justify-between rounded-lg bg-white p-3 text-left font-semibold text-slate-700 transition hover:bg-slate-100">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">tune</span>
                Konfigurasi Tampilan Lanjutan
            </span>
            <span id="advanced-toggle-icon" class="material-symbols-outlined transition-transform">expand_more</span>
        </button>
        
        <div id="advanced-props" class="mt-4 hidden space-y-6">
            <!-- Button Type Settings -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Button Settings (untuk tipe Button)</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    <?= $form->field($model, 'button_text')->textInput([
                        'placeholder' => 'Teks button',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Button Text', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'button_style')->dropDownList([
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'success' => 'Success',
                        'danger' => 'Danger',
                        'warning' => 'Warning',
                        'info' => 'Info',
                        'link' => 'Link',
                        'outline-primary' => 'Outline Primary',
                        'outline-secondary' => 'Outline Secondary',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Button Style', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'button_size')->dropDownList([
                        'sm' => 'Small',
                        'md' => 'Medium',
                        'lg' => 'Large',
                        'block' => 'Block (Full Width)',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Button Size', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'button_icon')->textInput([
                        'placeholder' => 'Icon name',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Button Icon', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <div class="flex items-center gap-2">
                        <?= $form->field($model, 'button_full_width')->checkbox([
                            'class' => 'w-4 h-4 text-blue-600 rounded border-slate-300',
                        ])->label('Full Width', ['class' => 'text-sm text-slate-700']) ?>
                    </div>
                </div>
            </div>
            
            <!-- Target & Action -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Target & Action</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    <?= $form->field($model, 'target')->dropDownList([
                        '_self' => 'Same Tab (_self)',
                        '_blank' => 'New Tab (_blank)',
                        '_modal' => 'Modal Popup',
                        '_ajax' => 'AJAX Load',
                        '_popup' => 'Popup Window',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Target Window', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'action_type')->dropDownList([
                        'link' => 'Link',
                        'modal' => 'Open Modal',
                        'ajax' => 'AJAX Request',
                        'form_submit' => 'Submit Form',
                        'download' => 'Download',
                        'javascript' => 'JavaScript',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Action Type', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'icon_position')->dropDownList([
                        'left' => 'Left',
                        'right' => 'Right',
                        'top' => 'Top',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Icon Position', ['class' => 'text-xs text-slate-500']) ?>
                </div>
            </div>
            
            <!-- Border & Styling -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Border & Styling</h4>
                <div class="grid gap-4 md:grid-cols-3">
                    <?= $form->field($model, 'border_style')->dropDownList([
                        'none' => 'None',
                        'solid' => 'Solid',
                        'dashed' => 'Dashed',
                        'dotted' => 'Dotted',
                        'double' => 'Double',
                        'groove' => 'Groove',
                        'ridge' => 'Ridge',
                        'inset' => 'Inset',
                        'outset' => 'Outset',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Border Style', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'border_width')->textInput([
                        'placeholder' => '1px',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Border Width', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'border_color')->textInput([
                        'type' => 'color',
                        'class' => 'w-full h-10 rounded-lg border border-slate-200',
                    ])->label('Border Color', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'border_position')->dropDownList([
                        'all' => 'All Sides',
                        'top' => 'Top',
                        'right' => 'Right',
                        'bottom' => 'Bottom',
                        'left' => 'Left',
                        'top-bottom' => 'Top & Bottom',
                        'left-right' => 'Left & Right',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Border Position', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'border_radius')->dropDownList([
                        'none' => 'None',
                        'sm' => 'Small (2px)',
                        'md' => 'Medium (4px)',
                        'lg' => 'Large (8px)',
                        'xl' => 'Extra Large (12px)',
                        'circle' => 'Circle (50%)',
                        'pill' => 'Pill',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Border Radius', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'border_radius_size')->textInput([
                        'placeholder' => 'e.g. 5px, 50%',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Custom Radius', ['class' => 'text-xs text-slate-500']) ?>
                </div>
            </div>
            
            <!-- CSS Customization -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Custom CSS</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    <?= $form->field($model, 'css_class')->textInput([
                        'placeholder' => 'custom-class another-class',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('CSS Class', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'css_style')->textarea([
                        'placeholder' => 'color: red; margin-top: 10px;',
                        'rows' => 2,
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono',
                    ])->label('Inline CSS', ['class' => 'text-xs text-slate-500']) ?>
                </div>
            </div>
            
            <!-- Badge & Tooltip -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Badge & Tooltip</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    <?= $form->field($model, 'badge_text')->textInput([
                        'placeholder' => 'New, 5, dll',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Badge Text', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'badge_style')->dropDownList([
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'success' => 'Success',
                        'danger' => 'Danger',
                        'warning' => 'Warning',
                        'info' => 'Info',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Badge Style', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'show_tooltip')->textInput([
                        'placeholder' => 'Teks tooltip',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Tooltip Text', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'tooltip_position')->dropDownList([
                        'top' => 'Top',
                        'bottom' => 'Bottom',
                        'left' => 'Left',
                        'right' => 'Right',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Tooltip Position', ['class' => 'text-xs text-slate-500']) ?>
                </div>
            </div>
            
            <!-- Animation -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Animation</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    <?= $form->field($model, 'animation_type')->dropDownList([
                        'none' => 'None',
                        'fade' => 'Fade',
                        'slide' => 'Slide',
                        'bounce' => 'Bounce',
                        'pulse' => 'Pulse',
                        'zoom' => 'Zoom',
                    ], [
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Animation Type', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'animation_duration')->textInput([
                        'type' => 'number',
                        'min' => 100,
                        'max' => 5000,
                        'placeholder' => '300',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Duration (ms)', ['class' => 'text-xs text-slate-500']) ?>
                </div>
            </div>
            
            <!-- Visibility -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Visibility</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    <?= $form->field($model, 'visibility_roles')->textInput([
                        'placeholder' => 'admin, user, guest',
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Visible for Roles', ['class' => 'text-xs text-slate-500']) ?>
                    
                    <?= $form->field($model, 'sort_priority')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                    ])->label('Sort Priority', ['class' => 'text-xs text-slate-500']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdvancedProps() {
    const props = document.getElementById('advanced-props');
    const icon = document.getElementById('advanced-toggle-icon');
    if (props.classList.contains('hidden')) {
        props.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        props.classList.add('hidden');
icon.style.transform = 'rotate(0deg)';
    }
}
</script>

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
// Simplified preview - using inline script instead
// $previewScript is disabled to avoid conflicts
// The preview is now handled by inline script in the button click handler
?>

<?php
$script = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    // Type change handler
    const typeSelect = document.getElementById('menu-type-select');
    const pageContainer = document.getElementById('page-field-container');
    const routeContainer = document.getElementById('route-field-container');
    const formContainer = document.getElementById('form-field-container');
    const pageSelect = document.getElementById('mastermenu-page_id');
    const routeInput = document.getElementById('mastermenu-route');
    const formSelect = document.getElementById('mastermenu-form_id');
    
    // Error message elements
    const pageError = pageContainer ? pageContainer.querySelector('.help-block') : null;
    const routeError = routeContainer ? routeContainer.querySelector('.help-block') : null;
    
    function updateFields() {
        const type = typeSelect.value;
        
        // Guard against null elements
        if (!pageContainer || !routeContainer || !formContainer) {
            console.error('Menu form containers not found:', {
                pageContainer: !!pageContainer,
                routeContainer: !!routeContainer,
                formContainer: !!formContainer
            });
            return;
        }
        
        // Clear validation states
        clearValidation();
        
        if (type === 'page') {
            pageContainer.classList.remove('menu-hidden');
            routeContainer.classList.add('menu-hidden');
            formContainer.classList.add('menu-hidden');
            if (routeInput) routeInput.value = '';
            if (formSelect) formSelect.value = '';
        } else if (type === 'route' || type === 'button') {
            pageContainer.classList.add('menu-hidden');
            routeContainer.classList.remove('menu-hidden');
            formContainer.classList.add('menu-hidden');
            if (pageSelect) pageSelect.value = '';
            if (formSelect) formSelect.value = '';
        } else if (type === 'form') {
            console.log('Switching to Form type - showing form container');
            pageContainer.classList.add('menu-hidden');
            routeContainer.classList.add('menu-hidden');
            formContainer.classList.remove('menu-hidden');
            if (pageSelect) pageSelect.value = '';
            if (routeInput) routeInput.value = '';
        } else if (type === 'divider') {
            pageContainer.classList.add('menu-hidden');
            routeContainer.classList.add('menu-hidden');
            formContainer.classList.add('menu-hidden');
            if (pageSelect) pageSelect.value = '';
            if (routeInput) routeInput.value = '';
            if (formSelect) formSelect.value = '';
        } else {
            // Group
            pageContainer.classList.add('menu-hidden');
            routeContainer.classList.add('menu-hidden');
            formContainer.classList.add('menu-hidden');
            if (pageSelect) pageSelect.value = '';
            if (routeInput) routeInput.value = '';
            if (formSelect) formSelect.value = '';
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
        } else if (type === 'route' || type === 'button') {
            // Button also requires URL
            if (!routeInput || !routeInput.value.trim()) {
                const msg = type === 'button' ? 'URL wajib diisi untuk tombol' : 'URL route wajib diisi untuk tipe Route';
                showFieldError(routeInput, msg);
                isValid = false;
            }
        } else if (type === 'form') {
            if (!formSelect || !formSelect.value) {
                showFieldError(formSelect, 'Pilih formulir untuk tipe Form');
                isValid = false;
            }
        }
        // Divider type doesn't need validation
        
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
        updateFields();
    }
    
    // Form validation on submit - only validate if type is 'form'
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const type = typeSelect ? typeSelect.value : '';
            
            // Get form_id value at SUBMIT TIME (not at page load)
            const formIdField = document.getElementById('mastermenu-form_id');
            const formSelectElement = formIdField ? formIdField : document.querySelector('#form-field-wrapper select');
            const formValue = formSelectElement ? formSelectElement.value : '';
            
            console.log('[MenuType] Submit check - type:', type);
            console.log('[MenuType] form_id element:', formSelectElement ? 'FOUND' : 'NOT FOUND');
            console.log('[MenuType] form_id value at submit:', formValue);
            
            // List all form inputs
            const allInputs = Array.from(document.querySelectorAll('form input, form select')).map(i => i.name + '=' + i.value);
            console.log('[MenuType] All form inputs:', allInputs.slice(0, 10));
            
            if (type === 'form') {
                if (!formValue) {
                    e.preventDefault();
                    showFieldError(formSelectElement, 'Pilih formulir untuk tipe Form');
                    console.error('[MenuType] Form validation failed - no form selected');
                }
            }
        });
    }
    
    // Form submission - allow default submission
});
JS;
$this->registerJs($script);
?>

<script>
// Standalone menu type handler - runs immediately
(function() {
    console.log('[MenuType] Script loaded, waiting for DOM...');
    
    function initMenuTypeHandler() {
        var typeSelect = document.getElementById('menu-type-select');
        var pageContainer = document.getElementById('page-field-container');
        var routeContainer = document.getElementById('route-field-container');
        var formContainer = document.getElementById('form-field-container');
        
        console.log('[MenuType] Elements found:', {
            typeSelect: !!typeSelect,
            pageContainer: !!pageContainer,
            routeContainer: !!routeContainer,
            formContainer: !!formContainer
        });
        
        if (!typeSelect || !pageContainer || !routeContainer || !formContainer) {
            console.error('[MenuType] Required elements not found');
            return;
        }
        
        function updateFields() {
            var type = typeSelect.value;
            console.log('[MenuType] Type changed to:', type);
            
            // Hide all containers first using menu-hidden class
            pageContainer.classList.add('menu-hidden');
            routeContainer.classList.add('menu-hidden');
            formContainer.classList.add('menu-hidden');
            
            // Show relevant container based on type
            if (type === 'page') {
                pageContainer.classList.remove('menu-hidden');
                console.log('[MenuType] Showing page field');
            } else if (type === 'route' || type === 'button') {
                routeContainer.classList.remove('menu-hidden');
                console.log('[MenuType] Showing route field');
            } else if (type === 'form') {
                formContainer.classList.remove('menu-hidden');
                console.log('[MenuType] Showing FORM field - Pilih Formulir');
            } else {
                console.log('[MenuType] No field container for type:', type);
            }
            
            // Debug: show all containers state
            console.log('[MenuType] Container states:', {
                page: !pageContainer.classList.contains('menu-hidden'),
                route: !routeContainer.classList.contains('menu-hidden'),
                form: !formContainer.classList.contains('menu-hidden')
            });
        }
        
        // Attach event listener
        typeSelect.addEventListener('change', updateFields);
        console.log('[MenuType] Event listener attached');
        
        // Initial call to sync UI with current value
        updateFields();
        console.log('[MenuType] Initial updateFields called');
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMenuTypeHandler);
    } else {
        initMenuTypeHandler();
    }
})();
</script>

<?php
// Icon Picker Inline Scripts
$iconScript = <<<ICONJS
window.toggleIconPicker = function(event) {
    if (event) event.preventDefault();
    const dropdown = document.getElementById('icon-picker-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
        
        // Auto-focus search on open
        if (!dropdown.classList.contains('hidden')) {
            setTimeout(() => {
                const search = document.getElementById('icon-search');
                if (search) search.focus();
            }, 100);
        }
    }
};

window.selectIcon = function(iconKey, iconName) {
    // Update hidden input
    const input = document.getElementById('mastermenu-icon');
    if (input) input.value = iconKey;
    
    // Update display
    const display = document.getElementById('icon-display');
    const nameDisplay = document.getElementById('icon-name-display');
    if (display) display.textContent = iconKey;
    if (nameDisplay) nameDisplay.textContent = iconName;
    
    // Close dropdown
    const dropdown = document.getElementById('icon-picker-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
    
    // Clear search
    const search = document.getElementById('icon-search');
    if (search) {
        search.value = '';
        window.filterIconPicker('');
    }
};

window.filterIconPicker = function(searchTerm) {
    const search = (searchTerm || '').toLowerCase();
    const buttons = document.querySelectorAll('#icon-grid .icon-option');
    let visibleCount = 0;
    
    buttons.forEach(btn => {
        const name = btn.dataset.name || '';
        const icon = btn.dataset.icon || '';
        const isMatch = name.includes(search) || icon.includes(search);
        
        btn.style.display = isMatch ? '' : 'none';
        if (isMatch) visibleCount++;
    });
    
    // Show no results message if needed
    let noResults = document.getElementById('icon-no-results');
    if (visibleCount === 0 && search) {
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.id = 'icon-no-results';
            noResults.className = 'col-span-4 py-8 text-center text-slate-500';
            noResults.textContent = 'Icon tidak ditemukan';
            const grid = document.getElementById('icon-grid');
            if (grid && grid.parentElement) {
                grid.parentElement.appendChild(noResults);
            }
        }
        if (noResults) noResults.style.display = 'block';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
};

// Initialize icon picker on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('icon-picker-btn');
    const dropdown = document.getElementById('icon-picker-dropdown');
    const search = document.getElementById('icon-search');
    
    // Add click handler to button
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.toggleIconPicker();
        });
    }
    
    // Add input handler to search
    if (search) {
        search.addEventListener('keyup', function() {
            window.filterIconPicker(this.value);
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
            if (search) {
                search.value = '';
                window.filterIconPicker('');
            }
        }
    });
});
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
}

#icon-grid .icon-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

#icon-grid .icon-option.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

#icon-grid .icon-option .material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.field-mastermenu-icon #icon-picker-btn {
    cursor: pointer;
    border-color: #e2e8f0;
    background-color: #ffffff;
}

.field-mastermenu-icon #icon-picker-btn:hover {
    border-color: #cbd5e1;
    background-color: #f8fafc;
}

.field-mastermenu-icon #icon-picker-btn:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
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

/* Preview Section */
#sidebar-preview .btn {
    transition: all 0.2s ease;
}

#sidebar-preview .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

#sidebar-preview a {
    transition: all 0.2s ease;
}

#sidebar-preview a:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Button Styles for Preview */
#button-preview .btn-primary { background-color: #3b82f6; color: white; }
#button-preview .btn-secondary { background-color: #6b7280; color: white; }
#button-preview .btn-success { background-color: #10b981; color: white; }
#button-preview .btn-danger { background-color: #ef4444; color: white; }
#button-preview .btn-warning { background-color: #f59e0b; color: white; }
#button-preview .btn-info { background-color: #06b6d4; color: white; }
#button-preview .btn-link { background-color: transparent; color: #3b82f6; text-decoration: underline; }
#button-preview .btn-outline-primary { background-color: transparent; border: 2px solid #3b82f6; color: #3b82f6; }
#button-preview .btn-outline-secondary { background-color: transparent; border: 2px solid #6b7280; color: #6b7280; }

/* Button Sizes */
#button-preview .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; }
#button-preview .btn-md { padding: 0.5rem 1rem; font-size: 1rem; }
#button-preview .btn-lg { padding: 0.75rem 1.5rem; font-size: 1.125rem; }
#button-preview .btn-block { width: 100%; }

/* Animation Keyframes for Preview */
@keyframes animate__fade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes animate__slide {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes animate__bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes animate__pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes animate__zoom {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* Advanced Properties */
#advanced-props {
    animation: slideDown 0.3s ease-out;
}

#advanced-toggle-icon {
    transition: transform 0.3s ease;
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
CSS;
$this->registerCss($css);
?>
