<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Project Settings';

$customDomain = trim((string)($project->custom_domain ?? ''));
$domainStatus = strtolower(trim((string)($project->domain_status ?? '')));
$statusLabel = $customDomain === ''
    ? 'Belum diatur'
    : ($domainStatus === 'active' ? 'Aktif' : ($domainStatus === 'error' ? 'Error' : 'Menunggu'));
$statusClass = $customDomain === ''
    ? 'bg-slate-100 text-slate-600 border-slate-200'
    : ($domainStatus === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200');

$form = ActiveForm::begin([
    'id' => 'project-settings-form',
    'fieldConfig' => [
        'template' => "{input}\n{error}",
        'errorOptions' => ['class' => 'mt-1 text-sm text-red-600', 'tag' => 'div'],
    ],
]);
?>

<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Project Settings</h1>
            <p class="text-secondary mb-0">Atur nama project dan custom domain untuk workspace ini.</p>
        </div>
        <?= Html::a('Kembali', ['project/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="mb-4">
                        <div class="text-uppercase small fw-bold text-secondary mb-2">Workspace</div>
                        <h2 class="h4 mb-1"><?= Html::encode($project->name) ?></h2>
                        <p class="text-secondary mb-0">Satu project, satu domain, satu workspace.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="project-name">Project Name</label>
                        <?= $form->field($project, 'name')->textInput([
                            'id' => 'project-name',
                            'class' => 'form-control form-control-lg',
                            'placeholder' => 'Nama project',
                        ])->label(false) ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="project-description">Description</label>
                        <?= $form->field($project, 'description')->textarea([
                            'id' => 'project-description',
                            'class' => 'form-control',
                            'rows' => 4,
                            'placeholder' => 'Deskripsi singkat workspace',
                        ])->label(false) ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="project-custom-domain">Custom Domain</label>
                        <?= $form->field($project, 'custom_domain')->textInput([
                            'id' => 'project-custom-domain',
                            'class' => 'form-control form-control-lg',
                            'placeholder' => 'contoh: testing.domain.com',
                            'autocomplete' => 'off',
                        ])->label(false) ?>
                        <div class="form-text">Isi domain atau subdomain yang diarahkan ke workspace ini.</div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Simpan</button>
                        <?= Html::a('Buka workspace', ['project/select', 'id' => $project->id], ['class' => 'btn btn-outline-primary btn-lg']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <div class="text-uppercase small fw-bold text-secondary mb-2">Domain</div>
                            <h3 class="h5 mb-1">Status domain</h3>
                        </div>
                        <span class="badge border <?= Html::encode($statusClass) ?>"><?= Html::encode($statusLabel) ?></span>
                    </div>

                    <div class="mb-3">
                        <div class="text-secondary small mb-1">Current mapping</div>
                        <div class="fw-semibold"><?= $customDomain !== '' ? Html::encode($customDomain) : 'Belum diatur' ?></div>
                    </div>

                    <div class="mb-3">
                        <div class="text-secondary small mb-1">Project ID</div>
                        <div class="fw-semibold">#<?= (int)$project->id ?></div>
                    </div>

                    <?php if (!empty($project->domain_verified_at)): ?>
                        <div class="mb-3">
                            <div class="text-secondary small mb-1">Verified at</div>
                            <div class="fw-semibold"><?= Html::encode((string)$project->domain_verified_at) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="rounded-3 p-3 bg-light border">
                        <div class="fw-semibold mb-2">Catatan</div>
                        <ul class="mb-0 ps-3 text-secondary">
                            <li>Custom domain diarahkan ke aplikasi yang sama.</li>
                            <li>Domain aktif akan membuka workspace project ini.</li>
                            <li>Jika kosong, project tetap bisa dibuka dari /project-list.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
