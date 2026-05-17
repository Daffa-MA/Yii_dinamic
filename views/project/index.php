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
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\models\Project;

$this->title = 'Workspace Project';
$this->registerJs("document.body.classList.add('project-page-v4');", \yii\web\View::POS_READY);

$username = Yii::$app->user->identity->username ?? 'Pengguna';
$projectCount = (int) ($projectCount ?? count($projects));
$visibleProjectCount = count($projects);
$activeProjectName = $activeProject !== null ? $activeProject->name : null;
$activeProjectDatabase = ($activeProject !== null && isset($projectDatabases[(int) $activeProject->id]))
    ? $projectDatabases[(int) $activeProject->id]
    : null;
?>

<style>
    .project-page-v4 {
        background:
            radial-gradient(circle at top right, rgba(15, 118, 110, 0.10), transparent 24%),
            radial-gradient(circle at left center, rgba(30, 64, 175, 0.08), transparent 26%),
            linear-gradient(180deg, #f8fafc 0%, #f4f7fb 100%);
        color: #0f172a;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .project-page-v4 main#main > .container {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .projects-shell {
        min-height: 100vh;
        padding-left: var(--app-sidebar-width, 16rem);
        position: relative;
        transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .projects-shell::before,
    .projects-shell::after {
        content: '';
        position: absolute;
        inset: auto;
        pointer-events: none;
        border-radius: 999px;
        filter: blur(28px);
        opacity: 0.6;
    }

    .projects-shell::before {
        top: 7rem;
        right: 2rem;
        width: 16rem;
        height: 16rem;
        background: rgba(15, 118, 110, 0.12);
    }

    .projects-shell::after {
        left: 1rem;
        bottom: 8rem;
        width: 18rem;
        height: 18rem;
        background: rgba(30, 64, 175, 0.08);
    }

    .projects-main-content {
        position: relative;
        z-index: 1;
        padding: 2rem 0 3rem;
    }

    .projects-container {
        max-width: 1480px;
    }

    .projects-surface {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.82);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(18px);
    }

    .projects-surface::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.34), transparent 38%);
        pointer-events: none;
    }

    .projects-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.7fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .projects-hero-copy {
        padding: 2rem;
    }

    .projects-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.6rem 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        background: rgba(255, 255, 255, 0.84);
        color: #334155;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .projects-kicker .material-symbols-outlined {
        font-size: 1rem;
        color: #0f766e;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .projects-title {
        margin: 1.4rem 0 1rem;
        max-width: 12ch;
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: clamp(2.5rem, 4.8vw, 4.2rem);
        line-height: 1.02;
        font-weight: 800;
        letter-spacing: -0.05em;
        color: #0f172a;
    }

    .projects-title-accent {
        color: #0f766e;
    }

    .projects-hero-text {
        max-width: 42rem;
        margin: 0;
        color: #475569;
        font-size: 1.02rem;
        line-height: 1.8;
    }

    .projects-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        margin-top: 1.75rem;
    }

    .projects-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        min-height: 3.35rem;
        padding: 0 1.25rem;
        border-radius: 16px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
    }

    .projects-button:hover {
        transform: translateY(-2px);
    }

    .projects-button .material-symbols-outlined {
        font-size: 1.15rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .projects-button-primary {
        border: 1px solid #0f172a;
        background: linear-gradient(135deg, #0f172a 0%, #1f2937 100%);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.16);
        color: #fff;
    }

    .projects-button-primary:hover {
        color: #fff;
        box-shadow: 0 22px 42px rgba(15, 23, 42, 0.22);
    }

    .projects-button-secondary {
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(255, 255, 255, 0.88);
        color: #0f172a;
    }

    .projects-button-secondary:hover {
        color: #0f172a;
        border-color: rgba(15, 118, 110, 0.28);
        box-shadow: 0 16px 30px rgba(148, 163, 184, 0.14);
    }

    .projects-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1.75rem;
    }

    .projects-stat-card {
        border-radius: 22px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.9));
        padding: 1rem 1.05rem;
    }

    .projects-stat-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.73rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .projects-stat-value {
        display: block;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .projects-stat-hint {
        display: block;
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .projects-hero-side {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .projects-spotlight {
        padding: 1.75rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.16), transparent 30%),
            linear-gradient(145deg, #0f172a 0%, #162033 60%, #1c2f43 100%);
        border-color: rgba(148, 163, 184, 0.08);
        color: #fff;
    }

    .projects-spotlight::before {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), transparent 42%);
    }

    .projects-spotlight-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .projects-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2rem;
        padding: 0 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .projects-chip .material-symbols-outlined {
        font-size: 1rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .projects-status-dot {
        width: 0.7rem;
        height: 0.7rem;
        border-radius: 999px;
        background: #34d399;
        box-shadow: 0 0 0 0.45rem rgba(52, 211, 153, 0.16);
        flex-shrink: 0;
    }

    .projects-spotlight-title {
        margin: 0;
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .projects-spotlight-text {
        margin: 0.6rem 0 0;
        color: rgba(226, 232, 240, 0.78);
        line-height: 1.7;
    }

    .projects-spotlight-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
        margin-top: 1.5rem;
    }

    .projects-spotlight-meta-card {
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.06);
        padding: 0.95rem 1rem;
    }

    .projects-spotlight-meta-card span {
        display: block;
        color: rgba(226, 232, 240, 0.7);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .projects-spotlight-meta-card strong {
        display: block;
        margin-top: 0.4rem;
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.5;
        word-break: break-word;
    }

    .projects-note-card {
        padding: 1.5rem;
    }

    .projects-note-head {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 1rem;
    }

    .projects-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.12), rgba(30, 64, 175, 0.10));
        color: #0f172a;
    }

    .projects-icon .material-symbols-outlined {
        font-size: 1.35rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .projects-note-title {
        margin: 0;
        font-size: 1.08rem;
        font-weight: 800;
        color: #0f172a;
    }

    .projects-note-list {
        display: grid;
        gap: 0.75rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .projects-note-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        color: #475569;
        line-height: 1.7;
    }

    .projects-note-list .material-symbols-outlined {
        margin-top: 0.12rem;
        font-size: 1rem;
        color: #0f766e;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .projects-panel {
        height: 100%;
        padding: 1.75rem;
    }

    .projects-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .projects-panel-title {
        margin: 0;
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.03em;
    }

    .projects-panel-subtitle {
        margin: 0.4rem 0 0;
        color: #64748b;
        line-height: 1.7;
    }

    .projects-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 2.5rem;
        padding: 0 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(248, 250, 252, 0.9);
        color: #334155;
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .projects-count-badge .material-symbols-outlined {
        font-size: 1rem;
        color: #0f766e;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .projects-form {
        display: grid;
        gap: 1rem;
    }

    .projects-field {
        display: grid;
        gap: 0.55rem;
    }

    .projects-field label {
        color: #334155;
        font-size: 0.84rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .projects-input {
        min-height: 3.35rem;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: rgba(255, 255, 255, 0.94);
        color: #0f172a;
        padding: 0.95rem 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .projects-input:focus {
        border-color: rgba(15, 118, 110, 0.48);
        box-shadow: 0 0 0 0.28rem rgba(15, 118, 110, 0.12);
        background: #fff;
        color: #0f172a;
        outline: none;
    }

    .projects-input::placeholder {
        color: #94a3b8;
    }

    .projects-input.projects-textarea {
        min-height: 8.5rem;
        resize: vertical;
    }

    .projects-field-error {
        color: #dc2626;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .projects-submit {
        width: 100%;
        margin-top: 0.25rem;
    }

    .projects-submit-hint {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-top: 1rem;
        padding: 0.9rem 1rem;
        border-radius: 18px;
        background: rgba(248, 250, 252, 0.95);
        color: #64748b;
        line-height: 1.6;
    }

    .projects-submit-hint .material-symbols-outlined {
        color: #0f766e;
        font-size: 1.1rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 22;
    }

    .projects-list-wrap {
        display: grid;
        gap: 1rem;
    }

    .projects-project-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.1rem;
        border-radius: 24px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92));
        padding: 1.2rem 1.25rem;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .projects-project-card:hover {
        transform: translateY(-3px);
        border-color: rgba(15, 118, 110, 0.24);
        box-shadow: 0 20px 34px rgba(15, 23, 42, 0.08);
    }

    .projects-project-card.is-active {
        border-color: rgba(15, 118, 110, 0.25);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(240, 253, 250, 0.9));
        box-shadow: 0 22px 40px rgba(15, 118, 110, 0.10);
    }

    .projects-project-main {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
        min-width: 0;
    }

    .projects-project-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.35rem;
        height: 3.35rem;
        min-width: 3.35rem;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.08), rgba(100, 116, 139, 0.14));
        color: #334155;
    }

    .projects-project-card.is-active .projects-project-icon {
        background: linear-gradient(135deg, #0f172a, #1f2937);
        color: #fff;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.16);
    }

    .projects-project-icon .material-symbols-outlined {
        font-size: 1.35rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .projects-project-copy {
        min-width: 0;
        flex: 1;
    }

    .projects-project-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-bottom: 0.35rem;
    }

    .projects-project-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.02rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .projects-active-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        min-height: 1.7rem;
        padding: 0 0.7rem;
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.10);
        color: #0f766e;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .projects-active-badge .material-symbols-outlined {
        font-size: 0.95rem;
        font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 18;
    }

    .projects-project-description {
        margin: 0;
        color: #64748b;
        line-height: 1.7;
    }

    .projects-project-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 0.9rem;
    }

    .projects-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2rem;
        max-width: 100%;
        padding: 0 0.8rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(248, 250, 252, 0.92);
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .projects-meta-pill .material-symbols-outlined {
        font-size: 0.95rem;
        color: #0f766e;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 18;
    }

    .projects-meta-pill-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .projects-project-action {
        flex-shrink: 0;
    }

    .projects-empty-state {
        padding: 3rem 1.25rem 3.25rem;
        text-align: center;
    }

    .projects-empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 5.25rem;
        height: 5.25rem;
        margin-bottom: 1.25rem;
        border-radius: 28px;
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.12), rgba(30, 64, 175, 0.10));
        color: #0f172a;
    }

    .projects-empty-icon .material-symbols-outlined {
        font-size: 2rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 40;
    }

    .projects-empty-title {
        margin: 0;
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: 1.55rem;
        font-weight: 800;
        color: #0f172a;
    }

    .projects-empty-text {
        max-width: 26rem;
        margin: 0.85rem auto 0;
        color: #64748b;
        line-height: 1.75;
    }

    .projects-pagination {
        margin-top: 1.5rem;
        padding-top: 1.4rem;
        border-top: 1px solid rgba(148, 163, 184, 0.16);
    }

    .projects-pagination .pagination {
        gap: 0.4rem;
    }

    .projects-pagination .page-link {
        min-width: 2.65rem;
        height: 2.65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.92);
        color: #334155;
        font-weight: 700;
        transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .projects-pagination .page-link:hover {
        transform: translateY(-1px);
        border-color: rgba(15, 118, 110, 0.28);
        background: #fff;
        color: #0f172a;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
    }

    .projects-pagination .page-item.active .page-link {
        border-color: #0f172a;
        background: linear-gradient(135deg, #0f172a, #1f2937);
        color: #fff;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.16);
    }

    .projects-pagination .page-item.disabled .page-link {
        background: rgba(248, 250, 252, 0.92);
        color: #cbd5e1;
        box-shadow: none;
    }

    @media (max-width: 1199.98px) {
        .projects-hero {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .projects-shell {
            padding-left: 0;
        }

        .projects-main-content {
            padding-top: 1.5rem;
        }

        .projects-hero-copy,
        .projects-panel,
        .projects-spotlight,
        .projects-note-card {
            padding: 1.4rem;
        }

        .projects-stat-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .projects-container {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .projects-title {
            max-width: none;
        }

        .projects-panel-head,
        .projects-project-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .projects-count-badge,
        .projects-project-action,
        .projects-project-action .projects-button {
            width: 100%;
        }

        .projects-project-main,
        .projects-project-copy {
            width: 100%;
        }

        .projects-spotlight-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'projects', 'sidebarVariant' => 'minimal']) ?>

<div class="projects-shell">
    <section class="projects-main-content">
        <div class="container-fluid projects-container">
            <section class="projects-hero">
                <div class="projects-surface projects-hero-copy">
                    <span class="projects-kicker">
                        <span class="material-symbols-outlined">stacks</span>
                        Workspace Project
                    </span>

                    <h1 class="projects-title">
                        Bangun workspace yang <span class="projects-title-accent">rapi, tajam, dan profesional.</span>
                    </h1>

                    <p class="projects-hero-text">
                        Halo, <?= Html::encode($username) ?>. Halaman ini sekarang jadi pusat kontrol project Anda:
                        pilih workspace aktif, buat project baru, dan kelola database terisolasi dengan tampilan yang
                        jauh lebih clean dan fokus.
                    </p>

                    <div class="projects-action-row">
                        <a href="#create-project" class="projects-button projects-button-primary">
                            <span class="material-symbols-outlined">add_circle</span>
                            <span>Buat Project Baru</span>
                        </a>
                        <a href="#projects-list" class="projects-button projects-button-secondary">
                            <span class="material-symbols-outlined">folder_managed</span>
                            <span>Lihat Semua Project</span>
                        </a>
                    </div>

                    <div class="projects-stat-grid">
                        <div class="projects-stat-card">
                            <span class="projects-stat-label">Total Workspace</span>
                            <strong class="projects-stat-value"><?= $projectCount ?></strong>
                            <span class="projects-stat-hint">Semua project tersimpan dalam hub Anda.</span>
                        </div>
                        <div class="projects-stat-card">
                            <span class="projects-stat-label">Project Aktif</span>
                            <strong class="projects-stat-value"><?= Html::encode($activeProjectName ?? 'Belum ada') ?></strong>
                            <span class="projects-stat-hint">Workspace yang dipakai untuk alur kerja sekarang.</span>
                        </div>
                        <div class="projects-stat-card">
                            <span class="projects-stat-label">Project Ditampilkan</span>
                            <strong class="projects-stat-value"><?= $visibleProjectCount ?></strong>
                            <span class="projects-stat-hint">Daftar aktif pada halaman ini dengan pagination.</span>
                        </div>
                    </div>
                </div>

                <div class="projects-hero-side">
                    <aside class="projects-surface projects-spotlight">
                        <div class="projects-spotlight-top">
                            <span class="projects-chip">
                                <span class="material-symbols-outlined">target</span>
                                Workspace Aktif
                            </span>
                            <span class="projects-status-dot" aria-hidden="true"></span>
                        </div>

                        <h2 class="projects-spotlight-title"><?= Html::encode($activeProjectName ?? 'Belum ada project dipilih') ?></h2>
                        <p class="projects-spotlight-text">
                            <?= $activeProject !== null
                                ? 'Project ini siap dipakai untuk form, tabel, dan data submission berikutnya.'
                                : 'Pilih salah satu project di bawah atau buat project baru untuk mengaktifkan workspace.' ?>
                        </p>

                        <div class="projects-spotlight-meta">
                            <div class="projects-spotlight-meta-card">
                                <span>Status</span>
                                <strong><?= $activeProject !== null ? 'Ready to use' : 'Waiting selection' ?></strong>
                            </div>
                            <div class="projects-spotlight-meta-card">
                                <span>Database</span>
                                <strong><?= Html::encode($activeProjectDatabase ?? '-') ?></strong>
                            </div>
                        </div>
                    </aside>

                    <div class="projects-surface projects-note-card">
                        <div class="projects-note-head">
                            <div class="projects-icon">
                                <span class="material-symbols-outlined">verified</span>
                            </div>
                            <div>
                                <h3 class="projects-note-title">Struktur lebih aman</h3>
                                <p class="projects-panel-subtitle">Setiap project dibuat dengan database MySQL terpisah.</p>
                            </div>
                        </div>

                        <ul class="projects-note-list">
                            <li>
                                <span class="material-symbols-outlined">check_circle</span>
                                <span>Lebih mudah menjaga data antar project tetap terorganisir.</span>
                            </li>
                            <li>
                                <span class="material-symbols-outlined">check_circle</span>
                                <span>Transisi dari pilih project ke dashboard tetap cepat dan jelas.</span>
                            </li>
                            <li>
                                <span class="material-symbols-outlined">check_circle</span>
                                <span>Tampilan kartu, tombol, dan icon sekarang konsisten dan lebih premium.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <div class="row g-4">
                <div class="col-12 col-xl-4" id="create-project">
                    <section class="projects-surface projects-panel">
                        <div class="projects-panel-head">
                            <div>
                                <div class="projects-note-head mb-0">
                                    <div class="projects-icon">
                                        <span class="material-symbols-outlined">edit_square</span>
                                    </div>
                                    <div>
                                        <h2 class="projects-panel-title">Buat Project Baru</h2>
                                        <p class="projects-panel-subtitle">Isi nama project. Database dan domain otomatis dibuat oleh sistem.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="post" action="<?= Url::to(['project/index']) ?>" class="projects-form" autocomplete="off">
                            <input type="hidden" name="_csrf" value="<?= Yii::$app->request->getCsrfToken() ?>">

                            <div class="projects-field">
                                <label for="project-name">Nama Project</label>
                                <input
                                    type="text"
                                    id="project-name"
                                    name="Project[name]"
                                    value="<?= Html::encode($model->name ?? '') ?>"
                                    class="form-control projects-input"
                                    placeholder="Contoh: Absensi Siswa"
                                    maxlength="150"
                                    required
                                >
                                <?php if ($model->hasErrors('name')): ?>
                                    <div class="projects-field-error"><?= Html::encode($model->getFirstError('name')) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="projects-field">
                                <label for="project-database">Database</label>
                                <?php
                                $databasePreview = strtolower(trim((string)($model->name ?? '')));
                                $databasePreview = preg_replace('/[^a-z0-9]+/i', '_', $databasePreview) ?? '';
                                $databasePreview = trim($databasePreview, '_');
                                if ($databasePreview === '') {
                                    $databasePreview = 'project';
                                }
                                if (preg_match('/^[0-9]/', $databasePreview) === 1) {
                                    $databasePreview = 'project_' . $databasePreview;
                                }
                                ?>
                                <input
                                    type="text"
                                    id="project-database"
                                    class="form-control projects-input"
                                    value="<?= Html::encode($databasePreview) ?>"
                                    readonly
                                >
                                <div class="projects-submit-hint" style="margin-top:8px;">
                                    <span class="material-symbols-outlined">dns</span>
                                    <span>Database dibuat otomatis dari nama project.</span>
                                </div>
                            </div>

                            <div class="projects-field">
                                <label for="project-domain-preview">Domain Otomatis</label>
                                <?php
                                $projectDomainSuffix = Project::getProjectDomainSuffix();
                                $projectSlugPreview = strtolower(trim((string)($model->name ?? '')));
                                $projectSlugPreview = preg_replace('/[^a-z0-9]+/i', '-', $projectSlugPreview) ?? '';
                                $projectSlugPreview = preg_replace('/-+/', '-', $projectSlugPreview) ?? $projectSlugPreview;
                                $projectSlugPreview = trim($projectSlugPreview, '-');
                                if ($projectSlugPreview === '') {
                                    $projectSlugPreview = 'project';
                                }
                                if (preg_match('/^[0-9]/', $projectSlugPreview) === 1) {
                                    $projectSlugPreview = 'project-' . $projectSlugPreview;
                                }
                                $domainPreview = $projectSlugPreview . '.' . $projectDomainSuffix;
                                ?>
                                <input
                                    type="text"
                                    id="project-domain-preview"
                                    class="form-control projects-input"
                                    value="<?= Html::encode($domainPreview) ?>"
                                    readonly
                                >
                                <div class="projects-submit-hint" style="margin-top:8px;">
                                    <span class="material-symbols-outlined">language</span>
                                    <span>Domain dibuat otomatis dari slug project dan wildcard sslip.io.</span>
                                </div>
                            </div>

                            <div class="projects-field">
                                <label for="project-description">Deskripsi</label>
                                <textarea
                                    id="project-description"
                                    name="Project[description]"
                                    class="form-control projects-input projects-textarea"
                                    rows="4"
                                    placeholder="Jelaskan tujuan project ini secara singkat"
                                ><?= Html::encode($model->description ?? '') ?></textarea>
                                <?php if ($model->hasErrors('description')): ?>
                                    <div class="projects-field-error"><?= Html::encode($model->getFirstError('description')) ?></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="projects-button projects-button-primary projects-submit">
                                <span class="material-symbols-outlined">rocket_launch</span>
                                <span>Simpan dan Gunakan</span>
                            </button>

                            <div class="projects-submit-hint">
                                <span class="material-symbols-outlined">shield</span>
                                <span>Setelah disimpan, project baru langsung menjadi workspace aktif untuk pekerjaan berikutnya.</span>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="col-12 col-xl-8" id="projects-list">
                    <section class="projects-surface projects-panel">
                        <div class="projects-panel-head">
                            <div>
                                <div class="projects-note-head mb-0">
                                    <div class="projects-icon">
                                        <span class="material-symbols-outlined">folder_open</span>
                                    </div>
                                    <div>
                                        <h2 class="projects-panel-title">Daftar Project</h2>
                                        <p class="projects-panel-subtitle">Pilih workspace yang ingin diaktifkan untuk melanjutkan ke dashboard.</p>
                                    </div>
                                </div>
                            </div>
                            <span class="projects-count-badge">
                                <span class="material-symbols-outlined">database</span>
                                <?= $projectCount ?> workspace
                            </span>
                        </div>

                        <?php if (empty($projects)): ?>
                            <div class="projects-empty-state">
                                <div class="projects-empty-icon">
                                    <span class="material-symbols-outlined">folder_off</span>
                                </div>
                                <h3 class="projects-empty-title">Belum ada project</h3>
                                <p class="projects-empty-text">
                                    Mulai dari panel kiri untuk membuat workspace pertama. Setelah itu Anda bisa langsung
                                    mengaktifkannya dan lanjut ke halaman dashboard.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="projects-list-wrap">
                                <?php foreach ($projects as $project): ?>
                                    <?php $isActive = (int) $activeProjectId === (int) $project->id; ?>
                                    <article class="projects-project-card<?= $isActive ? ' is-active' : '' ?>">
                                        <div class="projects-project-main">
                                            <div class="projects-project-icon">
                                                <span class="material-symbols-outlined"><?= $isActive ? 'folder_managed' : 'folder' ?></span>
                                            </div>

                                            <div class="projects-project-copy">
                                                <div class="projects-project-title-row">
                                                    <h3 class="projects-project-title"><?= Html::encode($project->name) ?></h3>
                                                    <?php if ($isActive): ?>
                                                        <span class="projects-active-badge">
                                                            <span class="material-symbols-outlined">check_circle</span>
                                                            Aktif
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="projects-project-description">
                                                    <?= Html::encode($project->description ?: 'Deskripsi belum ditambahkan untuk project ini.') ?>
                                                </p>

                                                <div class="projects-project-meta">
                                                    <span class="projects-meta-pill">
                                                        <span class="material-symbols-outlined">dns</span>
                                                        <span class="projects-meta-pill-text"><?= Html::encode($projectDatabases[(int) $project->id] ?? '-') ?></span>
                                                    </span>
                                                    <?php if (!empty($project->custom_domain)): ?>
                                                        <span class="projects-meta-pill">
                                                            <span class="material-symbols-outlined">language</span>
                                                            <span class="projects-meta-pill-text"><?= Html::encode($project->custom_domain) ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="projects-meta-pill">
                                                        <span class="material-symbols-outlined">deployed_code</span>
                                                        <span class="projects-meta-pill-text">Project #<?= (int) $project->id ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="projects-project-action">
                                            <?= Html::a(
                                                '<span class="material-symbols-outlined">' . ($isActive ? 'arrow_outward' : 'north_east') . '</span><span>' . ($isActive ? 'Buka Workspace' : 'Jadikan Aktif') . '</span>',
                                                ['project/select', 'id' => $project->id],
                                                [
                                                    'class' => 'projects-button ' . ($isActive ? 'projects-button-primary' : 'projects-button-secondary'),
                                                    'encode' => false,
                                                ]
                                            ) ?>

                                            <?php if (!empty($project->custom_domain)): ?>
                                                <?= Html::a(
                                                    '<span class="material-symbols-outlined">language</span><span>Open Domain</span>',
                                                    'https://' . $project->custom_domain,
                                                    [
                                                        'class' => 'projects-button projects-button-secondary',
                                                        'encode' => false,
                                                        'target' => '_blank',
                                                        'rel' => 'noopener noreferrer',
                                                    ]
                                                ) ?>
                                            <?php endif; ?>
                                            
                                            <?= Html::a(
                                                '<span class="material-symbols-outlined">tune</span><span>Settings</span>',
                                                ['project/update', 'id' => $project->id],
                                                [
                                                    'class' => 'projects-button projects-button-secondary',
                                                    'encode' => false,
                                                ]
                                            ) ?>

                                            <?= Html::a(
                                                '<span class="material-symbols-outlined">delete</span><span>Hapus</span>',
                                                ['project/delete', 'id' => $project->id],
                                                [
                                                    'class' => 'projects-button projects-button-secondary',
                                                    'encode' => false,
                                                    'data' => [
                                                        'confirm' => 'Yakin hapus project "' . Html::encode($project->name) . '"? Semua data di database akan dihapus!',
                                                        'method' => 'post',
                                                    ],
                                                ]
                                            ) ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <div class="projects-pagination">
                                <?= LinkPager::widget([
                                    'pagination' => $pagination,
                                    'options' => ['class' => 'pagination justify-content-center mb-0'],
                                    'linkOptions' => ['class' => 'page-link'],
                                    'activePageCssClass' => 'active',
                                ]) ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
$this->registerJs(<<<JS
(function () {
    const nameInput = document.getElementById('project-name');
    const databaseInput = document.getElementById('project-database');
    if (!nameInput || !databaseInput) {
        return;
    }

    const buildDatabaseName = (value) => {
        let normalized = String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        if (!normalized) {
            normalized = 'project';
        }
        if (/^[0-9]/.test(normalized)) {
            normalized = 'project_' + normalized;
        }
        return normalized;
    };

    const buildProjectSlug = (value) => {
        let normalized = String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
        if (!normalized) {
            normalized = 'project';
        }
        if (/^[0-9]/.test(normalized)) {
            normalized = 'project-' + normalized;
        }
        return normalized;
    };

    const updateDatabasePreview = () => {
        databaseInput.value = buildDatabaseName(nameInput.value);
    };

    const domainInput = document.getElementById('project-domain-preview');
    const domainSuffix = '<?= Html::encode($projectDomainSuffix) ?>';
    const updateDomainPreview = () => {
        if (!domainInput) {
            return;
        }
        domainInput.value = buildProjectSlug(nameInput.value) + '.' + domainSuffix;
    };

    nameInput.addEventListener('input', updateDatabasePreview);
    nameInput.addEventListener('input', updateDomainPreview);
    updateDatabasePreview();
    updateDomainPreview();
})();
JS);
?>
