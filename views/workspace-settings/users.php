<?php

/** @var yii\web\View $this */
/** @var array $roles */
/** @var array $users */
/** @var string $selectedRoleName */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Workspace Users';

$roles = is_array($roles ?? null) ? $roles : [];
$users = is_array($users ?? null) ? $users : [];
$selectedRoleName = (string)($selectedRoleName ?? 'admin');

?>

<div class="workspace-users-page">
    <style>
        .workspace-users-page {
            min-height: 100vh;
            padding: 28px;
            background: #f6f8fb;
            color: #0f172a;
            font-family: Inter, system-ui, sans-serif;
        }
        .shell {
            max-width: 1320px;
            margin: 0 auto;
        }
        .hero {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 18px;
        }
        .eyebrow {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            font-weight: 800;
            color: #64748b;
        }
        .title {
            margin: 6px 0 0;
            font-size: clamp(1.8rem, 2vw, 2.25rem);
            font-weight: 850;
            letter-spacing: -.04em;
            line-height: 1.1;
        }
        .subtitle {
            margin: 10px 0 0;
            max-width: 68rem;
            color: #64748b;
            line-height: 1.7;
        }
        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid #dbe3ef;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            font-weight: 700;
        }
        .pill.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .layout {
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
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
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 14px;
        }
        .section-head h2,
        .section-head h3 {
            margin: 0;
            font-size: 1rem;
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
        .input,
        .textarea,
        select.input {
            width: 100%;
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid #d7dce5;
            background: #fff;
            color: #0f172a;
            padding: 0 14px;
            outline: none;
        }
        .textarea {
            padding: 12px 14px;
            resize: vertical;
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
            color: #fff;
            border-color: #0f172a;
        }
        .button.ghost {
            background: #f8fafc;
        }
        .button.danger {
            background: #fff;
            border-color: #fecaca;
            color: #b91c1c;
        }
        .stack {
            display: grid;
            gap: 16px;
        }
        .role-list,
        .user-list {
            display: grid;
            gap: 10px;
            max-height: 20rem;
            overflow: auto;
            padding-right: 2px;
        }
        .role-card,
        .user-card {
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
        .role-card:hover,
        .user-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            font-size: .77rem;
            font-weight: 800;
        }
        .empty-state {
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed #d7dce5;
            background: #fbfdff;
            color: #64748b;
            line-height: 1.7;
        }
        .search-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            margin-bottom: 14px;
        }
        .mini-note {
            color: #64748b;
            font-size: .84rem;
            line-height: 1.5;
        }
        @media (max-width: 1180px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 720px) {
            .workspace-users-page {
                padding: 16px;
            }
            .search-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="shell">
        <div class="hero">
            <div>
                <div class="eyebrow">Workspace Settings</div>
                <h1 class="title">Users</h1>
                <p class="subtitle">Halaman ini khusus untuk assign role ke user. Alur permission tetap dipisah agar halaman utama lebih sederhana.</p>
            </div>
            <div class="top-actions">
                <?= Html::a('Akses Workspace', ['permissions'], ['class' => 'pill']) ?>
                <?= Html::a('Workspace Settings', ['index'], ['class' => 'pill primary']) ?>
            </div>
        </div>

        <div class="layout">
            <section class="panel">
                <div class="panel-inner">
                    <div class="section-head">
                        <div>
                            <h2>Pilih role tujuan</h2>
                            <p>Role yang dipilih akan dipakai sebagai target saat mengubah user.</p>
                        </div>
                        <span class="help-dot" title="Role tujuan menentukan akses user setelah login.">?</span>
                    </div>
                    <div class="role-list" id="roleList">
                        <?php if (empty($roles)): ?>
                            <div class="empty-state">Belum ada role yang tersedia.</div>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                                <?php $isActive = strtolower((string)$role['name']) === $selectedRoleName; ?>
                                <a class="role-card<?= $isActive ? ' active' : '' ?>" href="<?= Html::encode(Url::to(['users', 'role_name' => $role['name']])) ?>" data-role-name="<?= Html::encode(strtolower((string)$role['name'])) ?>">
                                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                                        <div style="min-width:0;">
                                            <strong style="display:block;line-height:1.4;"><?= Html::encode($role['name']) ?></strong>
                                            <span class="mini-note"><?= Html::encode($role['description'] ?? '') ?></span>
                                        </div>
                                        <span class="badge">Target</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-inner">
                    <div class="section-head">
                        <div>
                            <h2>Assign role ke user</h2>
                            <p>Pilih user lalu tentukan role tujuan. Perubahan langsung memakai data database aktif.</p>
                        </div>
                        <span class="help-dot" title="Ini memindahkan user ke role yang dipilih tanpa mengubah permission engine.">?</span>
                    </div>

                    <div class="search-row">
                        <input id="userSearch" class="input" type="search" placeholder="Cari user..." aria-label="Cari user">
                        <button type="button" class="button ghost" id="clearUserSearch">Clear</button>
                    </div>

                    <form method="post" class="stack">
                        <input type="hidden" name="_csrf" value="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
                        <input type="hidden" name="permission_action" value="assign_user_role">

                        <div class="user-list" id="userList">
                            <?php if (empty($users)): ?>
                                <div class="empty-state">Belum ada user di aplikasi ini.</div>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userText = strtolower(trim((string)($user['name'] ?? '') . ' ' . (string)($user['username'] ?? '') . ' ' . (string)($user['email'] ?? '')));
                                    ?>
                                    <label class="user-card" data-user-card data-user-text="<?= Html::encode($userText) ?>">
                                        <div style="display:flex;gap:10px;align-items:flex-start;">
                                            <input type="radio" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">
                                            <div style="min-width:0;flex:1;">
                                                <strong style="display:block;line-height:1.4;"><?= Html::encode($user['name'] ?? $user['username'] ?? 'User') ?></strong>
                                                <div class="mini-note"><?= Html::encode(($user['username'] ?? '') . ($user['email'] ? ' · ' . $user['email'] : '')) ?></div>
                                            </div>
                                            <span class="badge"><?= Html::encode((string)($user['role'] ?? 'no role')) ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="mini-note" for="assignedRoleName">Role tujuan</label>
                            <select id="assignedRoleName" name="assigned_role_name" class="input" style="margin-top:6px;">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= Html::encode($role['name']) ?>" <?= strtolower((string)$role['name']) === $selectedRoleName ? 'selected' : '' ?>>
                                        <?= Html::encode($role['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="empty-state">
                            Flow yang dianjurkan: buat role di Akses Workspace, atur menu/page yang boleh dibuka, lalu assign user di halaman ini.
                        </div>

                        <button type="submit" class="button primary">Assign role</button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        (function () {
            const userSearch = document.getElementById('userSearch');
            const clearUserSearch = document.getElementById('clearUserSearch');
            const userCards = Array.from(document.querySelectorAll('[data-user-card]'));

            if (userSearch) {
                userSearch.addEventListener('input', function () {
                    const needle = this.value.trim().toLowerCase();
                    userCards.forEach(function (card) {
                        const text = card.getAttribute('data-user-text') || '';
                        card.style.display = text.indexOf(needle) !== -1 ? '' : 'none';
                    });
                });
            }

            if (clearUserSearch && userSearch) {
                clearUserSearch.addEventListener('click', function () {
                    userSearch.value = '';
                    userCards.forEach(function (card) {
                        card.style.display = '';
                    });
                });
            }
        })();
    </script>
</div>
