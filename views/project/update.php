<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var bool $isCommanderSuperAdmin */
/** @var string $databaseName */

use app\models\Project;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Edit Project';

$customDomain = trim((string)($project->custom_domain ?? ''));
$projectSlug = trim((string)($project->slug ?? ''));
$projectDomainSuffix = Project::getProjectDomainSuffix();
$projectDomainPrefix = trim((string)($project->custom_domain_prefix ?? ''));
if ($projectDomainPrefix === '') {
    $projectDomainPrefix = Project::extractProjectDomainPrefix($customDomain);
}
if ($projectDomainPrefix === '') {
    $projectDomainPrefix = $projectSlug !== '' ? $projectSlug : 'project';
}
$projectDomainPrefix = Project::normalizeDomainPrefix($projectDomainPrefix);
$domainPreview = $projectDomainPrefix . '.' . $projectDomainSuffix;
$domainUrl = 'https://' . $domainPreview;
$domainStatus = strtolower(trim((string)($project->domain_status ?? '')));
$isDomainActive = $customDomain !== '' && ($domainStatus === 'active' || $domainStatus === '');
$statusLabel = $isDomainActive ? 'Active' : 'Pending';
$statusClass = $isDomainActive ? 'status-active' : 'status-pending';
$databaseName = trim((string)($databaseName ?? ''));

$form = ActiveForm::begin([
    'id' => 'project-update-form',
    'fieldConfig' => [
        'template' => "{input}\n{error}",
        'errorOptions' => ['class' => 'field-error', 'tag' => 'div'],
    ],
]);
?>

<style>
    .project-edit-shell {
        min-height: calc(100vh - 72px);
        margin: -1.5rem -0.75rem 0;
        padding: 48px 18px 72px;
        background:
            radial-gradient(circle at 12% 8%, rgba(214, 177, 88, .22), transparent 30%),
            radial-gradient(circle at 92% 0%, rgba(35, 83, 73, .16), transparent 34%),
            linear-gradient(135deg, #f7f3ea 0%, #fbfaf7 44%, #eef3ef 100%);
        color: #171717;
    }

    .project-edit-wrap {
        width: min(1180px, 100%);
        margin: 0 auto;
    }

    .project-edit-top {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: flex-start;
        margin-bottom: 28px;
    }

    .project-breadcrumb {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #746f66;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .02em;
        margin-bottom: 12px;
    }

    .project-edit-title {
        font-size: clamp(34px, 5vw, 56px);
        letter-spacing: -.05em;
        line-height: .95;
        margin: 0 0 12px;
        font-weight: 850;
    }

    .project-edit-subtitle {
        margin: 0;
        color: #67635d;
        max-width: 620px;
        font-size: 16px;
        line-height: 1.65;
    }

    .soft-btn {
        border: 1px solid rgba(23, 23, 23, .12);
        background: rgba(255, 255, 255, .72);
        color: #171717;
        border-radius: 999px;
        padding: 11px 16px;
        font-weight: 750;
        text-decoration: none;
        box-shadow: 0 12px 34px rgba(31, 28, 22, .08);
        backdrop-filter: blur(16px);
        white-space: nowrap;
    }

    .project-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 22px;
        align-items: start;
    }

    .settings-card {
        border: 1px solid rgba(33, 31, 27, .1);
        border-radius: 30px;
        background: rgba(255, 255, 255, .82);
        box-shadow: 0 28px 90px rgba(31, 28, 22, .1);
        backdrop-filter: blur(18px);
        overflow: hidden;
    }

    .settings-card-body {
        padding: clamp(24px, 4vw, 38px);
    }

    .section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    .section-kicker {
        color: #8a6f2a;
        font-size: 12px;
        font-weight: 850;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .section-title {
        font-size: 22px;
        font-weight: 820;
        letter-spacing: -.03em;
        margin: 0 0 6px;
    }

    .section-copy {
        color: #716c64;
        margin: 0;
        line-height: 1.55;
    }

    .field-block {
        margin-bottom: 22px;
    }

    .field-label {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 9px;
        color: #25231f;
        font-weight: 780;
        font-size: 14px;
    }

    .project-edit-shell .form-control {
        border: 1px solid #ded8cb;
        background: #fffdfa;
        border-radius: 16px;
        min-height: 52px;
        color: #171717;
        font-weight: 650;
        box-shadow: none;
    }

    .project-edit-shell textarea.form-control {
        min-height: 132px;
        font-weight: 500;
        line-height: 1.6;
    }

    .project-edit-shell .form-control:focus {
        border-color: #1f5d4c;
        box-shadow: 0 0 0 4px rgba(31, 93, 76, .12);
    }

    .domain-composer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: stretch;
        border: 1px solid #ded8cb;
        border-radius: 18px;
        background: #fffdfa;
        overflow: hidden;
    }

    .domain-composer .form-control {
        border: 0;
        border-radius: 0;
        min-height: 58px;
    }

    .domain-suffix {
        display: flex;
        align-items: center;
        padding: 0 18px;
        border-left: 1px solid #e7e1d5;
        color: #68625a;
        font-weight: 800;
        background: #f5efe4;
        white-space: nowrap;
    }

    .helper-text {
        margin-top: 10px;
        color: #746f66;
        font-size: 13px;
        line-height: 1.5;
    }

    .domain-preview-card {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        margin-top: 16px;
        border: 1px solid #d9e3db;
        background: linear-gradient(135deg, #eef7f1, #fffdf8);
        border-radius: 22px;
        padding: 18px;
    }

    .domain-preview-label {
        color: #597266;
        font-size: 12px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .1em;
        margin-bottom: 6px;
    }

    .domain-preview-url {
        color: #133f34;
        font-weight: 850;
        word-break: break-all;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border-radius: 999px;
        border: 1px solid;
        padding: 8px 11px;
        font-weight: 850;
        font-size: 12px;
    }

    .status-pill::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: currentColor;
    }

    .status-active {
        color: #107348;
        background: #e9f8ef;
        border-color: #bfe8cf;
    }

    .status-pending {
        color: #9a6700;
        background: #fff7d9;
        border-color: #efd584;
    }

    .action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 28px;
    }

    .primary-action,
    .secondary-action,
    .ghost-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        min-height: 48px;
        padding: 0 18px;
        font-weight: 850;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .primary-action {
        background: #171717;
        color: #fff;
        box-shadow: 0 16px 42px rgba(23, 23, 23, .22);
    }

    .secondary-action {
        background: #1f5d4c;
        color: #fff;
        border-color: #1f5d4c;
    }

    .ghost-action {
        background: #fffdfa;
        color: #171717;
        border-color: #ded8cb;
    }

    .summary-card {
        position: sticky;
        top: 18px;
    }

    .summary-hero {
        padding: 26px;
        background:
            radial-gradient(circle at 92% 16%, rgba(214, 177, 88, .32), transparent 32%),
            linear-gradient(145deg, #173d34, #10251f);
        color: #fff;
        border-radius: 28px 28px 0 0;
    }

    .summary-avatar {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        background: rgba(255, 255, 255, .13);
        border: 1px solid rgba(255, 255, 255, .16);
        font-size: 24px;
        font-weight: 900;
        margin-bottom: 18px;
    }

    .summary-name {
        margin: 0 0 8px;
        font-size: 24px;
        font-weight: 850;
        letter-spacing: -.03em;
    }

    .summary-domain {
        color: rgba(255, 255, 255, .75);
        word-break: break-all;
        margin: 0;
        line-height: 1.5;
    }

    .summary-body {
        padding: 24px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        padding: 15px 0;
        border-bottom: 1px solid #eee8dd;
    }

    .summary-row:first-child {
        padding-top: 0;
    }

    .summary-row:last-child {
        border-bottom: 0;
    }

    .summary-label {
        color: #746f66;
        font-size: 13px;
        font-weight: 720;
    }

    .summary-value {
        color: #171717;
        font-size: 13px;
        font-weight: 850;
        text-align: right;
        word-break: break-word;
    }

    .quick-actions {
        display: grid;
        gap: 10px;
        margin-top: 22px;
    }

    .quick-actions a {
        width: 100%;
    }

    .danger-zone {
        margin-top: 22px;
        border: 1px solid #f0c9bd;
        background: #fff2ee;
        color: #8d2e16;
        border-radius: 22px;
        padding: 18px;
    }

    .danger-zone-title {
        font-weight: 850;
        margin-bottom: 6px;
    }

    .danger-zone p {
        margin: 0;
        line-height: 1.55;
        color: #984229;
    }

    .field-error {
        margin-top: 8px;
        color: #b42318;
        font-size: 13px;
        font-weight: 700;
    }

    @media (max-width: 991.98px) {
        .project-grid {
            grid-template-columns: 1fr;
        }

        .summary-card {
            position: static;
        }
    }

    @media (max-width: 575.98px) {
        .project-edit-top {
            display: block;
        }

        .soft-btn {
            display: inline-flex;
            margin-top: 18px;
        }

        .domain-composer {
            grid-template-columns: 1fr;
        }

        .domain-suffix {
            border-left: 0;
            border-top: 1px solid #e7e1d5;
            min-height: 46px;
        }

        .domain-preview-card {
            display: block;
        }

        .domain-preview-card .status-pill {
            margin-top: 14px;
        }
    }
</style>

<div class="project-edit-shell">
    <div class="project-edit-wrap">
        <div class="project-edit-top">
            <div>
                <div class="project-breadcrumb">
                    <span>Projects</span>
                    <span>/</span>
                    <span>Edit Project</span>
                </div>
                <h1 class="project-edit-title">Edit Project</h1>
                <p class="project-edit-subtitle">Kelola identitas, database, dan domain workspace ini.</p>
            </div>
            <?= Html::a('Back to Projects', ['project/index'], ['class' => 'soft-btn']) ?>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
        <?php endif; ?>

        <div class="project-grid">
            <main class="settings-card">
                <div class="settings-card-body">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Project Information</div>
                            <h2 class="section-title">Workspace identity</h2>
                            <p class="section-copy">Data ini dipakai untuk daftar project, nama database, dan domain workspace.</p>
                        </div>
                        <span class="status-pill <?= Html::encode($statusClass) ?>" id="domain-status-badge"><?= Html::encode($statusLabel) ?></span>
                    </div>

                    <div class="field-block">
                        <label class="field-label" for="project-name">Project Name</label>
                        <?= $form->field($project, 'name')->textInput([
                            'id' => 'project-name',
                            'class' => 'form-control',
                            'placeholder' => 'Nama project',
                        ])->label(false) ?>
                    </div>

                    <div class="field-block">
                        <label class="field-label" for="project-description">Description</label>
                        <?= $form->field($project, 'description')->textarea([
                            'id' => 'project-description',
                            'class' => 'form-control',
                            'rows' => 5,
                            'placeholder' => 'Deskripsi singkat workspace',
                        ])->label(false) ?>
                    </div>

                    <div class="field-block">
                        <label class="field-label" for="project-database-name">Database Name</label>
                        <input
                            type="text"
                            id="project-database-name"
                            class="form-control"
                            value="<?= Html::encode($databaseName !== '' ? $databaseName : '-') ?>"
                            readonly
                        >
                    </div>

                    <div class="section-head" style="margin-top: 34px;">
                        <div>
                            <div class="section-kicker">Domain Settings</div>
                            <h2 class="section-title">Workspace domain</h2>
                            <p class="section-copy">Ubah hanya bagian depan domain.</p>
                        </div>
                    </div>

                    <div class="field-block">
                        <label class="field-label" for="project-domain-prefix">
                            <span>Domain Prefix</span>
                            <span>[prefix] .<?= Html::encode($projectDomainSuffix) ?></span>
                        </label>

                        <?php if (!empty($isCommanderSuperAdmin)): ?>
                            <div class="domain-composer">
                                <input
                                    type="text"
                                    id="project-domain-prefix"
                                    name="Project[custom_domain_prefix]"
                                    class="form-control"
                                    value="<?= Html::encode($projectDomainPrefix) ?>"
                                    placeholder="sekolah-demo"
                                    autocomplete="off"
                                >
                                <span class="domain-suffix">.<?= Html::encode($projectDomainSuffix) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="domain-composer">
                                <input
                                    type="text"
                                    id="project-domain-prefix"
                                    class="form-control"
                                    value="<?= Html::encode($projectDomainPrefix) ?>"
                                    readonly
                                >
                                <span class="domain-suffix">.<?= Html::encode($projectDomainSuffix) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="helper-text">Superadmin cukup mengubah bagian depan domain. Bagian .<?= Html::encode($projectDomainSuffix) ?> otomatis dan tidak bisa diubah.</div>

                        <div class="domain-preview-card">
                            <div>
                                <div class="domain-preview-label">Live Preview</div>
                                <div class="domain-preview-url" id="domain-preview-url">https://<?= Html::encode($domainPreview) ?></div>
                            </div>
                            <span class="status-pill <?= Html::encode($statusClass) ?>"><?= Html::encode($statusLabel) ?></span>
                        </div>
                    </div>

                    <div class="action-row">
                        <button type="submit" class="primary-action">Save Changes</button>
                        <?= Html::a('Open Domain', $domainUrl, [
                            'class' => 'secondary-action',
                            'id' => 'open-domain-button',
                            'target' => '_blank',
                            'rel' => 'noopener',
                        ]) ?>
                        <?= Html::a('Back to Projects', ['project/index'], ['class' => 'ghost-action']) ?>
                    </div>
                </div>
            </main>

            <aside class="settings-card summary-card">
                <div class="summary-hero">
                    <div class="summary-avatar"><?= Html::encode(strtoupper(substr((string)$project->name, 0, 1)) ?: 'P') ?></div>
                    <h2 class="summary-name" id="summary-project-name"><?= Html::encode($project->name) ?></h2>
                    <p class="summary-domain" id="summary-domain">https://<?= Html::encode($domainPreview) ?></p>
                </div>

                <div class="summary-body">
                    <div class="section-kicker">Project Summary</div>
                    <div class="summary-row">
                        <div class="summary-label">Project name</div>
                        <div class="summary-value" id="summary-project-value"><?= Html::encode($project->name) ?></div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Database</div>
                        <div class="summary-value"><?= Html::encode($databaseName !== '' ? $databaseName : '-') ?></div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Domain</div>
                        <div class="summary-value" id="summary-domain-value"><?= Html::encode($domainPreview) ?></div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Status</div>
                        <div class="summary-value"><?= Html::encode($statusLabel) ?></div>
                    </div>

                    <div class="quick-actions">
                        <?= Html::a('Open Workspace', ['project/select', 'id' => $project->id], ['class' => 'secondary-action']) ?>
                        <?= Html::a('Open Domain', $domainUrl, [
                            'class' => 'ghost-action',
                            'id' => 'open-domain-sidebar',
                            'target' => '_blank',
                            'rel' => 'noopener',
                        ]) ?>
                        <?= Html::a('Back to Projects', ['project/index'], ['class' => 'ghost-action']) ?>
                    </div>

                    <div class="danger-zone">
                        <div class="danger-zone-title">Danger Zone</div>
                        <p>Mengubah domain dapat memengaruhi link yang sudah dibagikan.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php
$suffixJs = json_encode($projectDomainSuffix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$this->registerJs(<<<JS
(function () {
    const suffix = {$suffixJs};
    const prefixInput = document.getElementById('project-domain-prefix');
    const nameInput = document.getElementById('project-name');
    const previewUrl = document.getElementById('domain-preview-url');
    const summaryDomain = document.getElementById('summary-domain');
    const summaryDomainValue = document.getElementById('summary-domain-value');
    const summaryProjectName = document.getElementById('summary-project-name');
    const summaryProjectValue = document.getElementById('summary-project-value');
    const openDomainButton = document.getElementById('open-domain-button');
    const openDomainSidebar = document.getElementById('open-domain-sidebar');

    function normalizePrefix(value) {
        value = String(value || '').toLowerCase().trim();
        value = value.replace(/^https?:\\/\\//, '');
        value = value.replace(/\\/.*$/, '');
        if (suffix && value.endsWith('.' + suffix)) {
            value = value.slice(0, -('.' + suffix).length);
        }
        value = value.replace(/[^a-z0-9-\\s.]+/g, '-');
        value = value.replace(/[\\s_]+/g, '-');
        value = value.replace(/\\.+/g, '.');
        value = value.replace(/-+/g, '-');
        value = value.replace(/^[-.]+|[-.]+$/g, '');
        value = value.split('.')[0] || '';
        value = value.replace(/[^a-z0-9-]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
        if (/^[0-9]/.test(value)) {
            value = 'project-' + value;
        }
        return value.slice(0, 63);
    }

    function applyDomainPreview() {
        if (!prefixInput) {
            return;
        }

        const prefix = normalizePrefix(prefixInput.value) || 'project';
        const domain = prefix + '.' + suffix;
        const url = 'https://' + domain;

        previewUrl.textContent = url;
        summaryDomain.textContent = url;
        summaryDomainValue.textContent = domain;
        openDomainButton.href = url;
        openDomainSidebar.href = url;
    }

    if (prefixInput && !prefixInput.readOnly) {
        prefixInput.addEventListener('input', function () {
            const cursor = prefixInput.selectionStart;
            const normalized = normalizePrefix(prefixInput.value);
            if (prefixInput.value !== normalized) {
                prefixInput.value = normalized;
                if (cursor !== null) {
                    prefixInput.setSelectionRange(Math.min(cursor, normalized.length), Math.min(cursor, normalized.length));
                }
            }
            applyDomainPreview();
        });
    }

    if (nameInput) {
        nameInput.addEventListener('input', function () {
            const name = nameInput.value.trim() || 'Project';
            summaryProjectName.textContent = name;
            summaryProjectValue.textContent = name;
        });
    }

    applyDomainPreview();
})();
JS);
?>

<?php ActiveForm::end(); ?>
