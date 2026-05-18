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
        padding: 40px 18px 64px;
        background: #f8fafc;
        color: #0f172a;
    }

    .project-edit-wrap {
        width: min(1100px, 100%);
        margin: 0 auto;
    }

    .project-edit-top {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: center;
        margin-bottom: 24px;
    }

    .project-breadcrumb {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .project-edit-title {
        font-size: 30px;
        letter-spacing: 0;
        line-height: 1.2;
        margin: 0 0 8px;
        font-weight: 750;
        color: #111827;
    }

    .project-edit-subtitle {
        margin: 0;
        color: #64748b;
        max-width: 620px;
        font-size: 15px;
        line-height: 1.6;
    }

    .soft-btn {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #111827;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color .18s ease, border-color .18s ease;
    }

    .soft-btn:hover {
        background: #f9fafb;
        border-color: #cbd5e1;
        color: #111827;
    }

    .project-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 20px;
        align-items: start;
    }

    .settings-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        overflow: hidden;
    }

    .settings-card-body {
        padding: 28px;
    }

    .section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }

    .section-kicker {
        display: none;
    }

    .section-title {
        font-size: 17px;
        font-weight: 700;
        letter-spacing: 0;
        margin: 0 0 5px;
        color: #111827;
    }

    .section-copy {
        color: #64748b;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
    }

    .field-block {
        margin-bottom: 18px;
    }

    .field-label {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 7px;
        color: #111827;
        font-weight: 600;
        font-size: 13px;
    }

    .field-label span:last-child {
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
    }

    .project-edit-shell .form-control {
        border: 1px solid #dbe2ea;
        background: #ffffff;
        border-radius: 10px;
        min-height: 42px;
        color: #111827;
        font-size: 14px;
        font-weight: 450;
        box-shadow: none;
        transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .project-edit-shell .form-control::placeholder {
        color: #9ca3af;
        font-weight: 400;
    }

    .project-edit-shell textarea.form-control {
        min-height: 108px;
        font-weight: 400;
        line-height: 1.55;
        resize: vertical;
    }

    .project-edit-shell .form-control:focus {
        background: #ffffff;
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .10);
    }

    .domain-composer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: stretch;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        background: #ffffff;
        overflow: hidden;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .domain-composer:focus-within {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .10);
    }

    .domain-composer .form-control {
        border: 0;
        border-radius: 0;
        min-height: 42px;
    }

    .domain-composer .form-control:focus {
        box-shadow: none;
    }

    .domain-suffix {
        display: flex;
        align-items: center;
        padding: 0 13px;
        border-left: 1px solid #e5e7eb;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
        background: #f9fafb;
        white-space: nowrap;
    }

    .helper-text {
        margin-top: 8px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.45;
    }

    .form-section {
        padding-bottom: 24px;
        margin-bottom: 24px;
        border-bottom: 1px solid #e5e7eb;
    }

    .form-section:last-of-type {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: 0;
    }

    .domain-preview-card {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-top: 12px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        border-radius: 10px;
        padding: 11px 12px;
    }

    .domain-preview-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .domain-preview-url {
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        word-break: break-all;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        border: 1px solid;
        padding: 5px 8px;
        font-weight: 600;
        font-size: 12px;
        line-height: 1;
        white-space: nowrap;
    }

    .status-pill::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: currentColor;
    }

    .status-active {
        color: #047857;
        background: #ecfdf5;
        border-color: #bbf7d0;
    }

    .status-pending {
        color: #92400e;
        background: #fffbeb;
        border-color: #fde68a;
    }

    .action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
        padding-top: 22px;
        border-top: 1px solid #e5e7eb;
    }

    .primary-action,
    .secondary-action,
    .ghost-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        min-height: 42px;
        padding: 0 14px;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        border: 1px solid transparent;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease;
    }

    .primary-action {
        background: #111827;
        color: #fff;
        box-shadow: none;
    }

    .primary-action:hover {
        background: #1f2937;
        color: #fff;
    }

    .secondary-action {
        background: #4f46e5;
        color: #fff;
        border-color: #4f46e5;
    }

    .secondary-action:hover {
        background: #4338ca;
        color: #fff;
    }

    .ghost-action {
        background: #ffffff;
        color: #111827;
        border-color: #e2e8f0;
    }

    .ghost-action:hover {
        background: #f9fafb;
        border-color: #cbd5e1;
        color: #111827;
    }

    .summary-card {
        position: sticky;
        top: 18px;
    }

    .summary-hero {
        padding: 22px;
        background: #ffffff;
        color: #111827;
        border-bottom: 1px solid #e5e7eb;
    }

    .summary-avatar {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 750;
        margin-bottom: 14px;
    }

    .summary-name {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0;
        color: #111827;
    }

    .summary-domain {
        color: #64748b;
        font-size: 13px;
        word-break: break-all;
        margin: 0;
        line-height: 1.45;
    }

    .summary-body {
        padding: 22px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .summary-row:first-child {
        padding-top: 0;
    }

    .summary-row:last-child {
        border-bottom: 0;
    }

    .summary-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }

    .summary-value {
        color: #111827;
        font-size: 13px;
        font-weight: 650;
        text-align: right;
        word-break: break-word;
    }

    .quick-actions {
        display: grid;
        gap: 9px;
        margin-top: 22px;
    }

    .quick-actions a {
        width: 100%;
    }

    .danger-zone {
        margin-top: 22px;
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #9a3412;
        border-radius: 12px;
        padding: 14px;
    }

    .danger-zone-title {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .danger-zone p {
        margin: 0;
        font-size: 13px;
        line-height: 1.5;
        color: #9a3412;
    }

    .field-error {
        margin-top: 8px;
        color: #b42318;
        font-size: 13px;
        font-weight: 600;
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
            border-top: 1px solid #e5e7eb;
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
                <p class="project-edit-subtitle">Perbarui identitas, database, dan domain workspace.</p>
            </div>
            <?= Html::a('Back to Projects', (new \app\components\DomainContext())->projectListUrl(), ['class' => 'soft-btn']) ?>
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
                    <section class="form-section">
                        <div class="section-head">
                            <div>
                                <div class="section-kicker">Project Information</div>
                                <h2 class="section-title">Informasi Project</h2>
                                <p class="section-copy">Nama, deskripsi, dan database workspace.</p>
                            </div>
                        </div>

                        <div class="field-block">
                            <label class="field-label" for="project-name">Nama Project</label>
                            <?= $form->field($project, 'name')->textInput([
                                'id' => 'project-name',
                                'class' => 'form-control',
                                'placeholder' => 'Contoh: Sekolah Swasta',
                            ])->label(false) ?>
                        </div>

                        <div class="field-block">
                            <label class="field-label" for="project-description">Deskripsi</label>
                            <?= $form->field($project, 'description')->textarea([
                                'id' => 'project-description',
                                'class' => 'form-control',
                                'rows' => 5,
                                'placeholder' => 'Tulis deskripsi singkat workspace',
                            ])->label(false) ?>
                        </div>

                        <div class="field-block">
                            <label class="field-label" for="project-database-name">Database</label>
                            <input
                                type="text"
                                id="project-database-name"
                                class="form-control"
                                value="<?= Html::encode($databaseName !== '' ? $databaseName : '-') ?>"
                                readonly
                            >
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div>
                                <div class="section-kicker">Domain Settings</div>
                                <h2 class="section-title">Domain Workspace</h2>
                                <p class="section-copy">Atur alamat workspace. Suffix domain mengikuti sistem.</p>
                            </div>
                        </div>

                        <div class="field-block">
                            <label class="field-label" for="project-domain-prefix">
                                <span>Subdomain</span>
                                <span>Format: prefix.<?= Html::encode($projectDomainSuffix) ?></span>
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

                            <div class="helper-text">Ubah bagian depan saja. .<?= Html::encode($projectDomainSuffix) ?> tidak bisa diubah.</div>

                            <div class="domain-preview-card">
                                <div>
                                    <div class="domain-preview-label">Preview</div>
                                    <div class="domain-preview-url" id="domain-preview-url">https://<?= Html::encode($domainPreview) ?></div>
                                </div>
                                <span class="status-pill <?= Html::encode($statusClass) ?>" id="domain-status-badge"><?= Html::encode($statusLabel) ?></span>
                            </div>
                        </div>
                    </section>

                    <div class="action-row">
                        <button type="submit" class="primary-action">Simpan Perubahan</button>
                        <?= Html::a('Open Domain', $domainUrl, [
                            'class' => 'secondary-action',
                            'id' => 'open-domain-button',
                            'target' => '_blank',
                            'rel' => 'noopener',
                        ]) ?>
                        <?= Html::a('Back to Projects', (new \app\components\DomainContext())->projectListUrl(), ['class' => 'ghost-action']) ?>
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
                    <h2 class="section-title">Project Summary</h2>
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
                        <?= Html::a('Back to Projects', (new \app\components\DomainContext())->projectListUrl(), ['class' => 'ghost-action']) ?>
                    </div>

                    <div class="danger-zone">
                        <div class="danger-zone-title">Catatan domain</div>
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
