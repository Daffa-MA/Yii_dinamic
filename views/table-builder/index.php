<?php

/** @var yii\web\View $this */
/** @var array $tables */
/** @var array $databaseInfo */
/** @var string|null $search */

use yii\bootstrap5\Html;

$this->title = 'Database Tables';
$tableCategory = $tableCategory ?? 'user';
$tableCategoryOptions = [
    'user' => 'User Tables',
    'system' => 'System Tables',
    'all' => 'All Tables',
];
$this->registerJs("document.body.classList.add('dashboard-main-page');", \yii\web\View::POS_READY);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

$tableCount = count($tables);
$createdCount = count(array_filter($tables, static function ($item) {
    return (($item->statusMeta->code ?? '') === 'active');
}));
$failedCount = count(array_filter($tables, static function ($item) {
    return (($item->statusMeta->code ?? '') === 'failed');
}));
$pendingCount = count(array_filter($tables, static function ($item) {
    return in_array(($item->statusMeta->code ?? ''), ['pending', 'incomplete'], true);
}));
$totalColumns = array_sum(array_map(static function ($item) {
    return count($item->columns);
}, $tables));
$databaseInfo = $databaseInfo ?? [];
$databaseName = $databaseInfo['name'] ?? null;
$databaseHost = $databaseInfo['host'] ?? null;
$databasePort = $databaseInfo['port'] ?? null;
$tableBuilderSuccess = Yii::$app->session->getFlash('tableBuilderSuccess');
$tableBuilderError = Yii::$app->session->getFlash('tableBuilderError');
$tableBuilderWarning = Yii::$app->session->getFlash('tableBuilderWarning');
?>

<style>
.table-index-page {
    --ink: #0f172a;
    --muted: #64748b;
    --line: #dbe3ee;
    --panel: rgba(255, 255, 255, 0.92);
    --panel-strong: #ffffff;
    --accent: var(--ws-sidebar-active-bg-start, #2563eb);
    --accent-strong: var(--ws-sidebar-active-bg-end, #1d4ed8);
    --accent-soft: var(--ws-sidebar-hover-bg, rgba(37, 99, 235, 0.12));
    --accent-ghost: var(--ws-light-sidebar-bg, #eff6ff);
    --success: #166534;
    --success-soft: #dcfce7;
    --warning: #92400e;
    --warning-soft: #fef3c7;
    --danger: #b91c1c;
    --danger-soft: #fee2e2;
    --shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
    --shadow-hover: 0 24px 60px rgba(15, 23, 42, 0.1);
    color: var(--ink);
}

.table-index-main {
    width: 100vw;
    min-height: 100vh;
    margin-left: calc(50% - 50vw);
}

body.has-app-sidebar .table-index-main {
    width: calc(100vw - var(--app-sidebar-width, 16rem));
    margin-left: calc(50% - 50vw + var(--app-sidebar-width, 16rem));
}

.table-index-content {
    width: 100%;
    max-width: 1620px;
    margin: 0 auto;
    padding: 30px clamp(20px, 2.4vw, 42px) 42px;
}

main#main > .container > .alert {
    margin-left: var(--app-sidebar-width, 16rem);
    margin-top: 1.5rem;
    width: calc(100% - var(--app-sidebar-width, 16rem));
    transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1), width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.table-index-page .page-shell {
    display: grid;
    gap: 22px;
}

.table-index-page .hero,
.table-index-page .panel,
.table-index-page .table-card {
    background: var(--panel);
    border: 1px solid rgba(219, 227, 238, 0.95);
    border-radius: 22px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
}

.table-index-page .hero {
    padding: 26px;
    background:
        radial-gradient(circle at top left, var(--accent-soft), transparent 25%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
}

.table-index-page .hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 22px;
}

.table-index-page .hero-title {
    display: flex;
    gap: 14px;
    align-items: flex-start;
}

.table-index-page .hero-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--accent-ghost), var(--accent-soft));
    color: var(--accent);
    border: 1px solid var(--line);
}

.table-index-page h1 {
    margin: 0 0 10px;
    font-size: clamp(30px, 2.6vw, 38px);
    line-height: 1.04;
    font-weight: 850;
    letter-spacing: -0.04em;
}

.table-index-page .hero-text,
.table-index-page .stat-note,
.table-index-page .panel-subtitle,
.table-index-page .empty-text,
.table-index-page .card-description,
.table-index-page .meta-label {
    color: var(--muted);
}

.table-index-page .hero-text {
    margin: 0;
    max-width: 820px;
    font-size: 14px;
    line-height: 1.7;
}

.table-index-page .hero-actions,
.table-index-page .meta-chips,
.table-index-page .column-chips {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.table-index-page .hero-actions {
    justify-content: flex-end;
}

.table-index-page .btn-clean {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 12px;
    padding: 11px 15px;
    border: 1px solid rgba(219, 227, 238, 0.95);
    background: rgba(255, 255, 255, 0.96);
    color: var(--ink);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.table-index-page .btn-clean:hover {
    border-color: #bfd0e6;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(20, 32, 51, 0.08);
}

.table-index-page .btn-primary-clean {
    background: linear-gradient(135deg, var(--accent-strong), var(--accent));
    border-color: var(--accent-strong);
    color: var(--ws-sidebar-active-text, #fff);
}

.table-index-page .btn-primary-clean:hover {
    color: #fff;
}

.table-index-page .action-btn {
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid transparent;
    line-height: 1;
}

.table-index-page .action-btn .material-symbols-outlined {
    font-size: 16px;
}

.table-index-page .action-view {
    background: var(--accent-ghost);
    color: var(--accent-strong);
    border-color: var(--accent-soft);
}

.table-index-page .action-view:hover {
    background: var(--accent-soft);
    color: var(--accent-strong);
}

.table-index-page .action-edit {
    background: #f8fafc;
    color: #334155;
    border-color: #e2e8f0;
}

.table-index-page .action-edit:hover {
    background: #eef2f7;
    color: #0f172a;
}

.table-index-page .action-delete {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}

.table-index-page .action-delete:hover {
    background: #fee2e2;
    color: #991b1b;
}

.table-index-page .action-create {
    background: #ecfeff;
    color: #0f766e;
    border-color: #cffafe;
}

.table-index-page .action-create:hover {
    background: #cffafe;
    color: #115e59;
}

.table-index-page .hero-stats {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 14px;
}

.table-index-page .stat-card {
    padding: 17px 18px;
    border-radius: 18px;
    border: 1px solid rgba(231, 237, 245, 0.95);
    background: rgba(255, 255, 255, 0.88);
}

.table-index-page .stat-label {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted);
    font-weight: 700;
    margin-bottom: 10px;
}

.table-index-page .stat-value {
    font-size: 26px;
    line-height: 1;
    font-weight: 850;
    margin-bottom: 6px;
}

.table-index-page .stat-note {
    margin: 0;
    font-size: 13px;
}

.table-index-page .panel-header {
    padding: 22px 24px;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(249, 251, 253, 0.96));
}

.table-index-page .panel-title {
    margin: 0 0 6px;
    font-size: clamp(18px, 1.45vw, 22px);
    font-weight: 800;
    letter-spacing: -0.03em;
}

.table-index-page .panel-subtitle {
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
}

.table-index-page .panel-body {
    padding: 24px;
}

.table-index-page .table-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    align-items: stretch;
}

.table-index-page .table-card {
    padding: 22px;
    display: grid;
    gap: 16px;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    align-content: start;
    min-height: 100%;
    overflow: hidden;
}

.table-index-page .table-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(191, 208, 230, 0.9);
}

.table-index-page .card-top {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
}

.table-index-page .card-top > div:first-child {
    min-width: 0;
    flex: 1;
}

.table-index-page .card-title {
    margin: 0 0 6px;
    font-size: 20px;
    line-height: 1.1;
    font-weight: 820;
    letter-spacing: -0.03em;
    overflow-wrap: anywhere;
}

.table-index-page .card-name {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 11px;
    color: #475569;
    background: #f3f7fb;
    border-radius: 999px;
    padding: 5px 10px;
    border: 1px solid #e2e8f0;
}

.table-index-page .card-description {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
    min-height: 4.2em;
}

.table-index-page .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: normal;
    flex-shrink: 0;
    max-width: 100%;
}

.table-index-page .status-created {
    background: var(--success-soft);
    color: var(--success);
}

.table-index-page .status-pending {
    background: var(--warning-soft);
    color: var(--warning);
}

.table-index-page .status-failed {
    background: var(--danger-soft);
    color: var(--danger);
}

.table-index-page .meta-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.table-index-page .meta-box {
    border: 1px solid #e8eef6;
    border-radius: 16px;
    padding: 14px 15px;
    background: linear-gradient(180deg, #ffffff, #fbfdff);
}

.table-index-page .meta-value {
    display: block;
    font-size: 18px;
    line-height: 1;
    font-weight: 800;
    margin-top: 4px;
    overflow-wrap: anywhere;
}

.table-index-page .column-chip {
    padding: 6px 10px;
    border-radius: 999px;
    background: var(--accent-ghost);
    color: var(--accent-strong);
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--accent-soft);
}

.table-index-page .structure-section {
    border: 1px solid #e6edf5;
    border-radius: 18px;
    background: linear-gradient(180deg, #fcfdff, #f7fafc);
    overflow: hidden;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.table-index-page .structure-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    padding: 13px 16px;
    border-bottom: 1px solid #e6edf5;
    background: rgba(255, 255, 255, 0.86);
    position: sticky;
    top: 0;
    z-index: 1;
    backdrop-filter: blur(8px);
}

.table-index-page .structure-title {
    margin: 0;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #475569;
}

.table-index-page .structure-count {
    font-size: 12px;
    color: var(--muted);
    font-weight: 700;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 5px 9px;
    white-space: nowrap;
}

.table-index-page .structure-list {
    max-height: 220px;
    overflow: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.table-index-page .structure-list::-webkit-scrollbar {
    width: 8px;
}

.table-index-page .structure-list::-webkit-scrollbar-track {
    background: transparent;
}

.table-index-page .structure-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.table-index-page .structure-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.table-index-page .structure-row {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 0.95fr) minmax(0, 0.95fr) auto;
    gap: 10px;
    align-items: start;
    padding: 12px 16px;
    border-bottom: 1px solid #edf2f7;
}

.table-index-page .structure-row:last-child {
    border-bottom: none;
}

.table-index-page .column-name {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.table-index-page .column-name strong {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 13px;
    line-height: 1.25;
    color: var(--ink);
    overflow-wrap: anywhere;
}

.table-index-page .column-name span {
    font-size: 12px;
    color: var(--muted);
    overflow-wrap: anywhere;
    line-height: 1.45;
}

.table-index-page .column-type,
.table-index-page .column-default {
    font-size: 12px;
    color: var(--ink);
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 10px;
    min-width: 0;
    overflow-wrap: anywhere;
    line-height: 1.5;
}

.table-index-page .column-default {
    color: #334155;
}

.table-index-page .column-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
    padding-top: 1px;
}

.table-index-page .column-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.04em;
    background: var(--accent-ghost);
    color: var(--accent-strong);
    border: 1px solid var(--accent-soft);
}

.table-index-page .column-badge.pk {
    background: #fef9c3;
    color: #854d0e;
    border-color: #fde68a;
}

.table-index-page .column-badge.ai {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}

.table-index-page .column-badge.fk {
    background: #e0f2fe;
    color: #075985;
    border-color: #bae6fd;
}

.table-index-page .column-badge.null {
    background: #f8fafc;
    color: #475569;
    border-color: #e2e8f0;
}

.table-index-page .btn-danger-clean:hover {
    border-color: #efb0b0;
    background: #fff7f7;
    color: var(--danger);
}

.table-index-page .card-actions {
    margin-top: 2px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding-top: 2px;
}

.table-index-page .empty-state {
    text-align: center;
    padding: 52px 24px;
    border: 1px dashed #cbd5e1;
    background:
        radial-gradient(circle at top, rgba(37, 99, 235, 0.05), transparent 34%),
        linear-gradient(180deg, #ffffff, #f8fafc);
    border-radius: 22px;
}

.table-index-page .empty-state .material-symbols-outlined {
    font-size: 42px;
    color: #94a3b8;
    margin-bottom: 12px;
}

.table-index-page .empty-title {
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 800;
}

.table-index-page .empty-text {
    margin: 0 0 18px;
    font-size: 14px;
    line-height: 1.65;
}

@media (max-width: 1100px) {
    body.has-app-sidebar .table-index-main,
    .table-index-main {
        width: 100vw;
        margin-left: calc(50% - 50vw);
    }

    main#main > .container > .alert {
        margin-left: 0;
        width: 100%;
    }

    .table-index-page .hero-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .table-index-page .table-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .table-index-content {
        padding: 20px 16px 28px;
    }

    .table-index-page .hero,
    .table-index-page .panel-body,
    .table-index-page .table-card {
        padding: 20px;
    }

    .table-index-page .hero-top,
    .table-index-page .card-top {
        flex-direction: column;
    }

    .table-index-page .hero-actions {
        justify-content: flex-start;
    }

    .table-index-page .hero-stats,
    .table-index-page .meta-grid {
        grid-template-columns: 1fr;
    }

    .table-index-page .structure-row {
        grid-template-columns: 1fr;
    }

    .table-index-page .column-badges {
        justify-content: flex-start;
    }

    .table-index-page .structure-list {
        max-height: 260px;
    }
}
</style>

<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'on-surface': '#0b1c30',
                    'on-surface-variant': '#464555',
                    'surface': '#fafbfe',
                    'surface-container-lowest': '#ffffff',
                    'surface-container-low': '#f8fafd',
                    'surface-container': '#f0f4f9',
                    'surface-container-high': '#e8eef7',
                    'primary-container': '#4f46e5',
                    'primary': '#3525cd',
                    'secondary': '#006c49',
                    'tertiary': '#7e3000',
                    'surface-tint': '#4d44e3',
                    'outline-variant': '#c7c4d8',
                    'outline': '#777587',
                    'error': '#ba1a1a',
                },
                fontFamily: {
                    'headline': ['Manrope'],
                    'body': ['Inter'],
                }
            }
        }
    }
</script>

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>

<body class="font-body text-on-surface" style="background: linear-gradient(135deg, var(--ws-light-sidebar-bg, #f9fafb) 0%, #f8fafc 55%, var(--ws-sidebar-hover-bg, #ede9fe) 100%); background-attachment: fixed;">
<main class="table-index-main app-shell-main pt-6 min-h-screen">
    <div class="table-index-content">
        <div class="table-index-page">
            <div class="page-shell">
        <?php if (!empty($tableBuilderSuccess)): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <?= Html::encode(is_array($tableBuilderSuccess) ? implode(' ', $tableBuilderSuccess) : $tableBuilderSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($tableBuilderError)): ?>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <?= Html::encode(is_array($tableBuilderError) ? implode(' ', $tableBuilderError) : $tableBuilderError) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($tableBuilderWarning)): ?>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <?= Html::encode(is_array($tableBuilderWarning) ? implode(' ', $tableBuilderWarning) : $tableBuilderWarning) ?>
            </div>
        <?php endif; ?>
        <section class="hero">
            <div class="hero-top">
                <div class="hero-title">
                    <div class="hero-icon">
                        <span class="material-symbols-outlined">table_chart</span>
                    </div>
                    <div>
                        <h1>Database Tables</h1>
                        <p class="hero-text">Manage actual table definitions saved in the application. Default view shows user tables and visible core tables; system tables stay hidden unless selected from the filter.</p>
                    </div>
                </div>

                <div class="hero-actions">
                    <?php if ((new \app\components\CommanderAuthContext())->isSuperAdmin()): ?>
                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">folder_open</span> Projects', (new \app\components\DomainContext())->projectListUrl(), [
                            'class' => 'btn-clean',
                            'encode' => false,
                        ]) ?>
                    <?php endif; ?>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">sync</span> Sync From Database', ['table-builder/sync-from-database'], [
                        'class' => 'btn-clean',
                        'encode' => false,
                        'data' => [
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a('Create Table', ['table-builder/create'], ['class' => 'btn-clean btn-primary-clean']) ?>
                </div>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-label">Tables</span>
                    <div class="stat-value"><?= $tableCount ?></div>
                    <p class="stat-note"><?= Html::encode($tableCategoryOptions[$tableCategory] ?? 'Filtered Tables') ?></p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Created</span>
                    <div class="stat-value"><?= $createdCount ?></div>
                    <p class="stat-note">Already available in SQL database</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Pending</span>
                    <div class="stat-value"><?= $pendingCount ?></div>
                    <p class="stat-note">Pending atau metadata belum lengkap</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Failed</span>
                    <div class="stat-value"><?= $failedCount ?></div>
                    <p class="stat-note">Masih tampil dan bisa dihapus dari UI</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Columns</span>
                    <div class="stat-value"><?= $totalColumns ?></div>
                    <p class="stat-note">Total columns across all definitions</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Database</span>
                    <div class="stat-value"><?= Html::encode($databaseName ?: '-') ?></div>
                    <p class="stat-note"><?= Html::encode($databaseHost ? ($databaseHost . ($databasePort ? ':' . $databasePort : '')) : '-') ?></p>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Table List</h2>
                    <p class="panel-subtitle">Only real table metadata is shown here. No dummy analytics, no placeholder cards.</p>
                </div>
                <div class="hero-actions">
                    <?php foreach ($tableCategoryOptions as $categoryKey => $categoryLabel): ?>
                        <?= Html::a($categoryLabel, ['table-builder/index', 'table_category' => $categoryKey], [
                            'class' => 'btn-clean' . ($tableCategory === $categoryKey ? ' btn-primary-clean' : ''),
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="px-6 pt-3 pb-1">
                <form method="get" action="<?= Yii::$app->urlManager->createUrl(['table-builder/index']) ?>" class="flex gap-2">
                    <input type="hidden" name="table_category" value="<?= Html::encode($tableCategory) ?>">
                    <input type="text" name="q" value="<?= Html::encode($search ?? '') ?>" placeholder="Cari table..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" autocomplete="off">
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white">Cari</button>
                    <?php if (!empty($search)): ?>
                        <?= Html::a('Clear', ['table-builder/index', 'table_category' => $tableCategory], ['class' => 'rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600 no-underline']) ?>
                    <?php endif; ?>
                </form>
            </div>
            <div class="panel-body">
                <?php if (empty($tables)): ?>
                    <div class="empty-state">
                        <span class="material-symbols-outlined">database</span>
                        <p class="empty-title">No tables created yet</p>
                        <p class="empty-text">Start by creating the first table definition for your project.</p>
                        <?= Html::a('Create First Table', ['table-builder/create'], ['class' => 'btn-clean btn-primary-clean']) ?>
                    </div>
                <?php else: ?>
                    <div class="table-grid">
                        <?php foreach ($tables as $item): ?>
                            <?php
                            $table = $item->table;
                            $columns = $item->columns;
                            $statusMeta = $item->statusMeta ?? (object)[
                                'code' => ((bool)$table->is_created ? 'active' : 'pending'),
                                'label' => ((bool)$table->is_created ? 'Active' : 'Pending'),
                                'note' => '',
                            ];
                            $statusCode = (string)($statusMeta->code ?? 'pending');
                            $statusClass = $statusCode === 'active'
                                ? 'status-pill status-created'
                                : ($statusCode === 'failed' ? 'status-pill status-failed' : 'status-pill status-pending');
                            $statusIcon = $statusCode === 'active' ? 'check_circle' : ($statusCode === 'failed' ? 'error' : 'schedule');
                            $statusLabel = (string)($statusMeta->label ?? ($statusCode === 'active' ? 'Active' : 'Pending'));
                            ?>
                            <article class="table-card">
                                <div class="card-top">
                                    <div>
                                        <h3 class="card-title"><?= Html::encode($table->label ?: $table->name) ?></h3>
                                        <span class="card-name"><?= Html::encode($table->name) ?></span>
                                    </div>
                                    <div class="<?= $statusClass ?>">
                                        <span class="material-symbols-outlined" style="font-size:16px;"><?= $statusIcon ?></span>
                                        <?= Html::encode($statusLabel) ?>
                                    </div>
                                </div>

                                <?php if (!empty($table->description)): ?>
                                    <p class="card-description"><?= Html::encode($table->description) ?></p>
                                <?php else: ?>
                                    <p class="card-description">No description provided for this table definition.</p>
                                <?php endif; ?>
                                <?php if (!empty($statusMeta->note ?? '')): ?>
                                    <p class="card-description" style="min-height:auto;color:<?= $statusCode === 'failed' ? '#b91c1c' : '#64748b' ?>;"><?= Html::encode((string)$statusMeta->note) ?></p>
                                <?php endif; ?>

                                <div class="meta-grid">
                                    <div class="meta-box">
                                        <div class="meta-label">Columns</div>
                                        <span class="meta-value"><?= count($columns) ?></span>
                                    </div>
                                    <div class="meta-box">
                                        <div class="meta-label">Engine</div>
                                        <span class="meta-value" style="font-size:18px;"><?= Html::encode($table->engine) ?></span>
                                    </div>
                                    <div class="meta-box">
                                        <div class="meta-label">Created</div>
                                        <span class="meta-value" style="font-size:18px;"><?= Html::encode(date('d M Y', strtotime($table->created_at))) ?></span>
                                    </div>
                                </div>

                                <div class="column-chips">
                                    <?php foreach (array_slice($columns, 0, 4) as $column): ?>
                                        <span class="column-chip"><?= Html::encode($column->name) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($columns) > 4): ?>
                                        <span class="column-chip">+<?= count($columns) - 4 ?> more</span>
                                    <?php endif; ?>
                                </div>

                                <div class="structure-section">
                                    <div class="structure-header">
                                        <h4 class="structure-title">Struktur Kolom</h4>
                                        <span class="structure-count"><?= count($columns) ?> columns</span>
                                    </div>
                                    <div class="structure-list">
                                        <?php foreach ($columns as $column): ?>
                                            <?php
                                            $isPrimary = (bool)($column->is_primary ?? false);
                                            $isAutoIncrement = (bool)($column->hasAttribute('is_auto_increment') ? $column->getAttribute('is_auto_increment') : false);
                                            $isForeignKey = (bool)($column->hasAttribute('is_foreign_key') ? $column->getAttribute('is_foreign_key') : false);
                                            $nullableLabel = (bool)$column->is_nullable ? 'NULL' : 'NOT NULL';
                                            $defaultValue = $column->default_value !== null && $column->default_value !== ''
                                                ? (string)$column->default_value
                                                : 'no default';
                                            $displayType = trim((string)$column->type);
                                            if ((string)$column->length !== '') {
                                                $displayType .= '(' . (string)$column->length . ')';
                                            }
                                            ?>
                                            <div class="structure-row">
                                                <div class="column-name">
                                                    <strong><?= Html::encode($column->name) ?></strong>
                                                    <span><?= Html::encode($column->label ?: $column->name) ?></span>
                                                </div>
                                                <div class="column-type">
                                                    <?= Html::encode($displayType ?: '-') ?><br>
                                                    <span style="color:<?= $column->is_nullable ? '#16a34a' : '#b45309' ?>;font-weight:700;"><?= Html::encode($nullableLabel) ?></span>
                                                </div>
                                                <div class="column-default">
                                                    Default: <?= Html::encode($defaultValue) ?>
                                                </div>
                                                <div class="column-badges">
                                                    <?php if ($isPrimary): ?>
                                                        <span class="column-badge pk">PK</span>
                                                    <?php endif; ?>
                                                    <?php if ($isAutoIncrement): ?>
                                                        <span class="column-badge ai">AI</span>
                                                    <?php endif; ?>
                                                    <?php if ($isForeignKey): ?>
                                                        <span class="column-badge fk">FK</span>
                                                    <?php endif; ?>
                                                    <span class="column-badge null"><?= Html::encode($nullableLabel) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="card-actions">
                                    <?= Html::a('<span class="material-symbols-outlined">visibility</span><span>View</span>', ['table-builder/view', 'id' => $table->id], [
                                        'class' => 'btn-clean action-btn action-view',
                                        'encode' => false,
                                    ]) ?>
                                    <?= Html::a('<span class="material-symbols-outlined">edit</span><span>Edit</span>', ['table-builder/update', 'id' => $table->id], [
                                        'class' => 'btn-clean action-btn action-edit',
                                        'encode' => false,
                                    ]) ?>
                                    <?php if (!$table->is_created): ?>
                                        <?= Html::a('<span class="material-symbols-outlined">database</span><span>Create in DB</span>', ['table-builder/execute-sql', 'id' => $table->id], [
                                            'class' => 'btn-clean action-btn action-create',
                                            'encode' => false,
                                            'data' => [
                                                'confirm' => 'Create this table in the database?',
                                                'method' => 'post',
                                            ],
                                        ]) ?>
                                    <?php endif; ?>
                                    <?= Html::a('<span class="material-symbols-outlined">delete</span><span>Delete</span>', ['table-builder/delete', 'id' => $table->id], [
                                        'class' => 'btn-clean action-btn action-delete',
                                        'encode' => false,
                                        'data' => [
                                            'confirm' => 'Are you sure you want to delete this table? All related metadata will be removed.',
                                            'method' => 'post',
                                        ],
                                    ]) ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
            </div>
        </div>
    </div>
</main>
</body>
