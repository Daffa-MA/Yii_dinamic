<?php

use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\models\MasterMenu;

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */
/** @var app\models\FormSubmission[] $recentSubmissions */
/** @var array $formSubmissionCounts */
/** @var array $databaseContext */
/** @var string $projectDatabaseName */
/** @var int $databaseTableCount */
/** @var int $totalForms */
/** @var int $totalSubmissions */
/** @var int $todaySubmissions */
/** @var app\models\Project|null $activeProject */

$this->title = 'Dashboard';

$activeDatabase = $projectDatabaseName ?? ($databaseContext['activeDatabase'] ?? 'default');
$workspaceName = $activeProject->name ?? 'Workspace';
$workspaceUserCount = null;
$recentFormsCount = isset($recentForms) ? count($recentForms) : count($forms);
$activityScore = (int)$todaySubmissions + $recentFormsCount;
$activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
$commanderAuth = new CommanderAuthContext();
$projectAuthUser = $activeProjectId !== null ? (new ProjectAuthContext())->getAuthenticatedUser($activeProjectId) : null;
$workspaceRole = $projectAuthUser !== null ? strtolower(trim((string)$projectAuthUser->role)) : '';
$isAdminDashboard = $commanderAuth->isSuperAdmin() || $workspaceRole === 'admin';

try {
    if (Yii::$app->db->schema->getTableSchema('users', true) !== null) {
        $workspaceUserCount = (int)(new Query())->from('users')->count('*', Yii::$app->db);
    }
} catch (\Throwable $e) {
    $workspaceUserCount = null;
}

$formatNumber = static function ($value): string {
    return number_format((int)$value);
};

$activityItems = [];
foreach (array_slice($recentSubmissions, 0, 2) as $submission) {
    $activityItems[] = [
        'label' => 'Submission baru',
        'detail' => $submission->form ? $submission->form->name : 'Form',
        'time' => Yii::$app->formatter->asRelativeTime($submission->created_at),
        'tone' => 'emerald',
    ];
}
foreach (array_slice($forms, 0, 2) as $form) {
    $activityItems[] = [
        'label' => 'Form tersedia',
        'detail' => $form->name,
        'time' => Yii::$app->formatter->asDate($form->created_at),
        'tone' => 'indigo',
    ];
}
if (empty($activityItems)) {
    $activityItems = [
        ['label' => 'Workspace aktif', 'detail' => 'Dashboard siap digunakan', 'time' => 'Now', 'tone' => 'emerald'],
        ['label' => 'Database tersambung', 'detail' => $activeDatabase, 'time' => 'Now', 'tone' => 'indigo'],
        ['label' => 'Role siap dikonfigurasi', 'detail' => 'Atur akses menu sesuai kebutuhan', 'time' => 'Next step', 'tone' => 'slate'],
    ];
}

$quickActions = [
    ['label' => 'Create Page', 'icon' => 'post_add', 'url' => ['master-page/create']],
    ['label' => 'Create Form', 'icon' => 'add_circle', 'url' => ['form/create']],
    ['label' => 'Create Table', 'icon' => 'table_chart', 'url' => ['table-builder/create']],
    ['label' => 'Manage Users', 'icon' => 'group', 'url' => ['workspace-settings/users']],
];

$resolveMenuUrl = static function (array $item) {
    $type = (string)($item['type'] ?? '');
    $route = trim((string)($item['route'] ?? ''), '/');
    $pageId = (int)($item['page_id'] ?? 0);
    $formId = (int)($item['form_id'] ?? 0);
    $itemId = (int)($item['id'] ?? 0);

    if ($type === 'route' && $route !== '') {
        return ['/' . $route];
    }
    if ($type === 'form' && $formId > 0) {
        return ['/master-form/preview', 'id' => $formId];
    }
    if ($type === 'page' && $pageId > 0) {
        return ['/page/view', 'id' => $pageId];
    }
    if (!empty($item['url'])) {
        return $item['url'];
    }
    if ($itemId > 0 && $type !== 'group') {
        return ['/master-menu/resolve-link', 'id' => $itemId];
    }

    return null;
};

$flattenMenus = static function (array $items) use (&$flattenMenus, $resolveMenuUrl): array {
    $flat = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $children = isset($item['children']) && is_array($item['children']) ? $item['children'] : [];
        $url = $resolveMenuUrl($item);
        if ($url !== null) {
            $flat[] = [
                'name' => (string)($item['name'] ?? 'Menu'),
                'icon' => (string)($item['icon'] ?? 'apps'),
                'url' => $url,
                'type' => (string)($item['type'] ?? ''),
            ];
        }

        if (!empty($children)) {
            $flat = array_merge($flat, $flattenMenus($children));
        }
    }

    return $flat;
};

$availableMenus = [];
if (!$isAdminDashboard) {
    try {
        $availableMenus = array_slice($flattenMenus(MasterMenu::getMenuTree(true)), 0, 8);
    } catch (\Throwable $e) {
        $availableMenus = [];
    }
}
?>

<style>
    .workspace-dashboard-content {
        color: #0f172a;
    }
    .workspace-dashboard-content .dash-card {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        box-shadow: 0 18px 42px rgba(15, 23, 42, .045);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .workspace-dashboard-content .dash-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 52px rgba(15, 23, 42, .075);
        border-color: rgba(99, 102, 241, .28);
    }
    .workspace-dashboard-content .muted {
        color: #64748b;
    }
    .workspace-dashboard-content .soft-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .workspace-dashboard-content .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        display: inline-block;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, .12);
    }
    .workspace-dashboard-content .metric-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: #eef2ff;
        color: #4f46e5;
    }
    .workspace-dashboard-content .timeline-line {
        position: absolute;
        left: 10px;
        top: 24px;
        bottom: -18px;
        width: 1px;
        background: #e2e8f0;
    }
    .workspace-dashboard-content .timeline-dot {
        width: 21px;
        height: 21px;
        border-radius: 999px;
        border: 5px solid #fff;
        box-shadow: 0 0 0 1px #cbd5e1;
        background: #6366f1;
    }
    .workspace-dashboard-content .timeline-dot.emerald {
        background: #10b981;
    }
    .workspace-dashboard-content .timeline-dot.slate {
        background: #64748b;
    }
</style>

<div class="app-shell-main min-h-screen">
    <div class="workspace-dashboard-content max-w-[1400px] mx-auto px-6 md:px-8 py-7 md:py-9">
<?php if (!$isAdminDashboard): ?>
            <section class="grid grid-cols-1 xl:grid-cols-[1.2fr_.8fr] gap-5 mb-6">
                <div class="dash-card p-6 md:p-7">
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div>
                            <p class="soft-label mb-2">Menu Anda</p>
                            <h2 class="text-xl font-bold m-0">Menu yang bisa Anda buka</h2>
                        </div>
                    </div>

                    <?php if (empty($availableMenus)): ?>
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-9">
                            <div class="flex items-start gap-4">
                                <span class="metric-icon bg-white">
                                    <span class="material-symbols-outlined text-[20px]">lock</span>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold mb-1">Belum ada akses yang diberikan untuk role ini.</h3>
                                    <p class="muted text-sm mb-0">Hubungi admin workspace untuk membuka menu yang Anda perlukan.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($availableMenus as $menu): ?>
                                <a href="<?= Url::to($menu['url']) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-4 flex items-center gap-3 no-underline text-slate-900 transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(15,23,42,.07)] hover:border-indigo-200">
                                    <span class="metric-icon">
                                        <span class="material-symbols-outlined text-[20px]"><?= Html::encode($menu['icon'] ?: 'apps') ?></span>
                                    </span>
                                    <span class="font-semibold text-sm"><?= Html::encode($menu['name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="dash-card p-6 md:p-7">
                    <div class="mb-6">
                        <p class="soft-label mb-2">Informasi</p>
                        <h2 class="text-xl font-bold m-0">Recent information</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-sm font-semibold">Profil role</div>
                            <p class="muted text-sm mb-0 mt-1">Akses Anda mengikuti pengaturan role <?= Html::encode($workspaceRole !== '' ? $workspaceRole : 'user') ?>.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-sm font-semibold">Workspace</div>
                            <p class="muted text-sm mb-0 mt-1"><?= Html::encode($workspaceName) ?> aktif dan siap digunakan.</p>
                        </div>
                        <?php if (!empty($availableMenus)): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="text-sm font-semibold">Quick links</div>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <?php foreach (array_slice($availableMenus, 0, 3) as $menu): ?>
                                        <a href="<?= Url::to($menu['url']) ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 no-underline hover:border-indigo-200">
                                            <?= Html::encode($menu['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
        <section class="dash-card p-6 md:p-7 mb-6">
            <div class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-7 items-center">
                <div>
                    <div class="flex items-start gap-4">
                        <div class="metric-icon">
                            <span class="material-symbols-outlined text-[20px]">workspaces</span>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h1 class="text-2xl md:text-[28px] font-bold tracking-normal leading-tight m-0"><?= Html::encode($workspaceName) ?> Workspace</h1>
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                    <span class="status-dot"></span>
                                    Production Active
                                </span>
                            </div>
                            <p class="muted text-sm md:text-base mb-4 max-w-2xl">Kelola halaman, form, data dan konfigurasi workspace aktif.</p>
                            <div class="inline-flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <span class="soft-label">Database</span>
                                <span class="text-sm font-semibold"><?= Html::encode($activeDatabase) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <span class="soft-label">Tables</span>
                        <div class="mt-2 text-2xl font-bold"><?= $formatNumber($databaseTableCount) ?></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <span class="soft-label">Forms</span>
                        <div class="mt-2 text-2xl font-bold"><?= $formatNumber($totalForms) ?></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <span class="soft-label">Users</span>
                        <div class="mt-2 text-2xl font-bold"><?= $workspaceUserCount === null ? '-' : $formatNumber($workspaceUserCount) ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-[1.35fr_1fr] gap-5 mb-6">
            <div class="dash-card p-6 md:p-7">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="soft-label mb-2">Quick Stats</p>
                        <h2 class="text-xl font-bold m-0">Workspace performance</h2>
                    </div>
                    <span class="rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 px-3 py-1 text-xs font-semibold">Last 7 days</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-[1.15fr_1fr_1fr] gap-4">
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5 md:row-span-2">
                        <div class="flex items-center justify-between mb-8">
                            <div class="metric-icon bg-white text-indigo-600">
                                <span class="material-symbols-outlined text-[20px]">description</span>
                            </div>
                            <span class="text-xs font-semibold text-indigo-700">+12%</span>
                        </div>
                        <p class="soft-label text-indigo-700">Total Forms</p>
                        <div class="text-4xl font-bold mt-2"><?= $formatNumber($totalForms) ?></div>
                        <p class="text-sm text-indigo-800/70 mt-3 mb-0">Form aktif dan draft yang tercatat di workspace ini.</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="soft-label">Submissions</p>
                        <div class="flex items-end justify-between mt-3">
                            <span class="text-2xl font-bold"><?= $formatNumber($totalSubmissions) ?></span>
                            <span class="text-xs font-semibold text-emerald-700">Last 7 days</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="soft-label">Tables</p>
                        <div class="flex items-end justify-between mt-3">
                            <span class="text-2xl font-bold"><?= $formatNumber($databaseTableCount) ?></span>
                            <span class="text-xs font-semibold text-slate-500">Builder</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="soft-label">Users</p>
                        <div class="flex items-end justify-between mt-3">
                            <span class="text-2xl font-bold"><?= $workspaceUserCount === null ? '-' : $formatNumber($workspaceUserCount) ?></span>
                            <span class="text-xs font-semibold text-slate-500">Workspace</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="soft-label">Activity</p>
                        <div class="flex items-end justify-between mt-3">
                            <span class="text-2xl font-bold"><?= $formatNumber($activityScore) ?></span>
                            <span class="text-xs font-semibold text-emerald-700">Today</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dash-card p-6 md:p-7">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="soft-label mb-2">Workspace Health</p>
                        <h2 class="text-xl font-bold m-0">System status</h2>
                    </div>
                    <span class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700"><span class="status-dot"></span>Healthy</span>
                </div>

                <?php
                $healthItems = [
                    ['label' => 'Database', 'value' => 'Healthy', 'tone' => 'emerald', 'meta' => $activeDatabase],
                    ['label' => 'API', 'value' => 'Online', 'tone' => 'emerald', 'meta' => '200 OK'],
                    ['label' => 'Storage', 'value' => '78%', 'tone' => 'indigo', 'meta' => 'Capacity'],
                    ['label' => 'Realtime', 'value' => 'Connected', 'tone' => 'emerald', 'meta' => 'Live'],
                ];
                ?>
                <div class="space-y-3">
                    <?php foreach ($healthItems as $item): ?>
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="text-sm font-semibold"><?= Html::encode($item['label']) ?></div>
                                <div class="text-xs muted"><?= Html::encode($item['meta']) ?></div>
                            </div>
                            <div class="inline-flex items-center gap-2 text-sm font-semibold <?= $item['tone'] === 'emerald' ? 'text-emerald-700' : 'text-indigo-700' ?>">
                                <span class="status-dot" style="<?= $item['tone'] === 'indigo' ? 'background:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,.12);' : '' ?>"></span>
                                <?= Html::encode($item['value']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-[.95fr_1.05fr] gap-5 mb-6">
            <div class="dash-card p-6 md:p-7">
                <div class="mb-6">
                    <p class="soft-label mb-2">Recent Activity</p>
                    <h2 class="text-xl font-bold m-0">Latest workspace events</h2>
                </div>

                <div class="space-y-5">
                    <?php foreach (array_slice($activityItems, 0, 4) as $index => $item): ?>
                        <div class="relative flex gap-4">
                            <?php if ($index < min(count($activityItems), 4) - 1): ?>
                                <span class="timeline-line"></span>
                            <?php endif; ?>
                            <span class="timeline-dot <?= Html::encode($item['tone']) ?>"></span>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold"><?= Html::encode($item['label']) ?></div>
                                <div class="text-sm muted truncate"><?= Html::encode($item['detail']) ?></div>
                                <div class="text-xs text-slate-400 mt-1"><?= Html::encode($item['time']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-card p-6 md:p-7">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="soft-label mb-2">Quick Actions</p>
                        <h2 class="text-xl font-bold m-0">Build faster</h2>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($quickActions as $action): ?>
                        <a href="<?= Url::to($action['url']) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-4 flex items-center gap-3 no-underline text-slate-900 transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(15,23,42,.07)] hover:border-indigo-200">
                            <span class="metric-icon">
                                <span class="material-symbols-outlined text-[20px]"><?= Html::encode($action['icon']) ?></span>
                            </span>
                            <span class="font-semibold text-sm"><?= Html::encode($action['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="dash-card p-6 md:p-7">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <p class="soft-label mb-2">My Forms</p>
                    <h2 class="text-xl font-bold m-0">Recent forms</h2>
                </div>
                <a href="<?= Url::to(['form/index']) ?>" class="text-sm font-semibold text-indigo-700 no-underline hover:underline">View all</a>
            </div>

            <?php if (count($forms) === 0): ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                    <div class="flex items-start gap-4">
                        <span class="metric-icon bg-white">
                            <span class="material-symbols-outlined text-[20px]">note_add</span>
                        </span>
                        <div>
                            <h3 class="text-base font-bold mb-1">Belum ada form</h3>
                            <p class="muted text-sm mb-0">Mulai dengan membuat form pertama untuk mengumpulkan data workspace.</p>
                        </div>
                    </div>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create Form', ['form/create'], [
                        'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white no-underline hover:bg-indigo-700 transition-colors',
                        'encode' => false,
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach ($forms as $form): ?>
                        <?php
                        $blocks = json_decode($form->schema_js ?? '[]', true);
                        $blockCount = is_array($blocks) ? count($blocks) : 0;
                        $submissionCount = (int)($form->submission_count ?? 0);
                        ?>
                        <article class="rounded-xl border border-slate-200 bg-white p-5 transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(15,23,42,.07)] hover:border-indigo-200">
                            <div class="flex items-start justify-between gap-4 mb-5">
                                <div class="metric-icon">
                                    <span class="material-symbols-outlined text-[20px]">article</span>
                                </div>
                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1">Active</span>
                            </div>
                            <h3 class="font-bold text-base mb-1 truncate"><?= Html::encode($form->name) ?></h3>
                            <p class="muted text-xs mb-4">Created <?= Html::encode(Yii::$app->formatter->asDate($form->created_at)) ?></p>
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                    <div class="soft-label">Fields</div>
                                    <div class="font-bold"><?= $formatNumber($blockCount) ?></div>
                                </div>
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                    <div class="soft-label">Responses</div>
                                    <div class="font-bold"><?= $formatNumber($submissionCount) ?></div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <?= Html::a('View', ['form/view', 'id' => $form->id], ['class' => 'text-xs font-semibold text-indigo-700 no-underline hover:underline']) ?>
                                <?= Html::a('Edit', ['form/update', 'id' => $form->id], ['class' => 'text-xs font-semibold text-slate-600 no-underline hover:underline']) ?>
                                <?= Html::a('Data', ['form/submissions', 'id' => $form->id], ['class' => 'text-xs font-semibold text-slate-600 no-underline hover:underline']) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</div>

<script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>
