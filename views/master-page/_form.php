<?php

use app\models\Form;
use app\models\MasterMenu;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MasterPage */
/* @var $form yii\widgets\ActiveForm */
/* @var $availableForms Form[] */

$availableForms = $availableForms ?? [];
$layoutTypes = $model::getLayoutOptions();

// Prepare parent menu options
$menuItems = MasterMenu::find()
    ->where(['is_active' => 1])
    ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
    ->all();
$parentMenuOptions = ['' => 'Root / Menu Utama'];
foreach ($menuItems as $menuItem) {
    $prefix = str_repeat('— ', (int)$menuItem->parent_id > 0 ? 1 : 0);
    $parentMenuOptions[(string)$menuItem->id] = $prefix . $menuItem->name;
}
?>

<?php $form = ActiveForm::begin(); ?>

<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-600">
        <p class="font-semibold text-slate-800">Alur yang dipakai admin</p>
        <p class="mt-1">1. Buat halaman dinamis. 2. Pilih satu atau beberapa form yang sudah benar-benar dibuat. 3. Simpan. 4. Hubungkan halaman itu ke menu di Master Menu. Kalau ingin dropdown sidebar, buat menu induk lalu tambahkan child menu di Master Menu.</p>
    </div>

    <?= $form->field($model, 'title')
        ->textInput([
            'maxlength' => true,
            'placeholder' => 'Contoh: Data Siswa, Nilai Siswa, Absensi Harian',
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10',
        ])
        ->label('Nama Halaman', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <?= $form->field($model, 'layout_type')
        ->dropDownList($layoutTypes, [
            'prompt' => 'Pilih layout halaman',
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10',
        ])
        ->label('Tipe Layout', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <?= $form->field($model, 'description')
        ->textarea([
            'rows' => 4,
            'placeholder' => 'Jelaskan tujuan halaman ini agar admin lain mudah memahami isi menunya.',
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10',
        ])
        ->label('Deskripsi', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <?= $form->field($model, 'route')
        ->textInput([
            'maxlength' => true,
            'placeholder' => '/site/dashboard atau /custom/page',
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10',
        ])
        ->label('Custom Route (URL)', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <p class="text-xs text-slate-500 -mt-3">isi jika ingin halaman ini langsung diakses via route ini.</p>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700" for="masterpage-formids">Form yang ditampilkan di halaman ini</label>
        <?php if (empty($availableForms)): ?>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Belum ada form yang bisa dipilih pada project aktif. Buat form dulu di menu <strong>Forms</strong>, lalu kembali ke halaman ini.
            </div>
        <?php else: ?>
            <?= Html::activeListBox($model, 'formIds', \yii\helpers\ArrayHelper::map($availableForms, 'id', static function (Form $formModel) {
                return $formModel->name;
            }), [
                'id' => 'masterpage-formids',
                'multiple' => true,
                'size' => min(max(count($availableForms), 6), 10),
                'class' => 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10',
            ]) ?>
            <p class="mt-2 text-xs text-slate-500">Gunakan Ctrl atau Cmd saat memilih lebih dari satu form. Semua form di sini adalah form asli yang sudah dibuat di project aktif.</p>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <?php foreach ($availableForms as $formModel): ?>
                    <?php
                    $isSelected = in_array((int) $formModel->id, array_map('intval', (array) $model->formIds), true);
                    $storageLabel = $formModel->storage_type === 'database' ? 'Database' : 'JSON';
                    ?>
                    <label class="flex items-start gap-3 rounded-2xl border <?= $isSelected ? 'border-indigo-300 bg-indigo-50/70' : 'border-slate-200 bg-white' ?> px-4 py-3">
                        <input
                            type="checkbox"
                            value="<?= (int) $formModel->id ?>"
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            <?= $isSelected ? 'checked' : '' ?>
                            data-sync-masterpage-form
                        >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900"><?= Html::encode($formModel->name) ?></span>
                            <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"><?= Html::encode($storageLabel) ?></span>
                            <span class="ml-2 text-[11px] text-slate-500">ID #<?= (int) $formModel->id ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?= $form->field($model, 'is_active')
        ->dropDownList([1 => 'Tampilkan / aktif', 0 => 'Simpan tapi nonaktif'], [
            'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3.5 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10',
        ])
        ->label('Status Halaman', ['class' => 'mb-1.5 block text-sm font-semibold text-slate-700']) ?>

    <!-- Connect to Menu Section -->
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
        <label class="flex items-center gap-3 cursor-pointer">
            <input 
                type="checkbox" 
                id="connect-to-menu-checkbox"
                class="mt-0.5 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span class="text-sm font-semibold text-slate-700">Hubungkan ke Menu Sidebar</span>
        </label>
        <p class="mt-1.5 ml-8 text-xs text-slate-500">Centang untuk membuat menu baru yang terhubung ke halaman ini.</p>
        
        <!-- Hidden inputs for form submission -->
        <input type="hidden" id="connect-to-menu-flag" name="connect_to_menu" value="0">
        <input type="hidden" id="menu-name-field" name="menu_name" value="">
        <input type="hidden" id="menu-parent-field" name="menu_parent_id" value="">
        
        <!-- Menu Connection Fields (Hidden by default) -->
        <div id="menu-connection-fields" class="mt-4 pl-1 hidden">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="menu-connection-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Menu</label>
                    <input 
                        type="text" 
                        id="menu-connection-name" 
                        placeholder="Contoh: Data Siswa"
                        class="menu-connection-field w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                    >
                </div>
                <div>
                    <label for="menu-connection-parent" class="mb-1.5 block text-sm font-semibold text-slate-700">Parent Menu</label>
                    <select 
                        id="menu-connection-parent" 
                        class="menu-connection-field w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                    >
                        <?php foreach ($parentMenuOptions as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Menu akan dibuat dengan tipe <strong>"Page"</strong> dan terhubung ke halaman ini.</p>
        </div>
    </div>
</div>

<div class="mt-8 flex items-center gap-3 border-t border-slate-100 pt-6">
    <?= Html::submitButton('Simpan Halaman', [
        'class' => 'rounded-xl bg-indigo-600 px-8 py-3.5 font-semibold text-white shadow-sm transition hover:bg-indigo-700',
    ]) ?>
    <?= Html::a('Batal', ['index'], [
        'class' => 'rounded-xl border border-slate-200 bg-white px-8 py-3.5 font-medium text-slate-600 no-underline transition hover:border-slate-300 hover:bg-slate-50',
    ]) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
$syncScript = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    const listBox = document.getElementById('masterpage-formids');
    if (!listBox) {
        return;
    }

    const syncListBoxFromCheckboxes = () => {
        const selectedIds = Array.from(document.querySelectorAll('[data-sync-masterpage-form]:checked')).map((input) => String(input.value));
        Array.from(listBox.options).forEach((option) => {
            option.selected = selectedIds.includes(option.value);
        });
    };

    document.querySelectorAll('[data-sync-masterpage-form]').forEach((input) => {
        input.addEventListener('change', syncListBoxFromCheckboxes);
    });

    listBox.addEventListener('change', function () {
        const selectedIds = Array.from(listBox.selectedOptions).map((option) => option.value);
        document.querySelectorAll('[data-sync-masterpage-form]').forEach((input) => {
            input.checked = selectedIds.includes(input.value);
        });
    });
    
    // Connect to Menu checkbox behavior
    const connectCheckbox = document.getElementById('connect-to-menu-checkbox');
    const menuConnectionFields = document.getElementById('menu-connection-fields');
    const menuConnectionName = document.getElementById('menu-connection-name');
    const menuConnectionParent = document.getElementById('menu-connection-parent');
    
    // Hidden inputs
    const connectFlag = document.getElementById('connect-to-menu-flag');
    const menuNameField = document.getElementById('menu-name-field');
    const menuParentField = document.getElementById('menu-parent-field');
    
    if (connectCheckbox && menuConnectionFields) {
        connectCheckbox.addEventListener('change', function() {
            if (this.checked) {
                menuConnectionFields.classList.remove('hidden');
                connectFlag.value = '1';
                // Auto-fill menu name from page title
                const pageTitleInput = document.getElementById('masterpage-title');
                if (pageTitleInput && pageTitleInput.value && !menuConnectionName.value) {
                    menuConnectionName.value = pageTitleInput.value;
                }
                menuConnectionName.focus();
            } else {
                menuConnectionFields.classList.add('hidden');
                connectFlag.value = '0';
            }
        });
        
        // Auto-fill menu name when page title changes
        const pageTitleInput = document.getElementById('masterpage-title');
        if (pageTitleInput && menuConnectionName) {
            pageTitleInput.addEventListener('input', function() {
                if (connectCheckbox.checked && !menuConnectionName.value) {
                    menuConnectionName.value = this.value;
                }
            });
        }
    }
    
    // Sync menu fields to hidden inputs before submit
    const pageForm = document.querySelector('form');
    if (pageForm) {
        pageForm.addEventListener('submit', function() {
            if (connectCheckbox && connectCheckbox.checked && menuConnectionName && menuParentField) {
                menuNameField.value = menuConnectionName.value;
                menuParentField.value = menuConnectionParent ? menuConnectionParent.value : '';
            }
        });
    }
});
JS;
$this->registerJs($syncScript, \yii\web\View::POS_END);
?>
