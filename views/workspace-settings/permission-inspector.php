<?php

/** @var yii\web\View $this */
/** @var array $roles */
/** @var string $selectedRoleName */
/** @var array|null $selectedRole */
/** @var array $rolePermissions */
/** @var array $groupedPermissions */
/** @var array $allGroupedPermissions */
/** @var array $hiddenGroupedPermissions */
/** @var array $allPermissions */
/** @var array $rolePermissionCounts */
/** @var array $stats */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Akses Role';

$roles = is_array($roles ?? null) ? $roles : [];
$selectedRole = $selectedRole ?? null;
$groupedPermissions = is_array($groupedPermissions ?? null) ? $groupedPermissions : [];
$allGroupedPermissions = is_array($allGroupedPermissions ?? null) ? $allGroupedPermissions : [];
$hiddenGroupedPermissions = is_array($hiddenGroupedPermissions ?? null) ? $hiddenGroupedPermissions : [];
$allPermissions = is_array($allPermissions ?? null) ? $allPermissions : [];
$rolePermissionCounts = is_array($rolePermissionCounts ?? null) ? $rolePermissionCounts : [];
$rolePermissions = is_array($rolePermissions ?? null) ? $rolePermissions : [];
$stats = is_array($stats ?? null) ? $stats : ['total' => 0, 'menu' => 0, 'page' => 0, 'form' => 0, 'route' => 0, 'builder' => 0];

$roleName = (string)($selectedRole['name'] ?? $selectedRoleName);
$roleDescription = (string)($selectedRole['description'] ?? 'Tidak ada deskripsi role.');
$hasData = !empty($allPermissions);

$visibleMenu = $groupedPermissions['menu'] ?? [];
$visiblePage = $groupedPermissions['page'] ?? [];
$visibleForm = $groupedPermissions['form'] ?? [];
$visibleRoute = $groupedPermissions['route'] ?? [];
$visibleBuilder = $groupedPermissions['builder'] ?? [];
$visibleFeature = $groupedPermissions['feature'] ?? [];

$hiddenMenu = $hiddenGroupedPermissions['menu'] ?? [];
$hiddenPage = $hiddenGroupedPermissions['page'] ?? [];
$hiddenForm = $hiddenGroupedPermissions['form'] ?? [];
$hiddenRoute = $hiddenGroupedPermissions['route'] ?? [];
$hiddenBuilder = $hiddenGroupedPermissions['builder'] ?? [];
$hiddenFeature = $hiddenGroupedPermissions['feature'] ?? [];

$roleCount = count($roles);

function inspector_chip_style(string $variant): array
{
    switch ($variant) {
        case 'accent':
            return ['bg' => '#eff6ff', 'bd' => '#dbeafe', 'fg' => '#1d4ed8'];
        case 'success':
            return ['bg' => '#ecfdf5', 'bd' => '#d1fae5', 'fg' => '#047857'];
        case 'warning':
            return ['bg' => '#fff7ed', 'bd' => '#fed7aa', 'fg' => '#c2410c'];
        case 'danger':
            return ['bg' => '#fef2f2', 'bd' => '#fecaca', 'fg' => '#b91c1c'];
        default:
            return ['bg' => '#f8fafc', 'bd' => '#e2e8f0', 'fg' => '#334155'];
    }
}

function render_inspector_chips(array $items, string $variant = 'default', int $limit = 12): string
{
    $style = inspector_chip_style($variant);
    if (empty($items)) {
        return '<span style="color:#64748b;font-size:.9rem;">Tidak ada item.</span>';
    }
    $html = [];
    foreach (array_slice($items, 0, $limit) as $item) {
        $label = (string)($item['label'] ?? '');
        if ($label === '') {
            continue;
        }
        $html[] = '<span class="inspector-chip" style="display:inline-flex;align-items:center;min-height:28px;padding:0 10px;border-radius:999px;background:' . Html::encode($style['bg']) . ';border:1px solid ' . Html::encode($style['bd']) . ';color:' . Html::encode($style['fg']) . ';font-size:.78rem;font-weight:800;">' . Html::encode($label) . '</span>';
    }
    return $html ? implode('', $html) : '<span style="color:#64748b;font-size:.9rem;">Tidak ada item.</span>';
}

?>

<div class="workspace-inspector-page">
    <style>
        .workspace-inspector-page {
            min-height: 100vh;
            padding: 28px;
            background: #f6f8fb;
            color: #0f172a;
            font-family: Inter, system-ui, sans-serif;
        }
        .inspector-shell {
            max-width: 1560px;
            margin: 0 auto;
        }
        .hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .eyebrow {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #64748b;
        }
        .title {
            margin: 6px 0 0;
            font-size: clamp(1.8rem, 2vw, 2.35rem);
            letter-spacing: -.04em;
            line-height: 1.1;
            font-weight: 850;
        }
        .subtitle {
            margin: 10px 0 0;
            max-width: 72rem;
            color: #64748b;
            line-height: 1.7;
        }
        .hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid #d7dce5;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 1px 0 rgba(15, 23, 42, .02);
        }
        .nav-pill.primary {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }
        .metric-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .metric {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0 12px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: .88rem;
            font-weight: 700;
        }
        .layout {
            display: grid;
            grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }
        .panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .04);
        }
        .panel-inner {
            padding: 18px;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }
        .section-head h2, .section-head h3 {
            margin: 0;
            font-weight: 800;
            color: #0f172a;
        }
        .section-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: .9rem;
            line-height: 1.6;
        }
        .help-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            font-weight: 800;
            flex: 0 0 auto;
        }
        .input {
            width: 100%;
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid #d7dce5;
            background: #fff;
            color: #0f172a;
            padding: 0 14px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .input:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, .14);
        }
        .role-list {
            display: grid;
            gap: 10px;
            max-height: 72vh;
            overflow: auto;
            padding-right: 2px;
        }
        .role-card {
            display: block;
            text-decoration: none;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, .03);
        }
        .role-card.active {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .role-card:hover {
            border-color: #cbd5e1;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: .77rem;
            font-weight: 800;
        }
        .badge.accent {
            background: #eff6ff;
            border-color: #dbeafe;
            color: #1d4ed8;
        }
        .badge.success {
            background: #ecfdf5;
            border-color: #d1fae5;
            color: #047857;
        }
        .badge.warning {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }
        .badge.muted {
            color: #64748b;
        }
        .small-note {
            color: #64748b;
            font-size: .84rem;
            line-height: 1.5;
        }
        .search-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
        }
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .preview-card {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #fff;
            padding: 16px;
        }
        .preview-card h4 {
            margin: 0;
            font-size: .98rem;
            font-weight: 800;
        }
        .preview-card p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: .88rem;
            line-height: 1.6;
        }
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .empty-state {
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed #d7dce5;
            background: #fbfdff;
            color: #64748b;
            line-height: 1.7;
        }
        .group-stack {
            display: grid;
            gap: 14px;
        }
        .group-card {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
        }
        .group-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            background: #fbfdff;
        }
        .group-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: #0f172a;
        }
        .group-body {
            padding: 16px;
        }
        .search-bar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            margin-bottom: 14px;
        }
        .button {
            min-height: 40px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid #d7dce5;
            background: #fff;
            color: #0f172a;
            font-weight: 700;
            cursor: pointer;
        }
        .button.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .button.ghost {
            background: #f8fafc;
        }
        .button.link {
            background: #fff;
        }
        .sticky-panel {
            position: sticky;
            top: 18px;
        }
        @media (max-width: 1180px) {
            .layout {
                grid-template-columns: 1fr;
            }
            .preview-grid {
                grid-template-columns: 1fr;
            }
            .sticky-panel {
                position: static;
            }
        }
        @media (max-width: 720px) {
            .workspace-inspector-page {
                padding: 16px;
            }
            .search-row {
                grid-template-columns: 1fr;
            }
            .metric-row {
                gap: 8px;
            }
        }
    </style>

    <div class="inspector-shell">
        <div class="hero">
            <div>
                <div class="eyebrow">Workspace Settings</div>
                <h1 class="title">Akses Role</h1>
                <p class="subtitle">Ringkasan akses role berdasarkan data database aplikasi aktif.</p>
            </div>
            <div class="hero-actions">
                <?= Html::a('Akses Workspace', ['permissions'], ['class' => 'nav-pill']) ?>
                <?= Html::a('Workspace Settings', ['index'], ['class' => 'nav-pill primary']) ?>
            </div>
        </div>

        <div class="metric-row">
            <span class="metric">Role <strong><?= Html::encode($roleName) ?></strong></span>
            <span class="metric">Total access <strong><?= (int)$stats['total'] ?></strong></span>
            <span class="metric">Menu <strong><?= (int)$stats['menu'] ?></strong></span>
            <span class="metric">Page <strong><?= (int)$stats['page'] ?></strong></span>
            <span class="metric">Form <strong><?= (int)$stats['form'] ?></strong></span>
            <span class="metric">Builder <strong><?= (int)$stats['builder'] ?></strong></span>
        </div>

        <div class="layout">
            <div class="sticky-panel">
                <section class="panel">
                    <div class="panel-inner">
                        <div class="section-head">
                            <div>
                                <h2>Pilih role</h2>
                                <p>Klik role untuk melihat apa yang benar-benar muncul dan apa yang disembunyikan.</p>
                            </div>
                            <span class="help-dot" title="Role menentukan akses yang terlihat setelah login.">?</span>
                        </div>
                        <input id="roleSearch" class="input" type="search" placeholder="Cari role..." aria-label="Cari role">
                        <div class="role-list" id="roleList" style="margin-top:12px;">
                            <?php if (empty($roles)): ?>
                                <div class="empty-state">Belum ada role yang bisa dipreview.</div>
                            <?php else: ?>
                                <?php foreach ($roles as $role): ?>
                                    <?php $isActive = strtolower((string)$role['name']) === $selectedRoleName; ?>
                                    <a class="role-card<?= $isActive ? ' active' : '' ?>" href="<?= Html::encode(Url::to(['permission-inspector', 'role_name' => $role['name']])) ?>" data-role-card data-role-name="<?= Html::encode(strtolower((string)$role['name'])) ?>">
                                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                                            <div style="min-width:0;">
                                                <strong style="display:block;line-height:1.4;"><?= Html::encode($role['name']) ?></strong>
                                                <span class="small-note"><?= Html::encode($role['description'] ?? '') ?></span>
                                            </div>
                                            <span class="badge accent"><?= (int)($rolePermissionCounts[$role['name']] ?? 0) ?> akses</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="panel" style="margin-top:16px;">
                    <div class="panel-inner">
                        <div class="section-head">
                            <div>
                                <h3>Quick fix</h3>
                                <p>Jika data masih kosong, sinkronkan permission dari aplikasi aktif.</p>
                            </div>
                            <span class="help-dot" title="Sync akan membaca menu, page, form, dan route dari aplikasi aktif.">?</span>
                        </div>
                        <form method="post" action="<?= Html::encode(Url::to(['permissions'])) ?>">
                            <input type="hidden" name="_csrf" value="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
                            <input type="hidden" name="permission_action" value="resync_permissions">
                            <input type="hidden" name="role_name" value="<?= Html::encode($selectedRoleName) ?>">
                            <button type="submit" class="button primary" style="width:100%;">Sync permission</button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="panel">
                    <div class="panel-inner">
                        <div class="section-head">
                            <div>
                                <h2>Jika login sebagai <?= Html::encode($roleName) ?></h2>
                                <p><?= Html::encode($roleDescription) ?></p>
                            </div>
                            <span class="help-dot" title="Preview dibentuk dari permission yang benar-benar aktif di database.">?</span>
                        </div>

                        <?php if (!$hasData): ?>
                            <div class="empty-state">
                                Data permission untuk role ini belum tersedia atau belum tersinkron.
                                <div style="margin-top:12px;">
                                    <form method="post" action="<?= Html::encode(Url::to(['permissions'])) ?>">
                                        <input type="hidden" name="_csrf" value="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
                                        <input type="hidden" name="permission_action" value="resync_permissions">
                                        <input type="hidden" name="role_name" value="<?= Html::encode($selectedRoleName) ?>">
                                        <button type="submit" class="button primary">Sync permission</button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="preview-grid">
                                <div class="preview-card">
                                    <h4>Menu yang terlihat</h4>
                                    <p>Item sidebar yang muncul untuk role ini.</p>
                                    <div class="chips" id="visibleMenuChips">
                                        <?= render_inspector_chips($visibleMenu, 'accent') ?>
                                    </div>
                                </div>
                                <div class="preview-card">
                                    <h4>Menu yang disembunyikan</h4>
                                    <p>Item sidebar yang tidak muncul untuk role ini.</p>
                                    <div class="chips" id="hiddenMenuChips">
                                        <?= render_inspector_chips($hiddenMenu, 'default') ?>
                                    </div>
                                </div>
                                <div class="preview-card">
                                    <h4>Page yang bisa dibuka</h4>
                                    <p>Halaman yang lolos akses setelah login.</p>
                                    <div class="chips">
                                        <?= render_inspector_chips($visiblePage, 'default') ?>
                                    </div>
                                </div>
                                <div class="preview-card">
                                    <h4>Form yang bisa digunakan</h4>
                                    <p>Form input dan submit yang tersedia untuk role ini.</p>
                                    <div class="chips">
                                        <?= render_inspector_chips($visibleForm, 'success') ?>
                                    </div>
                                </div>
                                <div class="preview-card">
                                    <h4>Route yang bisa dibuka</h4>
                                    <p>Jalur aplikasi yang boleh diakses role ini.</p>
                                    <div class="chips">
                                        <?= render_inspector_chips($visibleRoute, 'warning') ?>
                                    </div>
                                </div>
                                <div class="preview-card">
                                    <h4>Builder yang bisa dipakai</h4>
                                    <p>Komponen visual editor yang muncul saat role ini login.</p>
                                    <div class="chips">
                                        <?= render_inspector_chips($visibleBuilder, 'accent') ?>
                                    </div>
                                </div>
                                <div class="preview-card" style="grid-column:1 / -1;">
                                    <h4>Tombol yang muncul</h4>
                                    <p>Aksi tambahan seperti create, edit, delete, publish, dan setting.</p>
                                    <div class="chips">
                                        <?= render_inspector_chips($visibleFeature, 'success') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-inner">
                        <div class="section-head">
                            <div>
                                <h2>Ringkasan visual</h2>
                                <p>Ini membantu admin cepat memahami efek akses tanpa perlu membaca istilah teknis.</p>
                            </div>
                            <span class="help-dot" title="Ringkasan dibangun dari permission real, bukan contoh statis.">?</span>
                        </div>

                        <div class="group-stack">
                            <div class="group-card">
                                <div class="group-head">
                                    <div class="group-title">
                                        <span>Yang terlihat</span>
                                        <span class="badge accent"><?= count($visibleMenu) ?> menu</span>
                                        <span class="badge"><?= count($visiblePage) ?> page</span>
                                    </div>
                                </div>
                                <div class="group-body">
                                    <div class="chips"><?= render_inspector_chips($visibleMenu, 'accent') ?></div>
                                    <div class="chips"><?= render_inspector_chips($visiblePage, 'default') ?></div>
                                </div>
                            </div>
                            <div class="group-card">
                                <div class="group-head">
                                    <div class="group-title">
                                        <span>Yang disembunyikan</span>
                                        <span class="badge muted"><?= count($hiddenMenu) ?> menu</span>
                                        <span class="badge muted"><?= count($hiddenPage) ?> page</span>
                                    </div>
                                </div>
                                <div class="group-body">
                                    <div class="chips"><?= render_inspector_chips($hiddenMenu, 'default') ?></div>
                                    <div class="chips"><?= render_inspector_chips($hiddenPage, 'default') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-inner">
                        <div class="section-head">
                            <div>
                                <h2>Panduan singkat</h2>
                                <p>Gunakan halaman ini sebagai cek visual sebelum menyimpan perubahan permission.</p>
                            </div>
                            <span class="help-dot" title="Jika hasil preview tidak sesuai, kembali ke Akses Workspace untuk mengubah akses.">?</span>
                        </div>
                        <div class="group-stack">
                            <div class="empty-state">Jika menu terlihat, berarti sidebar akan menampilkannya. Jika menu disembunyikan, item tersebut tidak akan muncul sama sekali.</div>
                            <div class="empty-state">Jika page, form, route, atau builder tidak muncul di preview, role tersebut memang tidak punya akses ke item itu di database aktif.</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const roleSearch = document.getElementById('roleSearch');
            const roleCards = Array.from(document.querySelectorAll('[data-role-card]'));

            if (roleSearch) {
                roleSearch.addEventListener('input', function () {
                    const needle = this.value.trim().toLowerCase();
                    roleCards.forEach(function (card) {
                        const text = card.getAttribute('data-role-name') || '';
                        card.style.display = text.indexOf(needle) !== -1 ? '' : 'none';
                    });
                });
            }
        })();
    </script>
</div>
