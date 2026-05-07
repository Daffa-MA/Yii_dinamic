<?php

use app\models\Form;
use app\models\MasterPage;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $page MasterPage */
/* @var $forms Form[] */

$this->title = $page->title;
$returnUrl = Url::to(['/page/view', 'id' => $page->id]);

$layoutClasses = 'grid gap-5';
if ($page->layout_type === MasterPage::LAYOUT_DASHBOARD) {
    $layoutClasses = 'grid gap-5 xl:grid-cols-2';
} elseif ($page->layout_type === MasterPage::LAYOUT_TWO_COLUMN) {
    $layoutClasses = 'grid gap-5 lg:grid-cols-2';
} elseif ($page->layout_type === MasterPage::LAYOUT_FORM) {
    $layoutClasses = 'grid gap-5';
} elseif ($page->layout_type === MasterPage::LAYOUT_LIST) {
    $layoutClasses = 'space-y-5';
} elseif ($page->layout_type === MasterPage::LAYOUT_BLANK) {
    $layoutClasses = 'space-y-4';
}
?>

<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6 overflow-hidden rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.14),_transparent_36%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)] md:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-indigo-700">
                    <span class="material-symbols-outlined text-base">dashboard_customize</span>
                    Dynamic Page
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl"><?= Html::encode($page->title) ?></h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600"><?= $page->description !== null && $page->description !== '' ? Html::encode($page->description) : 'Halaman ini menampilkan form dinamis yang sudah dipilih admin dari project aktif.' ?></p>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[360px]">
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Layout</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900"><?= Html::encode($page->layout_type) ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Form</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900"><?= count($forms) ?> item</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status</p>
                    <p class="mt-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $page->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                            <?= $page->isActive() ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php if (count($forms) > 1): ?>
            <div class="mt-5 flex flex-wrap gap-2">
                <?php foreach ($forms as $index => $formModel): ?>
                    <a href="#dynamic-form-card-<?= (int) $formModel->id ?>" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 no-underline transition hover:border-indigo-300 hover:text-indigo-700">
                        <span class="material-symbols-outlined text-sm">description</span>
                        <?= Html::encode($formModel->name ?: ('Form ' . ($index + 1))) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php 
    // Render layout_json from dynamic builder
    $layoutJson = $page->layout_json ?? '[]';
    $layoutData = json_decode($layoutJson, true);
    $hasBuilderContent = !empty($layoutData) && is_array($layoutData);
    ?>
    
    <?php if ($hasBuilderContent): ?>
        <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Konten Halaman</h2>
            <?php foreach ($layoutData as $item): ?>
                <?php
                $type = $item['type'] ?? '';
                $props = $item['props'] ?? [];
                
                // Render based on type
                switch ($type) {
                    case 'heading':
                        $level = $props['level'] ?? 'h1';
                        $text = $props['text'] ?? '';
                        $align = $props['alignment'] ?? 'left';
                        $fontSize = $props['fontSize'] ?? '32px';
                        $color = $props['color'] ?? '#1e293b';
                        echo "<{$level} style='text-align:{$align};font-size:{$fontSize};color:{$color};margin:1rem 0;'>{$text}</{$level}>";
                        break;
                        
                    case 'paragraph':
                        $text = $props['text'] ?? '';
                        $align = $props['alignment'] ?? 'left';
                        echo "<p style='text-align:{$align};margin:0.5rem 0;'>{$text}</p>";
                        break;
                        
                    case 'divider':
                        echo '<hr style="border:none;border-top:1px solid #e2e8f0;margin:1rem 0;">';
                        break;
                        
                    case 'spacer':
                        $height = $props['height'] ?? '24px';
                        echo "<div style='height:{$height};'></div>";
                        break;
                        
                    case 'image':
                        $src = $props['src'] ?? '';
                        $alt = $props['alt'] ?? '';
                        $width = $props['width'] ?? '100%';
                        if ($src) {
                            echo "<img src='{$src}' alt='{$alt}' style='max-width:{$width};height:auto;margin:1rem 0;' />";
                        }
                        break;
                        
                    case 'card':
                        $title = $props['title'] ?? '';
                        $content = $props['content'] ?? '';
                        echo '<div class="rounded-lg border border-slate-200 p-4 my-2">';
                        if ($title) echo "<h3 class='font-bold text-slate-900'>{$title}</h3>";
                        if ($content) echo "<p class='text-slate-600'>{$content}</p>";
                        echo '</div>';
                        break;
                        
                    default:
                        // Debug: show unknown type
                        echo "<!-- Unknown type: {$type} -->";
                }
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($forms) && !$hasBuilderContent): ?>
        <div class="rounded-[28px] border border-amber-200 bg-amber-50 px-6 py-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white text-amber-600 shadow-sm">
                <span class="material-symbols-outlined text-[30px]">inventory_2</span>
            </div>
            <h2 class="mt-5 text-xl font-bold text-slate-900">Belum ada form yang dipasang</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">Halaman ini sudah siap dipakai, tetapi admin belum memilih form yang ingin ditampilkan. Buka Master Halaman untuk menambahkan form asli ke halaman ini.</p>
            <div class="mt-5">
                <?= Html::a('Buka Master Halaman', ['/master-page/update', 'id' => $page->id], [
                    'class' => 'inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white no-underline transition hover:bg-slate-800',
                ]) ?>
            </div>
        </div>
    <?php elseif (count($forms) > 0): ?>
        <div class="<?= $layoutClasses ?>">
            <?php foreach ($forms as $index => $formModel): ?>
                <?php
                $embedUrl = Url::to(['/form/render', 'id' => $formModel->id, 'embedded' => 1, 'return_url' => $returnUrl]);
                $schemaCount = count($formModel->getSchema());
                $storageLabel = $formModel->storage_type === 'database' ? 'Database' : 'JSON';
                ?>
                <section id="dynamic-form-card-<?= (int) $formModel->id ?>" class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-100 bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex min-w-0 items-start gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                    <span class="material-symbols-outlined">description</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="truncate text-lg font-bold text-slate-900"><?= Html::encode($formModel->name) ?></h2>
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-700"><?= Html::encode($storageLabel) ?></span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">Form ke-<?= $index + 1 ?> di halaman ini. Submit akan kembali ke halaman ini agar admin tetap di konteks yang sama.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs text-slate-500 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-center">
                                    <div class="font-semibold text-slate-900">#<?= (int) $formModel->id ?></div>
                                    <div>Form ID</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-center">
                                    <div class="font-semibold text-slate-900"><?= $schemaCount ?></div>
                                    <div>Field</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-center col-span-2 sm:col-span-1">
                                    <a href="<?= Html::encode(Url::to(['/form/view', 'id' => $formModel->id])) ?>" class="font-semibold text-indigo-700 no-underline hover:underline">Detail Form</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50">
                        <iframe
                            src="<?= Html::encode($embedUrl) ?>"
                            class="dynamic-form-iframe block w-full border-0 bg-white"
                            data-dynamic-form-iframe
                            data-form-id="<?= (int) $formModel->id ?>"
                            title="<?= Html::encode($formModel->name) ?>"
                            loading="lazy"
                            style="min-height: 780px;"
                        ></iframe>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$iframeResizeScript = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    const resizeIframe = (iframe) => {
        if (!iframe || !iframe.contentWindow) {
            return;
        }

        try {
            const doc = iframe.contentWindow.document;
            if (!doc || !doc.body || !doc.documentElement) {
                return;
            }

            const bodyHeight = doc.body.scrollHeight || 0;
            const documentHeight = doc.documentElement.scrollHeight || 0;
            iframe.style.height = Math.max(bodyHeight, documentHeight, 780) + 'px';
        } catch (error) {
            iframe.style.height = '980px';
        }
    };

    document.querySelectorAll('[data-dynamic-form-iframe]').forEach((iframe) => {
        iframe.addEventListener('load', function () {
            resizeIframe(iframe);
            setTimeout(() => resizeIframe(iframe), 250);
            setTimeout(() => resizeIframe(iframe), 1000);
        });
    });
});
JS;
$this->registerJs($iframeResizeScript, \yii\web\View::POS_END);
?>
