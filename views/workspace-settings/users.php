<?php

/** @var yii\web\View $this */
/** @var array $users */
/** @var array $roles */
/** @var int $selectedUserId */
/** @var array|null $selectedUser */
/** @var array $identityEntities */
/** @var array $entityFilterOptions */
/** @var array|null $selectedMappingInfo */
/** @var array $userStats */
/** @var array $filters */
/** @var array $pagination */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Users';

$users = is_array($users ?? null) ? $users : [];
$roles = is_array($roles ?? null) ? $roles : [];
$identityEntities = is_array($identityEntities ?? null) ? $identityEntities : [];
$entityFilterOptions = is_array($entityFilterOptions ?? null) ? $entityFilterOptions : [];
$userStats = is_array($userStats ?? null) ? $userStats : ['total' => 0, 'active' => 0, 'inactive' => 0, 'needs_attention' => 0, 'connected' => 0, 'pending' => 0];
$filters = is_array($filters ?? null) ? $filters : ['q' => '', 'role' => '', 'status' => '', 'mapping' => '', 'entity' => '', 'sort' => 'created_desc', 'page' => 1];
$pagination = is_array($pagination ?? null) ? $pagination : ['total' => 0, 'page' => 1, 'page_size' => 30, 'pages' => 1, 'has_prev' => false, 'has_next' => false];

$selectedUserId = (int)($selectedUserId ?? 0);
$selectedUser = is_array($selectedUser ?? null) ? $selectedUser : null;
$isNew = $selectedUserId <= 0 || $selectedUser === null;

$statusLabels = [1 => 'Aktif', 0 => 'Nonaktif'];
$sortOptions = [
    'created_desc' => 'Terbaru',
    'created_asc' => 'Terlama',
    'name_asc' => 'Nama A-Z',
    'name_desc' => 'Nama Z-A',
    'updated_desc' => 'Login Terakhir (terbaru)',
];

$entityLabelMap = [];
foreach ($identityEntities as $entity) {
    $entityLabelMap[(string)$entity['name']] = (string)($entity['label'] ?? $entity['name']);
}
$roleLabelMap = [];
foreach ($roles as $roleDef) {
    $roleLabelMap[strtolower(trim((string)$roleDef['name']))] = (string)($roleDef['label'] ?? $roleDef['name']);
}

$listParams = [];
foreach (['q', 'role', 'status', 'mapping', 'entity', 'sort'] as $k) {
    if (($filters[$k] ?? '') !== '') {
        $listParams[$k] = $filters[$k];
    }
}
$pageUrl = function (int $page) use ($listParams): string {
    $p = $listParams;
    if ($page > 1) {
        $p['page'] = $page;
    }
    return Url::to(['users'] + $p);
};

// ---- selected account mapping state ----
$selectedMappingInfo = is_array($selectedMappingInfo ?? null) ? $selectedMappingInfo : null;
$selectedMappedTable = '';
$selectedMappedRecordId = '';
$selPending = true;
$selDangling = false;
$selMapped = false;
$selRecordLabel = '';
if ($selectedUser !== null) {
    $selectedMappedTable = strtolower(trim((string)($selectedUser['identity_table'] ?? '')));
    $selectedMappedRecordId = trim((string)($selectedUser['identity_record_id'] ?? ''));
    $selMapped = $selectedMappedTable !== '' && $selectedMappedRecordId !== '';
    $selPending = !$selMapped;
}
if ($selMapped && $selectedMappingInfo !== null) {
    $selRecordLabel = (string)($selectedMappingInfo['label'] ?? '');
    $selDangling = empty($selectedMappingInfo['mapped']);
} else {
    $selDangling = false;
}
$selEntityLabel = $selectedMappedTable !== '' ? ($entityLabelMap[$selectedMappedTable] ?? $selectedMappedTable) : '';
$selUpdatedAt = $selectedUser !== null && is_string($selectedUser['updated_at'] ?? null) ? trim((string)$selectedUser['updated_at']) : '';

function users_role_label(string $role, array $map): string
{
    $role = strtolower(trim($role));
    return $role !== '' ? ($map[$role] ?? ucfirst(str_replace(['_', '-'], ' ', $role))) : 'Tanpa role';
}
?>

<div class="wu-page">
    <style>
        .wu-page { min-height: 100vh; padding: 24px; background: #f5f7fb; color: #0f172a; font-family: Inter, system-ui, -apple-system, sans-serif; }
        .wu-shell { max-width: 1520px; margin: 0 auto; }
        .wu-hero { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 16px; }
        .wu-eyebrow { font-size: 11px; letter-spacing: .16em; text-transform: uppercase; font-weight: 800; color: #667085; }
        .wu-title { margin: 6px 0 0; font-size: clamp(1.7rem, 2vw, 2.1rem); font-weight: 850; letter-spacing: -.03em; line-height: 1.1; }
        .wu-sub { margin: 8px 0 0; color: #667085; font-size: .92rem; max-width: 74rem; line-height: 1.6; }
        .wu-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .wu-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; gap: 7px; padding: 0 16px; border-radius: 12px; border: 1px solid #d9dee8; background: #fff; color: #101828; font-weight: 700; font-size: .9rem; text-decoration: none; cursor: pointer; }
        .wu-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .wu-btn.dark { background: #101828; border-color: #101828; color: #fff; }
        .wu-btn.sm { min-height: 34px; padding: 0 12px; font-size: .84rem; border-radius: 9px; }
        .wu-btn.ghost { background: #f7f8fa; }

        .entry-card { display: flex; gap: 18px; align-items: center; justify-content: space-between; flex-wrap: wrap; background: linear-gradient(120deg, #101828, #232b3f); color: #eef2ff; border-radius: 18px; padding: 18px 22px; margin-bottom: 16px; }
        .entry-card h3 { margin: 0 0 6px; font-size: 1.05rem; color: #fff; }
        .entry-card p { margin: 0; color: #aab2c5; font-size: .88rem; line-height: 1.55; }
        .entry-card .points { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 10px; }
        .entry-card .point { display: inline-flex; align-items: center; gap: 6px; font-size: .84rem; color: #d8ddf0; font-weight: 600; }
        .entry-card .point .ic { color: #8ce0b2; font-weight: 800; }

        .wu-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px; }
        .wu-stat { background: #fff; border: 1px solid #e4e8ef; border-radius: 14px; padding: 14px 16px; }
        .wu-stat .k { font-size: .72rem; font-weight: 800; color: #667085; text-transform: uppercase; letter-spacing: .05em; }
        .wu-stat .v { font-size: 1.6rem; font-weight: 850; margin-top: 4px; line-height: 1; }
        .wu-stat .hint { margin-top: 6px; font-size: .76rem; color: #98a2b3; }
        .v.green { color: #027a48; } .v.red { color: #b54708; } .v.blue { color: #175cd3; }
        .wu-mapmini { display: inline-flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; font-size: .8rem; color: #344054; font-weight: 700; }
        .wu-mapmini .lbl { color: #667085; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; }

        .wu-layout { display: grid; grid-template-columns: 640px minmax(0, 1fr); gap: 16px; align-items: start; }
        .wu-panel { background: #fff; border: 1px solid #e4e8ef; border-radius: 16px; box-shadow: 0 8px 24px rgba(16, 24, 40, .04); }
        .wu-pad { padding: 16px; }
        .wu-sec-head { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 12px; }
        .wu-sec-head h2, .wu-sec-head h3 { margin: 0; font-size: .98rem; font-weight: 800; color: #101828; }
        .wu-sec-head p { margin: 4px 0 0; color: #667085; font-size: .82rem; }

        .wu-field, select.wu-field { width: 100%; min-height: 40px; border-radius: 10px; border: 1px solid #d0d5dd; background: #fff; color: #101828; padding: 0 12px; outline: none; font-size: .9rem; }
        .wu-field:focus, select.wu-field:focus { border-color: #667085; box-shadow: 0 0 0 3px rgba(22, 92, 211, .12); }

        .wu-toolbar { display: grid; gap: 8px; }
        .wu-searchrow { display: grid; grid-template-columns: minmax(0,1fr); }
        .wu-filters { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .wu-bulkbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; padding: 10px 12px; border: 1px dashed #d5d9e2; background: #fbfcfe; border-radius: 10px; margin-top: 10px; }
        .wu-bulkbar[hidden] { display: none; }
        .wu-bulkbar .count { font-size: .82rem; font-weight: 800; color: #101828; }
        .wu-bulkbar select.wu-field { min-height: 34px; width: auto; }

        .wu-list { margin-top: 4px; }
        .wu-list-head, .wu-row { display: grid; grid-template-columns: 26px minmax(0,1fr) 120px 108px 140px; gap: 10px; align-items: center; }
        .wu-list-head { padding: 8px 12px; font-size: .72rem; font-weight: 800; color: #98a2b3; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #eef1f6; }
        .wu-row { padding: 10px 12px; border-radius: 10px; cursor: pointer; border: 1px solid transparent; }
        .wu-row:hover { background: #f7f9fc; border-color: #e2e7ef; }
        .wu-row.active { background: #eef4ff; border-color: #bcd0ff; }
        .wu-row a.wu-more { text-decoration: none; color: inherit; display: block; }
        .wu-acc-name { font-weight: 800; color: #101828; line-height: 1.3; }
        .wu-acc-user { font-size: .78rem; color: #667085; margin-top: 2px; }
        .wu-cell { display: flex; align-items: center; gap: 8px; }
        .wu-row input[type=checkbox] { width: 16px; height: 16px; accent-color: #175cd3; cursor: pointer; }
        .wu-count-note { color: #667085; font-size: .78rem; }

        .wu-pager { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #eef1f6; }
        .wu-pager .pages { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .wu-page-link { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border-radius: 8px; border: 1px solid #dfe3ea; background: #fff; color: #344054; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .wu-page-link:hover { border-color: #bcd0ff; }
        .wu-page-link.current { background: #101828; border-color: #101828; color: #fff; }
        .wu-page-link[disabled] { opacity: .4; cursor: not-allowed; }

        .wu-badge { display: inline-flex; align-items: center; gap: 5px; min-height: 22px; padding: 0 9px; border-radius: 999px; font-size: .72rem; font-weight: 800; white-space: nowrap; border: 1px solid transparent; }
        .wu-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .wu-badge.green { background: #ecfdf3; color: #027a48; border-color: #a6f4c5; }
        .wu-badge.amber { background: #fffaeb; color: #b54708; border-color: #fedf89; }
        .wu-badge.red { background: #fef3f2; color: #b42318; border-color: #fecdca; }
        .wu-badge.gray { background: #f2f4f7; color: #475467; border-color: #eaecf0; }
        .wu-badge[data-tip] { cursor: help; }

        .wu-edit { display: grid; gap: 14px; }
        .wu-block { border: 1px solid #eef1f6; border-radius: 12px; background: #fbfcfe; padding: 14px; }
        .wu-block legend { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #667085; padding: 0 6px; }
        .wu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .wu-g { display: grid; gap: 5px; }
        .wu-g label { font-size: .8rem; font-weight: 700; color: #344054; }
        .wu-note { color: #667085; font-size: .78rem; line-height: 1.5; }
        .wu-kv { display: grid; grid-template-columns: auto 1fr; gap: 4px 14px; font-size: .84rem; align-items: baseline; }
        .wu-kv .k { color: #667085; font-weight: 700; }
        .wu-savebar { display: flex; gap: 10px; flex-wrap: wrap; }
        .wu-toggle { display: inline-flex; align-items: center; gap: 8px; }

        .wu-mapstrip { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; border-radius: 12px; border: 1px solid #eef1f6; padding: 12px; background: #fbfcfe; }
        .wu-mapstrip.ok { background: #f4fdf8; border-color: #abf0c9; }
        .wu-mapstrip.warn { background: #fffaeb; border-color: #feeec2; }
        .wu-mapstrip.bad { background: #fef5f3; border-color: #fecdd2; }
        .wu-mapstrip .ms-title { font-weight: 800; font-size: .9rem; margin-bottom: 4px; }
        .wu-mapstrip .ms-meta { color: #667085; font-size: .8rem; line-height: 1.55; }

        .wu-modal-backdrop { display: none; position: fixed; inset: 0; z-index: 90; background: rgba(16,24,40,.45); align-items: flex-start; justify-content: center; padding: 40px 16px; overflow: auto; }
        .wu-modal-backdrop.open { display: flex; }
        .wu-modal { background: #fff; border-radius: 16px; max-width: 620px; width: 100%; box-shadow: 0 30px 60px rgba(16,24,40,.25); overflow: hidden; }
        .wu-modal-head { display: flex; justify-content: space-between; align-items: center; padding: 16px 18px; border-bottom: 1px solid #eef1f6; }
        .wu-modal-head h3 { margin: 0; font-size: 1rem; font-weight: 800; }
        .wu-steps { display: flex; gap: 6px; padding: 14px 18px 0; }
        .wu-step { flex: 0 1 auto; text-align: center; font-size: .74rem; font-weight: 700; color: #98a2b3; padding: 0 12px 10px; border-bottom: 3px solid #eef1f6; }
        .wu-step.on { color: #175cd3; border-color: #175cd3; }
        .wu-modal-body { padding: 18px; display: grid; gap: 14px; min-height: 180px; }
        .wu-step-pane { display: none; }
        .wu-step-pane.on { display: grid; gap: 14px; }
        .wu-modal-foot { display: flex; justify-content: space-between; gap: 10px; padding: 14px 18px; border-top: 1px solid #eef1f6; }
        .wu-picker { position: relative; }
        .wu-picker-panel { position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 60; background: #fff; border: 1px solid #d7dce5; border-radius: 10px; box-shadow: 0 18px 40px rgba(16,24,40,.16); max-height: 260px; overflow: auto; }
        .wu-picker-panel[hidden] { display: none; }
        .wu-picker-status { padding: 8px 12px; font-size: .78rem; color: #667085; border-bottom: 1px solid #eef1f6; }
        .wu-picker-item { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid #f1f5f9; background: #fff; padding: 10px 14px; cursor: pointer; font: inherit; font-size: .86rem; color: #101828; }
        .wu-picker-item:hover, .wu-picker-item.active { background: #f1f5f9; }
        .wu-picker-item.selected { background: #eef4ff; color: #175cd3; font-weight: 700; }

        @media (max-width: 1380px) { .wu-layout { grid-template-columns: 560px minmax(0,1fr); } }
        @media (max-width: 1200px) {
            .wu-layout { grid-template-columns: 1fr; }
            .wu-stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 720px) {
            .wu-page { padding: 14px; }
            .wu-stats, .wu-filters { grid-template-columns: 1fr; }
            .wu-list-head { display: none; }
        }
    </style>

    <div class="wu-shell">
        <!-- Entry point -->
        <div class="entry-card">
            <div style="flex:1;min-width:240px;">
                <h3>Generate Accounts</h3>
                <p>Buat akun massal dari data pengguna yang sudah ada. Setelah berhasil, akun langsung siap dipakai — tidak perlu menyentuh halaman ini kecuali ingin mengedit akun tertentu.</p>
                <div class="points">
                    <span class="point"><span class="ic">✔</span> Username otomatis</span>
                    <span class="point"><span class="ic">✔</span> Password otomatis</span>
                    <span class="point"><span class="ic">✔</span> Role otomatis</span>
                    <span class="point"><span class="ic">✔</span> Hubungan data otomatis</span>
                    <span class="point"><span class="ic">✔</span> Akun langsung siap pakai</span>
                </div>
            </div>
            <?= Html::a('+ Generate Accounts', ['generate-accounts'], ['class' => 'wu-btn dark']) ?>
        </div>

        <!-- Stats -->
        <div class="wu-stats">
            <div class="wu-stat">
                <div class="k">Total User</div>
                <div class="v"><?= (int)$userStats['total'] ?></div>
                <div class="hint"><?= count($users) ?> akun di halaman</div>
            </div>
            <div class="wu-stat">
                <div class="k">Aktif</div>
                <div class="v green"><?= (int)$userStats['active'] ?></div>
                <div class="hint">Akun berstatus aktif</div>
            </div>
            <div class="wu-stat">
                <div class="k">Nonaktif</div>
                <div class="v red"><?= (int)$userStats['inactive'] ?></div>
                <div class="hint">Akun dinonaktifkan</div>
            </div>
            <div class="wu-stat">
                <div class="k">Perlu Perhatian</div>
                <div class="v blue"><?= (int)$userStats['needs_attention'] ?></div>
                <div class="hint">Akun nonaktif / hubungan data bermasalah</div>
            </div>
        </div>

        <div class="wu-mapmini">
            <span class="lbl">Hubungan Data</span>
            <span class="wu-badge green"><span class="dot"></span><?= (int)$userStats['connected'] ?> Terhubung</span>
            <span class="wu-badge amber"><span class="dot"></span><?= (int)$userStats['pending'] ?> Pending</span>
        </div>

        <div class="wu-layout">
            <section class="wu-panel">
                <div class="wu-pad">
                    <div class="wu-sec-head">
                        <div>
                            <h3>Akun</h3>
                            <p><?= number_format((int)$pagination['total']) ?> akun ditemukan</p>
                        </div>
                        <a class="wu-btn sm" href="<?= Url::to(['index']) ?>">← Kembali ke Pengaturan</a>
                    </div>

                    <form method="get" id="userForm" class="wu-toolbar">
                        <div class="wu-searchrow">
                            <input id="userSearch" name="q" class="wu-field" type="search"
                                   placeholder="Cari nama, username, email, role, jenis data, rekaman..."
                                   value="<?= Html::encode($filters['q']) ?>">
                        </div>
                        <div class="wu-filters">
                            <select id="roleFilter" name="role" class="wu-field">
                                <option value="">Semua role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= Html::encode($role['name']) ?>" <?= $filters['role'] === strtolower((string)$role['name']) ? 'selected' : '' ?>><?= Html::encode($role['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="statusFilter" name="status" class="wu-field">
                                <option value="">Semua status</option>
                                <option value="1" <?= $filters['status'] === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= $filters['status'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <select id="mappingFilter" name="mapping" class="wu-field">
                                <option value="">Semua hubungan</option>
                                <option value="connected" <?= $filters['mapping'] === 'connected' ? 'selected' : '' ?>>Terhubung</option>
                                <option value="pending" <?= $filters['mapping'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="attention" <?= $filters['mapping'] === 'attention' ? 'selected' : '' ?>>Perlu Perhatian</option>
                            </select>
                            <select id="entityFilter" name="entity" class="wu-field">
                                <option value="">Semua jenis data</option>
                                <?php foreach ($entityFilterOptions as $ename => $elabel): ?>
                                    <option value="<?= Html::encode($ename) ?>" <?= $filters['entity'] === $ename ? 'selected' : '' ?>><?= Html::encode($elabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="sortFilter" name="sort" class="wu-field">
                                <?php foreach ($sortOptions as $sval => $slabel): ?>
                                    <option value="<?= Html::encode($sval) ?>" <?= $filters['sort'] === $sval ? 'selected' : '' ?>><?= Html::encode($slabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span></span>
                        </div>
                    </form>

                    <form method="post" id="bulkForm" class="wu-bulkbar" hidden>
                        <input type="hidden" name="_csrf" value="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
                        <input type="hidden" name="permission_action" value="bulk_user_action">
                        <input type="hidden" name="q" value="<?= Html::encode($filters['q']) ?>">
                        <input type="hidden" name="role" value="<?= Html::encode($filters['role']) ?>">
                        <input type="hidden" name="status" value="<?= Html::encode($filters['status']) ?>">
                        <input type="hidden" name="mapping" value="<?= Html::encode($filters['mapping']) ?>">
                        <input type="hidden" name="entity" value="<?= Html::encode($filters['entity']) ?>">
                        <input type="hidden" name="sort" value="<?= Html::encode($filters['sort']) ?>">
                        <input type="hidden" name="page" value="<?= (int)$pagination['page'] ?>">
                        <span class="count" id="bulkCount">0</span><span class="count" style="font-weight:600;color:#667085;">dipilih</span>
                        <select name="bulk_operation" class="wu-field wu-bar" id="bulkOp">
                            <option value="">Aksi massal...</option>
                            <option value="activate">Aktifkan</option>
                            <option value="disable">Nonaktifkan</option>
                            <option value="role">Ubah role</option>
                            <option value="reset_password">Reset kata sandi</option>
                            <option value="delete">Hapus</option>
                        </select>
                        <select name="bulk_role" class="wu-field wu-bar" id="bulkRoleField" style="display:none;">
                            <option value="">Pilih role...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= Html::encode($role['name']) ?>"><?= Html::encode($role['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="bulk_password" class="wu-field wu-bar" id="bulkPwdField" style="display:none;" placeholder="Kata sandi baru" type="text">
                        <label class="wu-toggle" id="bulkRandomWrap" style="display:none;"><input type="checkbox" name="bulk_random_password" id="bulkRandom" value="1"> Acak</label>
                        <button type="submit" class="wu-btn sm primary" id="bulkGo">Terapkan</button>
                    </form>

                    <div class="wu-list">
                        <div class="wu-list-head">
                            <span></span>
                            <span>Akun</span>
                            <span>Role</span>
                            <span>Status</span>
                            <span>Hubungan Data</span>
                        </div>

                        <?php if (empty($users)): ?>
                            <div class="wu-empty" style="padding:22px 12px;border:1px dashed #d7dce5;border-radius:12px;background:#fbfdff;color:#667085;font-size:.86rem;">
                                Tidak ada akun sesuai filter.
                            </div>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                    $uid = (int)$user['id'];
                                    $isActive = $uid === $selectedUserId;
                                    $name = (string)($user['name'] ?? $user['username'] ?? 'User');
                                    $uname = (string)($user['username'] ?? '');
                                    $roleRaw = strtolower((string)($user['role'] ?? ''));
                                    $ustat = (int)($user['status'] ?? 1);
                                    $mt = strtolower(trim((string)($user['identity_table'] ?? '')));
                                    $mr = trim((string)($user['identity_record_id'] ?? ''));
                                    $mapped = $mt !== '' && $mr !== '';
                                    $detailUrl = Url::to(['users'] + $listParams + ['page' => (int)$pagination['page'], 'user_id' => $uid]);
                                ?>
                                <div class="wu-row<?= $isActive ? ' active' : '' ?>">
                                    <input type="checkbox" class="row-check" value="<?= $uid ?>" aria-label="Pilih <?= Html::encode($name) ?>">
                                    <a class="wu-more" href="<?= Html::encode($detailUrl) ?>">
                                        <span class="wu-acc-name"><?= Html::encode($name) ?></span>
                                        <span class="wu-acc-user">@<?= Html::encode($uname) ?></span>
                                    </a>
                                    <span class="wu-cell"><?= Html::encode(users_role_label($roleRaw, $roleLabelMap)) ?></span>
                                    <span class="wu-cell">
                                        <span class="wu-badge <?= $ustat === 1 ? 'green' : 'red' ?>"><span class="dot"></span><?= Html::encode($statusLabels[$ustat] ?? 'Nonaktif') ?></span>
                                    </span>
                                    <span class="wu-cell">
                                        <?php if ($mapped): ?>
                                            <span class="wu-badge green" data-tip="Akun telah terhubung dengan data domain."><span class="dot"></span>Terhubung</span>
                                        <?php else: ?>
                                            <span class="wu-badge amber" data-tip="Belum ada hubungan data. Biasanya dibuat otomatis saat Generate Accounts."><span class="dot"></span>Pending</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="wu-pager">
                        <span class="wu-count-note">
                            <?php if ($pagination['total'] > 0): ?>
                                Menampilkan <?= (($pagination['page'] - 1) * $pagination['page_size'] + 1) ?>–<?= min($pagination['total'], $pagination['page'] * $pagination['page_size']) ?> dari <?= number_format((int)$pagination['total']) ?> akun
                            <?php endif; ?>
                        </span>
                        <div class="pages">
                            <a class="wu-page-link" <?= $pagination['has_prev'] ? 'href="' . Html::encode($pageUrl(max(1, (int)$pagination['page'] - 1))) . '"' : 'disabled' ?>>‹</a>
                            <?php
                                $p = (int)$pagination['page'];
                                $last = (int)$pagination['pages'];
                                $nums = [];
                                for ($i = 1; $i <= $last; $i++) {
                                    if ($i === 1 || $i === $last || ($i >= $p - 2 && $i <= $p + 2)) {
                                        $nums[] = $i;
                                    }
                                }
                                $prev = null;
                                foreach ($nums as $n) {
                                    if ($prev !== null && $n - $prev > 1) {
                                        echo '<span style="color:#98a2b3;align-self:center;">…</span>';
                                    }
                                    $prev = $n;
                                    echo '<a class="wu-page-link' . ($n === $p ? ' current' : '') . '" href="' . Url::to($pageUrl($n)) . '">' . $n . '</a>';
                                }
                            ?>
                            <a class="wu-page-link" <?= $pagination['has_next'] ? 'href="' . Url::to($pageUrl(min($last, $p + 1))) . '"' : 'disabled' ?>>›</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RIGHT: inspector -->
            <section class="wu-panel">
                <div class="wu-pad">
                    <div class="wu-sec-head">
                        <div>
                            <h2><?= $isNew ? 'Buat Akun' : 'Detail Akun' ?></h2>
                            <p>
                                <?php if ($isNew): ?>
                                    Tambah satu akun secara manual bila diperlukan.
                                <?php else: ?>
                                    <?= Html::encode((string)($selectedUser['name'] ?? $selectedUser['username'] ?? '')) ?> · @<?= Html::encode((string)($selectedUser['username'] ?? '')) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($selectedUser === null && !$isNew): ?>
                        <div class="wu-empty">Pilih akun dari daftar untuk melihat detail.</div>
                    <?php else: ?>
                        <form method="post" class="wu-edit" id="accountForm">
                            <input type="hidden" name="_csrf" value="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
                            <input type="hidden" name="permission_action" value="save_user">
                            <input type="hidden" name="user_id" value="<?= $isNew ? '0' : (int)($selectedUser['id'] ?? 0) ?>">
                            <input type="hidden" name="entity_table" id="mappingEntityTable" value="<?= Html::encode($selectedMappedTable) ?>">
                            <input type="hidden" name="record_id" id="mappingRecordId" value="<?= Html::encode($selectedMappedRecordId) ?>">

                            <!-- Hubungan Data strip -->
                            <div class="wu-mapstrip <?= $selDangling ? 'bad ' : ($selMapped ? 'ok ' : 'warn ') ?>">
                                <div>
                                    <?php if ($selDangling): ?>
                                        <div class="ms-title">Needs Attention</div>
                                        <div class="ms-meta">Hubungan data bermasalah — data yang dirujuk tidak ditemukan.</div>
                                    <?php elseif ($isNew || $selPending): ?>
                                        <div class="ms-title">Pending</div>
                                        <div class="ms-meta">Belum ada hubungan data. Biasanya dibuat otomatis melalui Generate Accounts — hubungkan hanya jika diperlukan.</div>
                                    <?php else: ?>
                                        <div class="ms-title">Terhubung</div>
                                        <div class="ms-meta">Akun ini telah terhubung dengan data domain. Tidak diperlukan tindakan tambahan.</div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="wu-btn sm" data-open-wizard>
                                    <?= $selDangling ? 'Perbaiki Sekarang' : 'Review' ?>
                                </button>
                            </div>

                            <fieldset class="wu-block">
                                <legend>Akun</legend>
                                <div class="wu-grid">
                                    <div class="wu-g">
                                        <label for="userName">Nama</label>
                                        <input id="userName" name="name" class="wu-field" type="text" value="<?= Html::encode((string)($selectedUser['name'] ?? '')) ?>" placeholder="Nama lengkap">
                                    </div>
                                    <div class="wu-g">
                                        <label for="userUsername">Username</label>
                                        <input id="userUsername" name="username" class="wu-field" type="text" required value="<?= Html::encode((string)($selectedUser['username'] ?? '')) ?>" placeholder="username">
                                    </div>
                                    <div class="wu-g">
                                        <label for="userEmail">Email</label>
                                        <input id="userEmail" name="email" class="wu-field" type="email" value="<?= Html::encode((string)($selectedUser['email'] ?? '')) ?>" placeholder="email">
                                    </div>
                                    <div class="wu-g">
                                        <label for="userPassword">Kata Sandi</label>
                                        <input id="userPassword" name="password" class="wu-field" type="password" value="" <?= $isNew ? 'required' : '' ?> placeholder="<?= $isNew ? 'Kata sandi akun baru' : 'Kosongkan untuk tetap' ?>" autocomplete="new-password">
                                        <?php if (!$isNew): ?><span class="wu-note">Kosongkan bila tidak ingin mengubah kata sandi.</span><?php endif; ?>
                                    </div>
                                    <div class="wu-g">
                                        <label for="userRole">Role</label>
                                        <input id="userRole" name="role" class="wu-field" type="text" required list="role-options" value="<?= Html::encode((string)($selectedUser['role'] ?? 'admin')) ?>" placeholder="Pilih atau ketik role">
                                        <datalist id="role-options">
                                            <?php foreach ($roles as $role): ?><option value="<?= Html::encode($role['name']) ?>"><?= Html::encode($role['label']) ?></option><?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <div class="wu-g">
                                        <label for="userStatus">Status</label>
                                        <select id="userStatus" name="status" class="wu-field">
                                            <option value="1" <?= (int)($selectedUser['status'] ?? 1) === 1 ? 'selected' : '' ?>>Aktif</option>
                                            <option value="0" <?= (int)($selectedUser['status'] ?? 1) === 0 ? 'selected' : '' ?>>Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>

                            <?php if (!$isNew): ?>
                                <fieldset class="wu-block">
                                    <legend>Hubungan Data</legend>
                                    <div class="wu-kv">
                                        <span class="k">Status</span>
                                        <span>
                                            <?php if ($selDangling): ?>
                                                <span class="wu-badge red"><span class="dot"></span>Perlu Perhatian</span>
                                            <?php elseif ($selMapped): ?>
                                                <span class="wu-badge green"><span class="dot"></span>Terhubung</span>
                                            <?php else: ?>
                                                <span class="wu-badge amber"><span class="dot"></span>Pending</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($selMapped): ?>
                                            <span class="k">Terhubung Dengan</span>
                                            <span><?= Html::encode($selEntityLabel) ?> · <?= Html::encode($selRecordLabel !== '' ? $selRecordLabel : ('#' . $selectedMappedRecordId)) ?></span>
                                            <span class="k">ID Rekaman</span>
                                            <span><?= Html::encode($selectedMappedRecordId) ?></span>
                                        <?php endif; ?>
                                        <?php if ($selUpdatedAt !== ''): ?>
                                            <span class="k">Terakhir Diperbarui</span>
                                            <span><?= Html::encode($selUpdatedAt) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </fieldset>

                                <fieldset class="wu-block">
                                    <legend>Keamanan</legend>
                                    <div class="wu-kv">
                                        <span class="k">Kata sandi terakhir diubah</span><span>—</span>
                                        <span class="k">Login terakhir</span><span>—</span>
                                        <span class="k">Status akun</span><span><?= Html::encode((int)($selectedUser['status'] ?? 1) === 1 ? 'Aktif' : 'Nonaktif') ?></span>
                                    </div>
                                    <span class="wu-note" style="display:block;margin-top:8px;">Riwayat login, MFA, audit, dan sesi akan tersedia otomatis di bagian ini tanpa mengubah tata letak.</span>
                                </fieldset>
                            <?php endif; ?>

                            <div class="wu-savebar">
                                <button type="submit" class="wu-btn primary" id="accountSaveBtn">Simpan Akun</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Mapping wizard modal -->
    <div class="wu-modal-backdrop" id="wizardBackdrop">
        <div class="wu-modal">
            <div class="wu-modal-head">
                <h3>Hubungkan Akun dengan Data</h3>
                <button type="button" class="wu-btn sm ghost" data-wiz-close>✕</button>
            </div>
            <div class="wu-steps">
                <div class="wu-step" data-wiz-step="1">1 · Jenis Data</div>
                <div class="wu-step" data-wiz-step="2">2 · Pilih Data</div>
                <div class="wu-step" data-wiz-step="3">3 · Ringkasan</div>
            </div>
            <div class="wu-modal-body">
                <div class="wu-step-pane" data-wiz-pane="1">
                    <div class="wu-g">
                        <label for="wizEntitySelect">Jenis Data</label>
                        <?php if (empty($identityEntities)): ?>
                            <div class="wu-empty">Belum ada jenis data yang dapat dihubungkan. Jenis data ditemukan otomatis dari skema aplikasi.</div>
                        <?php else: ?>
                            <select id="wizEntitySelect" class="wu-field">
                                <option value="">— Pilih jenis data —</option>
                                <?php foreach ($identityEntities as $entity): ?>
                                    <option value="<?= Html::encode($entity['name']) ?>"
                                            data-entity-label="<?= Html::encode($entity['label'] ?? $entity['name']) ?>"
                                            <?= $selMapped && $selectedMappedTable === $entity['name'] ? 'selected' : '' ?>>
                                        <?= Html::encode(($entity['label'] ?? $entity['name']) . ' (' . $entity['name'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="wu-step-pane" data-wiz-pane="2">
                    <div class="wu-g">
                        <label for="wizRecordSearch">Cari data</label>
                        <div class="wu-picker" id="wizPicker">
                            <input id="wizRecordSearch" class="wu-field" type="text" placeholder="Ketik nama/ID untuk mencari..." autocomplete="off">
                            <div class="wu-picker-panel" id="wizPickerPanel" hidden>
                                <div class="wu-picker-status" id="wizPickerStatus">Ketik untuk mencari...</div>
                                <div id="wizPickerList"></div>
                            </div>
                        </div>
                        <span class="wu-note" id="wizRecordHelp">Pilih jenis data terlebih dahulu, lalu cari datanya di langkah 2.</span>
                    </div>
                </div>
                <div class="wu-step-pane" data-wiz-pane="3">
                    <div class="wu-mapstrip ok">
                        <div>
                            <div class="ms-title">Terhubung Dengan</div>
                            <div class="ms-meta" id="wizPreviewMeta">Belum ada pilihan.</div>
                        </div>
                    </div>
                    <label class="wu-toggle"><input type="checkbox" id="wizClearChk"> Lepas hubungan (kosongkan)</label>
                </div>
            </div>
            <div class="wu-modal-foot">
                <button type="button" class="wu-btn sm ghost" id="wizBack" style="visibility:hidden;">← Kembali</button>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="wu-btn sm" data-wiz-cancel>Batal</button>
                    <button type="button" class="wu-btn sm" id="wizNext">Lanjut →</button>
                    <button type="button" class="wu-btn sm primary" id="wizFinish" style="display:none;">Simpan &amp; Selesai</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var userForm = document.getElementById('userForm');
            if (userForm) {
                Array.prototype.forEach.call(userForm.querySelectorAll('select'), function (s) {
                    s.addEventListener('change', function () { userForm.submit(); });
                });
                var search = document.getElementById('userSearch');
                if (search) {
                    var timer = null;
                    search.addEventListener('input', function () {
                        clearTimeout(timer);
                        timer = setTimeout(function () { userForm.submit(); }, 450);
                    });
                }
            }

            var checks = Array.prototype.slice.call(document.querySelectorAll('.row-check'));
            var bulkBar = document.getElementById('bulkForm');
            var bulkCount = document.getElementById('bulkCount');
            var bulkOp = document.getElementById('bulkOp');
            var bulkRole = document.getElementById('bulkRoleField');
            var bulkPwd = document.getElementById('bulkPwdField');
            var bulkRand = document.getElementById('bulkRandomWrap');
            function syncBulk() {
                var sel = checks.filter(function (c) { return c.checked; });
                if (bulkBar) bulkBar.hidden = sel.length === 0;
                if (bulkCount) bulkCount.textContent = sel.length;
            }
            checks.forEach(function (c) { c.addEventListener('change', syncBulk); });
            if (bulkOp) {
                bulkOp.addEventListener('change', function () {
                    var v = bulkOp.value;
                    if (bulkRole) bulkRole.style.display = v === 'role' ? '' : 'none';
                    if (bulkPwd) bulkPwd.style.display = v === 'reset_password' ? '' : 'none';
                    if (bulkRand) bulkRand.style.display = v === 'reset_password' ? '' : 'none';
                });
            }

            // ---- wizard ----
            var backdrop = document.getElementById('wizardBackdrop');
            var entitySelect = document.getElementById('wizEntitySelect');
            var recSearch = document.getElementById('wizRecordSearch');
            var recHelp = document.getElementById('wizRecordHelp');
            var pickPanel = document.getElementById('wizPickerPanel');
            var pickStatus = document.getElementById('wizPickerStatus');
            var pickList = document.getElementById('wizPickerList');
            var entityHidden = document.getElementById('mappingEntityTable');
            var recordHidden = document.getElementById('mappingRecordId');

            var picker = { rows: [], seq: 0, loading: false, selected: null };
            var timer2 = null;
            var baseEntity = entityHidden ? entityHidden.value : '';
            var baseRecord = recordHidden ? recordHidden.value : '';

            function stepTo(n) {
                Array.prototype.forEach.call(document.querySelectorAll('[data-wiz-step]'), function (el) {
                    el.classList.toggle('on', Number(el.getAttribute('data-wiz-step')) === n);
                });
                Array.prototype.forEach.call(document.querySelectorAll('[data-wiz-pane]'), function (el) {
                    el.classList.toggle('on', el.getAttribute('data-wiz-pane') === String(n));
                });
                document.getElementById('wizBack').style.visibility = n > 1 ? 'visible' : 'hidden';
                document.getElementById('wizNext').style.display = n === 3 ? 'none' : '';
                document.getElementById('wizFinish').style.display = n === 3 ? '' : 'none';
                if (n === 2 && entitySelect.value && picker.rows.length === 0 && !picker.loading) {
                    loadRecords(1, false);
                }
            }
            function openWizard() { if (backdrop) backdrop.classList.add('open'); stepTo(1); }
            function closeWizard() { if (backdrop) backdrop.classList.remove('open'); }

            document.addEventListener('click', function (e) {
                var trg = e.target;
                if (trg.closest && trg.closest('[data-open-wizard]')) { openWizard(); return; }
                if (trg.closest && (trg.closest('[data-wiz-close]') || trg.closest('[data-wiz-cancel]'))) { closeWizard(); return; }
                if (trg.closest && trg.closest('#wizNext')) {
                    if (getStep() === 1 && entitySelect && !entitySelect.value) {
                        if (recHelp) recHelp.textContent = 'Pilih jenis data terlebih dahulu.';
                        return;
                    }
                    if (getStep() === 2 && !picker.selected) {
                        if (recHelp) recHelp.textContent = 'Pilih satu data terlebih dahulu.';
                        return;
                    }
                    if (getStep() === 2) {
                        renderPreview();
                    }
                    stepTo(getStep() + 1);
                    return;
                }
                if (trg.closest && trg.id === 'wiz-back') { stepTo(getStep() - 1); return; }
                if (trg.closest && trg.id === 'wiz-finish') { finishWizard(); return; }
                if (backdrop && trg === backdrop) { closeWizard(); return; }
                if (trg.closest && !trg.closest('#wizPicker')) { if (pickPanel) pickPanel.hidden = true; }
            });

            function getStep() {
                var on = document.querySelector('[data-wiz-pane].on');
                return on ? Number(on.getAttribute('data-wiz-pane')) : 1;
            }
            function renderPreview() {
                var meta = document.getElementById('wizPreviewMeta');
                var e = entitySelect ? (entitySelect.selectedOptions[0] ? entitySelect.selectedOptions[0].textContent.trim() : entitySelect.value) : '';
                meta.textContent = picker.selected ? (e + ' · ' + picker.selected.label) : (e + ' · (belum dipilih)');
            }
            function finishWizard() {
                var clear = document.getElementById('wizClearChk').checked;
                var chosen = clear ? null : picker.selected;
                if (entityHidden) entityHidden.value = chosen ? entityValue() : '';
                if (recordHidden) recordHidden.value = chosen ? chosen.id : '';
                closeWizard();
            }
            function entityValue() { return entitySelect ? entitySelect.value : ''; }

            function entityLabelFor(e) {
                if (!entitySelect) return e;
                var opt = Array.prototype.find.call(entitySelect.options, function (o) { return o.value === e; });
                return opt ? (opt.getAttribute('data-entity-label') || e) : e;
            }
            function renderPickerStatus(t) { if (pickStatus) pickStatus.textContent = t; }
            function clearList() { while (pickList.firstChild) { pickList.removeChild(pickList.firstChild); } }
            function selectRecord(row) {
                picker.selected = { id: String(row.id), label: String(row.label) };
                if (recSearch) recSearch.value = picker.selected.label;
                renderItems(false);
                if (recHelp) recHelp.textContent = 'Terpilih: ' + picker.selected.label + ' — lanjut ke Ringkasan.';
            }
            function renderItems(append) {
                if (!append) clearList();
                picker.rows.forEach(function (row) {
                    var it = document.createElement('button');
                    it.type = 'button';
                    it.className = 'wu-picker-item';
                    if (picker.selected && String(picker.selected.id) === String(row.id)) it.classList.add('selected');
                    it.textContent = row.label;
                    it.addEventListener('click', function () { selectRecord(row); });
                    pickList.appendChild(it);
                });
                renderPickerStatus(picker.total > 0 ? picker.rows.length + ' dari ' + picker.total + ' data' : 'Tidak ada data.');
                if (baseEntity === entityValue() && baseRecord && !append && picker.rows.length) {
                    var f = picker.rows.find(function (r) { return String(r.id) === baseRecord; });
                    if (f) { selectRecord(f); baseRecord = ''; }
                }
            }
            function loadRecords(page, append) {
                var e = entityValue();
                if (!e) { renderPickerStatus('Pilih jenis data terlebih dahulu.'); return; }
                picker.loading = true;
                var seq = ++picker.seq;
                var q = recSearch.value.trim();
                if (picker.selected && q === picker.selected.label) q = '';
                fetch(recUrl() + '?entity=' + encodeURIComponent(e) + '&q=' + encodeURIComponent(q) + '&page=' + page + '&page_size=50', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (seq !== picker.seq) return;
                        picker.loading = false;
                        if (!data || data.success !== true) { renderPickerStatus('Gagal memuat data.'); return; }
                        picker.rows = append ? picker.rows.concat(data.rows || []) : (data.rows || []);
                        picker.total = (data.pagination && data.pagination.total) || 0;
                        renderItems(append);
                    })
                    .catch(function () { if (seq === picker.seq) { picker.loading = false; renderPickerStatus('Gagal memuat data.'); } });
            }
            function recUrl() { return '<?= Url::to(['user-mapping-records']) ?>'; }

            if (recSearch) {
                recSearch.addEventListener('focus', function () { if (pickPanel) pickPanel.hidden = false; });
                recSearch.addEventListener('input', function () {
                    if (picker.selected && this.value === picker.selected.label) return;
                    picker.selected = null;
                    clearTimeout(timer2);
                    timer2 = setTimeout(function () { if (entityValue()) { loadRecords(1, false); if (pickPanel) pickPanel.hidden = false; } }, 300);
                });
            }
            if (entitySelect) {
                entitySelect.addEventListener('change', function () {
                    picker.selected = null; picker.rows = []; clearList();
                    if (recSearch) recSearch.value = '';
                    if (recHelp) recHelp.textContent = 'Jenis data dipilih. Lanjutkan ke langkah 2.';
                });
            }
        })();
    </script>
</div>