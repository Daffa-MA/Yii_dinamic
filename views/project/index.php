<?php

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var app\models\Project[] $projects */
/** @var app\models\Project|null $activeProject */
/** @var int|null $activeProjectId */
/** @var int $projectCount */
/** @var array<int,string> $projectDatabases */
/** @var \yii\data\Pagination $pagination */

use yii\bootstrap5\Html;
use yii\widgets\LinkPager;

$this->title = 'Beranda Workspace';
$this->registerJs("document.body.classList.add('project-welcome-page');", \yii\web\View::POS_READY);
$username = Yii::$app->user->identity->username ?? 'Pengguna';
$projectCount = (int)($projectCount ?? count($projects));
$activeProjectName = $activeProject !== null ? $activeProject->name : null;
$activeProjectDatabase = ($activeProject !== null && isset($projectDatabases[(int)$activeProject->id]))
    ? $projectDatabases[(int)$activeProject->id]
    : null;
?>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'projects', 'sidebarVariant' => 'minimal']) ?>

<main class="app-shell-main project-home-shell" style="padding-left: var(--app-sidebar-width, 16rem); min-height: 100vh; padding-top: 2rem; padding-bottom: 2rem; transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
    <div class="project-home-orb project-home-orb-a"></div>
    <div class="project-home-orb project-home-orb-b"></div>
    <div class="container-lg position-relative" style="z-index: 1;">
        <section class="project-home-hero">
            <div class="project-home-hero-copy" data-animate="slideInLeft">
                <div class="project-home-kicker">
                    <span class="material-symbols-outlined">home</span>
                    <span>Beranda Workspace</span>
                </div>
                <h1>Selamat datang, <?= Html::encode($username) ?></h1>
                <p>
                    Ini titik awal Anda untuk memilih project aktif atau membuat project baru sebelum masuk ke form,
                    tabel, dan data form.
                </p>
                <div class="project-home-actions">
                    <a href="#create-project" class="project-home-action project-home-action-primary">
                        <span class="material-symbols-outlined">add</span>
                        <span>Buat Project Baru</span>
                    </a>
                    <a href="#projects-list" class="project-home-action project-home-action-secondary">
                        <span class="material-symbols-outlined">folder_open</span>
                        <span>Lihat Project Saya</span>
                    </a>
                </div>
                <div class="project-home-metrics">
                    <div class="project-home-metric">
                        <strong><?= $projectCount ?></strong>
                        <span>Project tersimpan</span>
                    </div>
                    <div class="project-home-metric">
                        <strong><?= Html::encode($activeProjectName ?? 'Belum ada project aktif') ?></strong>
                        <span>Project aktif</span>
                    </div>
                    <div class="project-home-metric">
                        <strong>Form & tabel</strong>
                        <span>Satu workspace</span>
                    </div>
                </div>
            </div>

            <div class="project-home-hero-panel" data-animate="slideInRight">
                <div class="project-home-hero-panel-top">
                    <span class="project-home-panel-tag">Project Hub</span>
                    <span class="project-home-live-pill">
                        <span class="material-symbols-outlined">space_dashboard</span>
                        Navigation
                    </span>
                </div>
                <div class="project-home-hero-panel-body">
                    <div class="project-home-panel-icon">
                        <span class="material-symbols-outlined">dashboard</span>
                    </div>
                    <h2>Kelola workspace dari sini</h2>
                    <p>Pilih project aktif untuk melanjutkan ke dashboard form, tabel, dan data form.</p>
                    <div class="project-home-panel-stats">
                        <div class="project-home-panel-stat">
                            <span>Project tersimpan</span>
                            <strong><?= $projectCount ?></strong>
                        </div>
                        <div class="project-home-panel-stat">
                            <span>Database aktif</span>
                            <strong><?= Html::encode($activeProjectDatabase ?? '-') ?></strong>
                        </div>
                    </div>
                </div>
                <div class="project-home-hero-panel-footer">
                    <span>Project aktif</span>
                    <strong><?= Html::encode($activeProjectName ?? 'Belum dipilih') ?></strong>
                </div>
            </div>
        </section>

        <div class="row g-4 align-items-stretch">
            <div class="col-12 col-lg-4" id="create-project">
                <section class="project-card project-card-create" data-animate="slideInUp">
                    <div class="project-card-head">
                        <div class="project-card-head-icon project-card-head-icon-create">
                            <span class="material-symbols-outlined">add</span>
                        </div>
                        <div>
                            <div class="project-card-kicker">Mulai dari sini</div>
                            <h2 class="project-card-title">Buat Project Baru</h2>
                        </div>
                    </div>
                    <p class="project-card-description">
                        Buat workspace baru untuk form, tabel, dan data form Anda. Saat project dibuat, sistem juga membuat DATABASE MySQL baru khusus project tersebut.
                    </p>

                    <form method="post" action="<?= \yii\helpers\Url::to(['project/index']) ?>" class="project-form" autocomplete="off">
                        <input type="hidden" name="_csrf" value="<?= Yii::$app->request->getCsrfToken() ?>">

                        <div class="mb-3">
                            <label for="proj-name" class="project-field-label">Nama Project</label>
                            <input type="text" id="proj-name" name="Project[name]" value="<?= Html::encode($model->name ?? '') ?>" class="form-control form-control-lg" placeholder="Contoh: Absensi Siswa" maxlength="150" required>
                            <?php if ($model->hasErrors('name')): ?>
                                <div class="project-field-error">
                                    <?= Html::encode($model->getFirstError('name')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label for="proj-desc" class="project-field-label">Deskripsi</label>
                            <textarea id="proj-desc" name="Project[description]" class="form-control form-control-lg" rows="4" placeholder="Deskripsi singkat project (opsional)"><?= Html::encode($model->description ?? '') ?></textarea>
                            <?php if ($model->hasErrors('description')): ?>
                                <div class="project-field-error">
                                    <?= Html::encode($model->getFirstError('description')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="project-submit-btn">
                            <span class="material-symbols-outlined">check</span>
                            <span>Simpan & Gunakan</span>
                        </button>

                        <p class="project-submit-hint">Project baru akan menjadi project aktif setelah disimpan.</p>
                    </form>
                </section>
            </div>

            <div class="col-12 col-lg-8" id="projects-list">
                <section class="project-card project-card-list" data-animate="slideInUp">
                    <div class="project-list-head">
                        <div>
                            <div class="project-card-kicker">Workspace utama</div>
                            <h2 class="project-card-title">Project Saya</h2>
                            <p class="project-list-subtitle">Ini adalah beranda Anda. Pilih project untuk masuk ke workspace.</p>
                        </div>
                        <span class="project-count-pill"><?= $projectCount ?> project</span>
                    </div>

                    <?php if (empty($projects)): ?>
                        <div class="project-empty-state">
                            <div class="project-empty-icon">
                                <span class="material-symbols-outlined">home</span>
                            </div>
                            <h3>Belum ada project</h3>
                            <p>Mulai dari kartu di sebelah kiri untuk membuat workspace pertama Anda.</p>
                            <a href="#create-project" class="project-empty-action">Buat project pertama</a>
                        </div>
                    <?php else: ?>
                        <div class="project-list">
                            <?php foreach ($projects as $project): ?>
                                <div class="project-item <?= (int)$activeProjectId === (int)$project->id ? 'is-active' : '' ?>">
                                    <div class="project-item-main">
                                        <div class="project-item-avatar">
                                            <span class="material-symbols-outlined">folder_open</span>
                                        </div>
                                        <div class="project-item-copy">
                                            <div class="project-item-title-row">
                                                <h3><?= Html::encode($project->name) ?></h3>
                                                <?php if ((int)$activeProjectId === (int)$project->id): ?>
                                                    <span class="project-item-badge is-active">Aktif</span>
                                                <?php endif; ?>
                                            </div>
                                            <p><?= Html::encode($project->description ?: 'Tanpa deskripsi') ?></p>
                                            <p class="project-item-database">
                                                <span class="material-symbols-outlined">database</span>
                                                <span><?= Html::encode($projectDatabases[(int)$project->id] ?? '-') ?></span>
                                            </p>
                                        </div>
                                    </div>

                                    <?= Html::a(
                                        'Pilih',
                                        ['project/select', 'id' => $project->id],
                                        ['class' => 'project-item-action']
                                    ) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination Controls -->
                        <div class="project-pagination">
                            <?= LinkPager::widget([
                                'pagination' => $pagination,
                                'options' => ['class' => 'pagination pagination-sm justify-content-center'],
                                'linkOptions' => ['class' => 'page-link'],
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<style>
    .project-pagination {
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: center;
    }

    .project-pagination .pagination {
        gap: 4px;
        margin-bottom: 0;
    }

    .project-pagination .page-item {
        margin: 0;
    }

    .project-pagination .page-link {
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .project-pagination .page-link:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .project-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.24);
    }

    .project-pagination .page-item.disabled .page-link {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #cbd5e1;
        cursor: not-allowed;
    }

    /* Dark theme override for pagination */
    body.project-welcome-page .project-pagination {
        border-top-color: rgba(148, 163, 184, 0.2);
    }

    body.project-welcome-page .project-pagination .page-link {
        background: rgba(255, 255, 255, 0.92);
        border-color: rgba(148, 163, 184, 0.2);
        color: #0f172a;
    }

    body.project-welcome-page .project-pagination .page-link:hover {
        background: rgba(255, 255, 255, 0.98);
        border-color: rgba(59, 130, 246, 0.4);
        color: #1d4ed8;
    }

    body.project-welcome-page .project-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #0369a1 100%);
        border-color: #2563eb;
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.32);
    }

    .project-home-shell {
        position: relative;
        overflow: hidden;
        background: linear-gradient(180deg, #f7fbff 0%, #eef2ff 42%, #f8fafc 100%);
    }

    .project-home-shell::before,
    .project-home-shell::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        filter: blur(10px);
        pointer-events: none;
    }

    .project-home-shell::before {
        top: 72px;
        right: -120px;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(79, 70, 229, 0.16) 0%, rgba(79, 70, 229, 0) 70%);
    }

    .project-home-shell::after {
        left: -140px;
        bottom: 160px;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(14, 165, 233, 0.14) 0%, rgba(14, 165, 233, 0) 70%);
    }

    .project-home-orb {
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
        opacity: 0.55;
        filter: blur(22px);
    }

    .project-home-orb-a {
        top: 240px;
        right: 96px;
        width: 110px;
        height: 110px;
        background: rgba(251, 146, 60, 0.22);
    }

    .project-home-orb-b {
        left: 260px;
        top: 96px;
        width: 80px;
        height: 80px;
        background: rgba(34, 197, 94, 0.18);
    }

    .project-home-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
        gap: 24px;
        margin-bottom: 24px;
        align-items: stretch;
    }

    .project-home-hero-copy {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        padding: 32px;
        color: #ffffff;
        background: linear-gradient(135deg, #0f172a 0%, #0f766e 60%, #1d4ed8 120%);
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.16);
    }

    .project-home-hero-copy::after {
        content: '';
        position: absolute;
        inset: auto -80px -120px auto;
        width: 260px;
        height: 260px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 72%);
    }

    .project-home-kicker {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.95);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .project-home-kicker .material-symbols-outlined {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    .project-home-hero-copy h1 {
        position: relative;
        margin: 18px 0 12px;
        font-size: clamp(2.2rem, 4vw, 3.6rem);
        font-weight: 800;
        line-height: 1.04;
        letter-spacing: -0.04em;
        z-index: 1;
    }

    .project-home-hero-copy p {
        position: relative;
        max-width: 46rem;
        margin: 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 1.05rem;
        line-height: 1.7;
        z-index: 1;
    }

    .project-home-actions {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 24px;
        z-index: 1;
    }

    .project-home-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 700;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .project-home-action:hover {
        transform: translateY(-2px);
    }

    .project-home-action .material-symbols-outlined {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    .project-home-action-primary {
        background: linear-gradient(135deg, #f59e0b 0%, #fb7185 100%);
        color: #111827;
        box-shadow: 0 14px 24px rgba(249, 115, 22, 0.22);
    }

    .project-home-action-primary:hover {
        box-shadow: 0 18px 30px rgba(249, 115, 22, 0.28);
        color: #111827;
    }

    .project-home-action-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .project-home-action-secondary:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.14);
    }

    .project-home-metrics {
        position: relative;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-top: 28px;
        z-index: 1;
    }

    .project-home-metric {
        padding: 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
    }

    .project-home-metric strong {
        display: block;
        color: #ffffff;
        font-size: 1rem;
        line-height: 1.25;
        word-break: break-word;
    }

    .project-home-metric span {
        display: block;
        margin-top: 4px;
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.86rem;
    }

    .project-home-hero-panel {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        padding: 26px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(148, 163, 184, 0.24);
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(18px);
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .project-home-hero-panel-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .project-home-panel-tag {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .project-home-live-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
    }

    .project-home-live-pill .material-symbols-outlined {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    .project-home-hero-panel-body {
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 14px;
    }

    .project-home-panel-icon {
        width: 68px;
        height: 68px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
        color: #ffffff;
        box-shadow: 0 18px 30px rgba(37, 99, 235, 0.22);
    }

    .project-home-panel-icon .material-symbols-outlined {
        font-size: 30px;
        width: 30px;
        height: 30px;
    }

    .project-home-hero-panel-body h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .project-home-hero-panel-body p {
        margin: 0;
        color: #475569;
        line-height: 1.65;
    }

    .project-home-panel-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 10px;
    }

    .project-home-panel-stat {
        padding: 14px 16px;
        border-radius: 18px;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #e2e8f0;
    }

    .project-home-panel-stat span {
        display: block;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .project-home-panel-stat strong {
        display: block;
        margin-top: 4px;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        word-break: break-word;
    }

    .project-home-hero-panel-footer {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding-top: 18px;
        border-top: 1px solid rgba(148, 163, 184, 0.24);
    }

    .project-home-hero-panel-footer span {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .project-home-hero-panel-footer strong {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        word-break: break-word;
    }

    .project-card {
        position: relative;
        height: 100%;
        border-radius: 28px;
        padding: 28px;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(226, 232, 240, 0.96);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
    }

    .project-card-create {
        border-top: 4px solid #f97316;
    }

    .project-card-list {
        border-top: 4px solid #0ea5e9;
    }

    .project-card-head,
    .project-list-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }

    .project-card-head-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .project-card-head-icon-create {
        background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
        color: #ffffff;
    }

    .project-card-head-icon .material-symbols-outlined {
        font-size: 22px;
        width: 22px;
        height: 22px;
    }

    .project-card-kicker {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .project-card-title {
        margin: 4px 0 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .project-card-description,
    .project-list-subtitle {
        margin: 12px 0 22px;
        color: #475569;
        line-height: 1.65;
    }

    .project-form .form-control {
        border-color: #dbe3ee;
        border-radius: 16px;
        background: #ffffff;
        color: #0f172a;
        padding: 0.9rem 1rem;
        box-shadow: none;
    }

    .project-form .form-control::placeholder {
        color: #94a3b8;
    }

    .project-form .form-control:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.14);
    }

    .project-field-label {
        display: block;
        margin-bottom: 10px;
        color: #0f172a;
        font-weight: 700;
    }

    .project-field-error {
        margin-top: 8px;
        color: #dc2626;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .project-submit-btn {
        width: 100%;
        min-height: 56px;
        border: none;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 1rem;
        box-shadow: 0 16px 28px rgba(37, 99, 235, 0.22);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .project-submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 32px rgba(37, 99, 235, 0.28);
    }

    .project-submit-btn .material-symbols-outlined {
        font-size: 20px;
        width: 20px;
        height: 20px;
    }

    .project-submit-hint {
        margin: 14px 0 0;
        color: #64748b;
        font-size: 0.875rem;
    }

    .project-count-pill {
        display: inline-flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 999px;
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        color: #ffffff;
        font-size: 0.85rem;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.2);
    }

    .project-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .project-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .project-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 26px rgba(15, 23, 42, 0.08);
        border-color: #bfdbfe;
    }

    .project-item.is-active {
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.92) 0%, rgba(240, 253, 250, 0.92) 100%);
        border-color: rgba(37, 99, 235, 0.18);
    }

    .project-item-main {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
        flex: 1;
    }

    .project-item-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(79, 70, 229, 0.2);
    }

    .project-item-avatar .material-symbols-outlined {
        font-size: 24px;
        width: 24px;
        height: 24px;
    }

    .project-item-copy {
        min-width: 0;
        flex: 1;
    }

    .project-item-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .project-item-copy h3 {
        margin: 0;
        color: #0f172a;
        font-size: 1.08rem;
        font-weight: 800;
    }

    .project-item-copy p {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 0.92rem;
        line-height: 1.55;
    }

    .project-item-copy .project-item-database {
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        padding: 5px 10px;
        max-width: 100%;
    }

    .project-item-copy .project-item-database .material-symbols-outlined {
        font-size: 14px;
        width: 14px;
        height: 14px;
    }

    .project-item-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .project-item-badge.is-active {
        background: #dcfce7;
        color: #166534;
    }

    .project-item-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 88px;
        padding: 10px 16px;
        border-radius: 14px;
        text-decoration: none;
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        color: #ffffff;
        font-size: 0.9rem;
        font-weight: 800;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.18);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        flex-shrink: 0;
    }

    .project-item-action:hover {
        transform: translateY(-2px);
        color: #ffffff;
        box-shadow: 0 16px 26px rgba(37, 99, 235, 0.24);
    }

    .project-empty-state {
        padding: 44px 24px;
        text-align: center;
        border-radius: 24px;
        border: 1px dashed #cbd5e1;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .project-empty-icon {
        width: 84px;
        height: 84px;
        margin: 0 auto 18px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1d4ed8;
    }

    .project-empty-icon .material-symbols-outlined {
        font-size: 38px;
        width: 38px;
        height: 38px;
    }

    .project-empty-state h3 {
        margin: 0;
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 800;
    }

    .project-empty-state p {
        max-width: 28rem;
        margin: 10px auto 18px;
        color: #64748b;
        line-height: 1.65;
    }

    .project-empty-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
        box-shadow: 0 14px 24px rgba(37, 99, 235, 0.2);
    }

    .project-empty-action:hover {
        color: #ffffff;
    }

    @media (max-width: 1199.98px) {
        .project-home-hero {
            grid-template-columns: 1fr;
        }

        .project-home-metrics {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .project-card-head,
        .project-list-head,
        .project-item {
            align-items: flex-start;
        }

        .project-item {
            flex-direction: column;
        }

        .project-item-action {
            width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .project-home-hero-copy,
        .project-home-hero-panel,
        .project-card {
            padding: 24px;
        }

        .project-home-actions {
            flex-direction: column;
        }

        .project-home-action {
            width: 100%;
        }
    }

    /* Premium overrides khusus halaman welcome project */
    body.project-welcome-page .project-home-shell {
        background:
            radial-gradient(circle at 14% 14%, rgba(59, 130, 246, 0.22) 0%, rgba(59, 130, 246, 0) 36%),
            radial-gradient(circle at 86% 18%, rgba(14, 165, 233, 0.2) 0%, rgba(14, 165, 233, 0) 34%),
            radial-gradient(circle at 75% 82%, rgba(99, 102, 241, 0.14) 0%, rgba(99, 102, 241, 0) 32%),
            linear-gradient(165deg, #edf4ff 0%, #eef2ff 50%, #f8fafc 100%);
    }

    body.project-welcome-page .project-home-orb-a,
    body.project-welcome-page .project-home-orb-b {
        animation: projectOrbFloat 7s ease-in-out infinite alternate;
    }

    body.project-welcome-page .project-home-orb-b {
        animation-delay: 1.2s;
    }

    body.project-welcome-page .project-home-hero-copy {
        background:
            radial-gradient(circle at 82% 12%, rgba(56, 189, 248, 0.35) 0%, rgba(56, 189, 248, 0) 46%),
            linear-gradient(130deg, #0b1530 0%, #0f2f63 42%, #0f766e 100%);
        border: 1px solid rgba(191, 219, 254, 0.28);
        box-shadow:
            0 34px 64px rgba(15, 23, 42, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    body.project-welcome-page .project-home-hero-copy h1 {
        text-shadow: 0 8px 30px rgba(15, 23, 42, 0.45);
    }

    body.project-welcome-page .project-home-action-primary {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #0369a1 100%);
        color: #ffffff;
        border: 1px solid rgba(191, 219, 254, 0.42);
        box-shadow: 0 14px 28px rgba(30, 64, 175, 0.36);
    }

    body.project-welcome-page .project-home-action-primary:hover {
        color: #ffffff;
        transform: translateY(-2px) scale(1.02);
    }

    body.project-welcome-page .project-home-action-secondary {
        background: rgba(15, 23, 42, 0.25);
        border: 1px solid rgba(191, 219, 254, 0.28);
        backdrop-filter: blur(12px);
    }

    body.project-welcome-page .project-home-action-secondary:hover {
        background: rgba(15, 23, 42, 0.36);
    }

    body.project-welcome-page .project-home-metric {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.14) 0%, rgba(219, 234, 254, 0.08) 100%);
        border-color: rgba(191, 219, 254, 0.25);
    }

    body.project-welcome-page .project-home-hero-panel,
    body.project-welcome-page .project-card {
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(148, 163, 184, 0.2);
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(12px);
    }

    body.project-welcome-page .project-card {
        transition: transform 0.26s ease, box-shadow 0.26s ease, border-color 0.26s ease;
    }

    body.project-welcome-page .project-card:hover {
        transform: translateY(-5px);
        border-color: rgba(59, 130, 246, 0.24);
        box-shadow: 0 28px 56px rgba(15, 23, 42, 0.12);
    }

    body.project-welcome-page .project-submit-btn {
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 52%, #0f766e 100%);
    }

    body.project-welcome-page .project-item {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.96) 0%, rgba(241, 245, 249, 0.92) 100%);
    }

    body.project-welcome-page .project-item:hover {
        transform: translateY(-3px) scale(1.005);
    }

    body.project-welcome-page [data-animate] {
        opacity: 0;
        transform: translateY(18px);
        animation: projectReveal 0.75s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    }

    body.project-welcome-page [data-animate="slideInLeft"] {
        transform: translateX(-22px);
        animation-delay: 0.05s;
    }

    body.project-welcome-page [data-animate="slideInRight"] {
        transform: translateX(22px);
        animation-delay: 0.18s;
    }

    body.project-welcome-page [data-animate="slideInUp"] {
        animation-delay: 0.28s;
    }

    body.project-welcome-page .app-sidebar {
        background:
            radial-gradient(circle at 20% 8%, rgba(59, 130, 246, 0.28) 0%, rgba(59, 130, 246, 0) 38%),
            radial-gradient(circle at 82% 16%, rgba(14, 165, 233, 0.16) 0%, rgba(14, 165, 233, 0) 34%),
            linear-gradient(180deg, #0b1220 0%, #0f172a 48%, #16253b 100%);
        border-right: 1px solid rgba(148, 163, 184, 0.24);
        box-shadow: 16px 0 38px rgba(2, 6, 23, 0.42);
    }

    body.project-welcome-page .app-sidebar-header {
        border-bottom-color: rgba(148, 163, 184, 0.2);
    }

    body.project-welcome-page .app-sidebar-header-badge {
        background: rgba(148, 163, 184, 0.12);
        border-color: rgba(148, 163, 184, 0.28);
        color: #cbd5e1;
    }

    body.project-welcome-page .app-sidebar-header-text h2 {
        color: #f8fafc;
    }

    body.project-welcome-page .app-sidebar-header-text p {
        color: #94a3b8;
    }

    body.project-welcome-page .app-sidebar-context {
        border-bottom-color: rgba(148, 163, 184, 0.2);
    }

    body.project-welcome-page .app-sidebar-context-item-label {
        color: #93c5fd;
    }

    body.project-welcome-page .app-sidebar-context-item {
        background: rgba(15, 23, 42, 0.78);
        border-color: rgba(148, 163, 184, 0.22);
    }

    body.project-welcome-page .app-sidebar-link {
        color: #dbe3ef;
        border-color: rgba(148, 163, 184, 0.08);
    }

    body.project-welcome-page .app-sidebar-link .material-symbols-outlined {
        color: #bfdbfe;
        background: rgba(148, 163, 184, 0.16);
    }

    body.project-welcome-page .app-sidebar-link:hover {
        background: rgba(37, 99, 235, 0.18);
        border-color: rgba(96, 165, 250, 0.3);
    }

    body.project-welcome-page .app-sidebar-link.active {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 58%, #0284c7 100%);
        box-shadow: 0 14px 24px rgba(37, 99, 235, 0.36);
    }

    body.project-welcome-page .app-sidebar-link.active .material-symbols-outlined {
        background: rgba(255, 255, 255, 0.24);
    }

    body.project-welcome-page .app-sidebar-toggle {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-color: rgba(148, 163, 184, 0.34);
        color: #0f172a;
    }

    body.project-welcome-page .app-sidebar-logout {
        background: rgba(15, 23, 42, 0.76);
        border-color: rgba(148, 163, 184, 0.24);
    }

    body.project-welcome-page .app-sidebar-logout .material-symbols-outlined {
        color: #bfdbfe;
        background: rgba(59, 130, 246, 0.18);
    }

    body.project-welcome-page .app-sidebar-logout:hover {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-color: rgba(148, 163, 184, 0.5);
        color: #0f172a;
    }

    @keyframes projectOrbFloat {
        0% {
            transform: translate3d(0, 0, 0) scale(0.96);
            opacity: 0.45;
        }

        100% {
            transform: translate3d(0, -14px, 0) scale(1.08);
            opacity: 0.65;
        }
    }

    @keyframes projectReveal {
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }
</style>
