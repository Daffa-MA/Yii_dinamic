<?php

use yii\helpers\Html;
use yii\db\ActiveRecord;

/* @var $this yii\web\View */
/* @var $model app\models\MasterForm */

// Safe attributes
if ($model instanceof ActiveRecord) {
    $attrs = $model->getAttributes();
} else {
    $attrs = (array) $model;
}

$formName = $attrs['form_name'] ?? 'Form';
$formType = $attrs['form_type'] ?? '-';
$formData = $attrs['form_data'] ?? '';
if (is_array($formData)) {
    $formData = json_encode($formData, JSON_PRETTY_PRINT);
} elseif (!is_string($formData)) {
    $formData = '';
}
$formDataStr = (string)$formData;
$formId = $attrs['id'] ?? null;
$pageId = $attrs['page_id'] ?? null;
$isActive = isset($attrs['is_active']) && $attrs['is_active'] == 1;
$createdAt = $attrs['created_at'] ?? '-';
$updatedAt = $attrs['updated_at'] ?? '-';

$this->title = $formName;
$this->params['breadcrumbs'][] = ['label' => 'Master Forms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- Header - Muted Professional -->
<div class="bg-slate-900 py-10">
    <div class="max-w-5xl mx-auto px-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Master Form</p>
                <h1 class="mt-2 text-2xl font-bold text-white"><?= Html::encode($formName) ?></h1>
                <p class="mt-1 text-slate-500">Detail dan konfigurasi form</p>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Ubah', ['update', 'id' => $formId], [
                    'class' => 'px-4 py-2 bg-slate-800 text-slate-300 text-sm font-medium rounded-lg border border-slate-700 hover:bg-slate-700 transition'
                ]) ?>
                <?= Html::a('Hapus', ['delete', 'id' => $formId], [
                    'class' => 'px-4 py-2 bg-red-900/50 text-red-400 text-sm font-medium rounded-lg border border-red-800 hover:bg-red-900/70 transition',
                    'data' => [
                        'confirm' => 'Yakin ingin menghapus form ini?',
                        'method' => 'post',
                    ]
                ]) ?>
            </div>
        </div>
    </div>
</div>

<!-- Content -->
<div class="max-w-5xl mx-auto px-6 -mt-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        <!-- Main Info -->
        <div class="lg:col-span-2 bg-slate-50 rounded-xl border border-slate-200 p-5">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4">Informasi Form</h2>
            
            <div class="space-y-0">
                <div class="flex items-center justify-between py-3 border-b border-slate-200">
                    <span class="text-sm text-slate-500">ID</span>
                    <span class="text-slate-900 font-mono text-sm">#<?= $formId ?></span>
                </div>
                
                <div class="flex items-center justify-between py-3 border-b border-slate-200">
                    <span class="text-sm text-slate-500">Nama Form</span>
                    <span class="text-slate-900 font-medium"><?= Html::encode($formName) ?></span>
                </div>
                
                <div class="flex items-center justify-between py-3 border-b border-slate-200">
                    <span class="text-sm text-slate-500">Tipe Form</span>
                    <span class="px-2.5 py-0.5 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md">
                        <?= Html::encode($formType) ?>
                    </span>
                </div>
                
                <div class="flex items-center justify-between py-3 border-b border-slate-200">
                    <span class="text-sm text-slate-500">Status</span>
                    <?php if ($isActive): ?>
                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-md flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                            Aktif
                        </span>
                    <?php else: ?>
                        <span class="px-2.5 py-0.5 bg-slate-200 text-slate-500 text-xs font-semibold rounded-md flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                            Nonaktif
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center justify-between py-3 border-b border-slate-200">
                    <span class="text-sm text-slate-500">Dibuat</span>
                    <span class="text-slate-600 text-sm"><?= $createdAt ?></span>
                </div>
                
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-slate-500">Diupdate</span>
                    <span class="text-slate-600 text-sm"><?= $updatedAt ?></span>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4">Aksi Cepat</h2>
            
            <div class="space-y-2">
                <?= Html::a('Preview Form', ['/form/view', 'id' => $formId], [
                    'class' => 'flex items-center gap-2 w-full px-3 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm transition',
                    'target' => '_blank'
                ]) ?>
                
                <?= Html::a('Lihat Submission', ['/form/submissions', 'id' => $formId], [
                    'class' => 'flex items-center gap-2 w-full px-3 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm transition'
                ]) ?>
                
                <?= Html::a('Duplicate', ['clone', 'id' => $formId], [
                    'class' => 'flex items-center gap-2 w-full px-3 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm transition'
                ]) ?>
            </div>
        </div>
    </div>
    
    <!-- JSON -->
    <div class="mt-5 bg-slate-50 rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Data JSON</h2>
            <button onclick="copyJson()" class="text-xs text-slate-500 hover:text-slate-700 font-medium">
                Copy
            </button>
        </div>
        
        <div class="bg-slate-900 rounded-lg p-3 overflow-x-auto">
            <pre class="text-xs text-emerald-400 font-mono whitespace-pre-wrap break-all"><?= Html::encode($formDataStr) ?></pre>
        </div>
    </div>
</div>

<script>
function copyJson() {
    const pre = document.querySelector('pre');
    navigator.clipboard.writeText(pre.textContent).then(() => {
        alert('Copied!');
    });
}
</script>