<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var array<int, array{name:string,label:string}> $roles */
/** @var string $selectedRoleName */
/** @var array<string, array<int, array<string, mixed>>> $catalog */
/** @var array<string, array<int, string>> $preview */

$this->title = 'Akses Workspace';
$this->params['breadcrumbs'] = [
    ['label' => 'Workspace Settings', 'url' => ['index']],
    'Akses',
];

$tabs = [
    'menu' => 'Menu',
    'page' => 'Page',
    'system_builder' => 'Admin Tools',
];

$renderItems = static function (array $items, string $type) {
    ob_start();
    ?>
    <div class="perm-searchbar">
        <input type="search" class="perm-search" data-perm-search placeholder="Cari item..." aria-label="Cari permission">
        <button type="button" class="perm-btn perm-btn-ghost" data-select-all-type="<?= Html::encode($type) ?>">Select all</button>
        <button type="button" class="perm-btn perm-btn-ghost" data-clear-type="<?= Html::encode($type) ?>">Clear</button>
    </div>
    <div class="perm-list" data-perm-list="<?= Html::encode($type) ?>">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-title">Belum ada data</div>
                <div class="empty-text">Pastikan data master tersedia di database aktif.</div>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                $key = (string)($item['key'] ?? '');
                $label = (string)($item['label'] ?? $key);
                $description = (string)($item['description'] ?? '');
                $checked = !empty($item['checked']);
                ?>
                <label class="perm-item" data-perm-item>
                    <div class="perm-item-main">
                        <div class="perm-item-title"><?= Html::encode($label) ?></div>
                        <?php if ($description !== ''): ?>
                            <div class="perm-item-desc"><?= Html::encode($description) ?></div>
                        <?php endif; ?>
                    </div>
                    <input
                        type="checkbox"
                        class="perm-checkbox"
                        name="access[<?= Html::encode($type) ?>][<?= Html::encode($key) ?>]"
                        value="1"
                        <?= $checked ? 'checked' : '' ?>
                        data-access-input
                        data-access-type="<?= Html::encode($type) ?>"
                        data-access-key="<?= Html::encode($key) ?>"
                    >
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};

$selectedRoleLabel = 'Admin';
foreach ($roles as $role) {
    if (strtolower((string)($role['name'] ?? '')) === strtolower($selectedRoleName)) {
        $selectedRoleLabel = (string)($role['label'] ?? $selectedRoleName);
        break;
    }
}
?>

<div class="permission-page">
    <style>
        .permission-page {
            color: #0f172a;
        }
        .perm-hero {
            display: grid;
            gap: 16px;
            grid-template-columns: 1.5fr 0.9fr;
            align-items: stretch;
            margin-bottom: 20px;
        }
        .perm-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .perm-panel.pad {
            padding: 20px;
        }
        .perm-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .perm-title {
            margin: 14px 0 8px;
            font-size: 28px;
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -0.03em;
        }
        .perm-subtitle {
            color: #64748b;
            max-width: 760px;
            line-height: 1.6;
        }
        .perm-role-box {
            display: grid;
            gap: 12px;
        }
        .perm-role-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .perm-select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 12px 14px;
            background: #fff;
            color: #0f172a;
            font-size: 14px;
            outline: none;
        }
        .perm-helper {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }
        .perm-layout {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1.4fr) 340px;
            align-items: start;
        }
        .perm-main {
            display: grid;
            gap: 16px;
        }
        .perm-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #f8fafc;
        }
        .perm-tab-btn {
            border: 0;
            background: transparent;
            color: #475569;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .perm-tab-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }
        .perm-body {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .perm-body-head {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
        }
        .perm-body-title {
            font-size: 16px;
            font-weight: 700;
        }
        .perm-body-note {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .perm-searchbar {
            display: flex;
            gap: 10px;
            padding: 16px 20px 0;
            flex-wrap: wrap;
        }
        .perm-search {
            flex: 1 1 220px;
            min-width: 180px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 11px 12px;
            background: #fff;
            outline: none;
        }
        .perm-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .perm-btn-ghost {
            background: #f8fafc;
        }
        .perm-list {
            padding: 16px 20px 20px;
            display: grid;
            gap: 10px;
        }
        .perm-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 16px;
            background: #fff;
        }
        .perm-item-main {
            min-width: 0;
        }
        .perm-item-title {
            font-weight: 600;
            color: #0f172a;
        }
        .perm-item-desc {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.45;
        }
        .perm-checkbox {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }
        .perm-preview {
            position: sticky;
            top: 20px;
            display: grid;
            gap: 14px;
        }
        .preview-card {
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .preview-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .preview-subtitle {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }
        .preview-list {
            margin: 12px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }
        .preview-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 10px;
            color: #0f172a;
            background: #f8fafc;
            font-size: 13px;
        }
        .preview-empty {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
            padding: 12px 0 0;
        }
        .perm-footer {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
        }
        .perm-submit {
            border: 0;
            background: #0f172a;
            color: #fff;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.16);
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .empty-state {
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 18px;
            background: #f8fafc;
        }
        .empty-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .empty-text {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }
        @media (max-width: 1100px) {
            .perm-hero,
            .perm-layout {
                grid-template-columns: 1fr;
            }
            .perm-preview {
                position: static;
            }
        }
    </style>

    <form method="post" action="<?= Html::encode(Url::to(['permissions'])) ?>" id="permission-form">
        <input type="hidden" name="_csrf" value="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
        <input type="hidden" name="access_action" value="save_access">
        <input type="hidden" name="role_name" id="role-name-input" value="<?= Html::encode($selectedRoleName) ?>">

        <div class="perm-hero">
            <div class="perm-panel pad">
                <div class="perm-kicker">Role access center</div>
                <div class="perm-title">Atur akses role dari users.role</div>
                <div class="perm-subtitle">
                    Pilih role, centang menu atau halaman yang boleh dibuka, lalu simpan. Data yang dipakai tetap nyata dari database aktif.
                </div>
            </div>

            <div class="perm-panel pad">
                <div class="perm-role-box">
                    <div class="perm-role-label">Pilih Role</div>
                    <select class="perm-select" id="role-selector" name="role_name_select">
                        <?php foreach ($roles as $role): ?>
                            <?php
                            $roleName = (string)($role['name'] ?? '');
                            $roleLabel = (string)($role['label'] ?? $roleName);
                            ?>
                            <option value="<?= Html::encode($roleName) ?>"<?= $roleName === $selectedRoleName ? ' selected' : '' ?>>
                                <?= Html::encode($roleLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="perm-helper">Role diambil langsung dari kolom <code>users.role</code>. Admin tetap full akses di runtime.</div>
                </div>
            </div>
        </div>

        <div class="perm-layout">
            <div class="perm-main">
                <div class="perm-tabs" role="tablist" aria-label="Permission tabs">
                    <?php foreach ($tabs as $key => $label): ?>
                        <button type="button" class="perm-tab-btn<?= $key === 'menu' ? ' active' : '' ?>" data-tab-target="<?= Html::encode($key) ?>">
                            <?= Html::encode($label) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="perm-body">
                    <div class="perm-body-head">
                        <div>
                            <div class="perm-body-title">Atur Akses</div>
                            <div class="perm-body-note">Centang item yang boleh diakses oleh role ini.</div>
                        </div>
                    </div>

                    <div class="tab-panel active" data-tab-panel="menu">
                        <?= $renderItems($catalog['menu'] ?? [], 'menu') ?>
                    </div>

                    <div class="tab-panel" data-tab-panel="page">
                        <?= $renderItems($catalog['page'] ?? [], 'page') ?>
                    </div>

                    <div class="tab-panel" data-tab-panel="system_builder">
                        <?= $renderItems($catalog['system_builder'] ?? [], 'system_builder') ?>
                    </div>
                </div>

                <div class="perm-footer">
                    <button type="submit" class="perm-submit">Simpan Akses</button>
                </div>
            </div>

            <aside class="perm-preview">
                <div class="preview-card">
                    <div class="preview-title">Preview Role</div>
                    <div class="preview-subtitle">
                        Jika login sebagai <strong><?= Html::encode($selectedRoleLabel) ?></strong>, tampilan yang terlihat akan mengikuti akses yang dicentang.
                    </div>
                </div>

                <div class="preview-card">
                    <div class="preview-title">Menu terlihat</div>
                    <ul class="preview-list" data-preview-list="menu">
                        <?php if (!empty($preview['menu'])): ?>
                            <?php foreach ($preview['menu'] as $item): ?>
                                <li class="preview-pill"><?= Html::encode($item) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="preview-empty">Belum ada menu yang diizinkan.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="preview-card">
                    <div class="preview-title">Page bisa dibuka</div>
                    <ul class="preview-list" data-preview-list="page">
                        <?php if (!empty($preview['page'])): ?>
                            <?php foreach ($preview['page'] as $item): ?>
                                <li class="preview-pill"><?= Html::encode($item) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="preview-empty">Belum ada page yang diizinkan.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="preview-card">
                    <div class="preview-title">Admin Tools</div>
                    <ul class="preview-list" data-preview-list="system_builder">
                        <?php if (!empty($preview['system_builder'])): ?>
                            <?php foreach ($preview['system_builder'] as $item): ?>
                                <li class="preview-pill"><?= Html::encode($item) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="preview-empty">Belum ada akses builder yang diizinkan.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="preview-card">
                    <div class="preview-title">Disembunyikan</div>
                    <ul class="preview-list" data-preview-list="hidden">
                        <?php if (!empty($preview['hidden'])): ?>
                            <?php foreach ($preview['hidden'] as $item): ?>
                                <li class="preview-pill"><?= Html::encode($item) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="preview-empty">Tidak ada item yang disembunyikan.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
(function () {
    const roleSelector = document.getElementById('role-selector');
    const roleNameInput = document.getElementById('role-name-input');
    const form = document.getElementById('permission-form');
    const tabButtons = document.querySelectorAll('[data-tab-target]');
    const tabPanels = document.querySelectorAll('[data-tab-panel]');
    const searchInputs = document.querySelectorAll('[data-perm-search]');
    const accessInputs = document.querySelectorAll('[data-access-input]');

    function setActiveTab(tab) {
        tabButtons.forEach((button) => {
            button.classList.toggle('active', button.getAttribute('data-tab-target') === tab);
        });
        tabPanels.forEach((panel) => {
            panel.classList.toggle('active', panel.getAttribute('data-tab-panel') === tab);
        });
    }

    function filterItems(tab) {
        const searchInput = document.querySelector('[data-perm-list="' + tab + '"]')?.parentElement?.querySelector('[data-perm-search]');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const list = document.querySelector('[data-perm-list="' + tab + '"]');
        if (!list) {
            return;
        }
        list.querySelectorAll('[data-perm-item]').forEach((item) => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function refreshPreview() {
        const preview = {
            menu: [],
            page: [],
            system_builder: [],
            hidden: []
        };

        accessInputs.forEach((input) => {
            const type = input.getAttribute('data-access-type');
            const key = input.getAttribute('data-access-key');
            const label = input.closest('.perm-item')?.querySelector('.perm-item-title')?.textContent?.trim() || key;

            if (!type) {
                return;
            }

            if (input.checked) {
                if (preview[type]) {
                    preview[type].push(label);
                }
            } else {
                preview.hidden.push(label);
            }
        });

        Object.keys(preview).forEach((type) => {
            const list = document.querySelector('[data-preview-list="' + type + '"]');
            if (!list) {
                return;
            }

            list.innerHTML = '';
            if (!preview[type].length) {
                const empty = document.createElement('li');
                empty.className = 'preview-empty';
                empty.textContent = type === 'menu'
                    ? 'Belum ada menu yang diizinkan.'
                    : (type === 'page'
                        ? 'Belum ada page yang diizinkan.'
                        : (type === 'system_builder'
                            ? 'Belum ada akses builder yang diizinkan.'
                            : 'Tidak ada item yang disembunyikan.'));
                list.appendChild(empty);
                return;
            }

            preview[type].forEach((text) => {
                const item = document.createElement('li');
                item.className = 'preview-pill';
                item.textContent = text;
                list.appendChild(item);
            });
        });
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveTab(button.getAttribute('data-tab-target'));
        });
    });

    document.querySelectorAll('[data-select-all-type]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.getAttribute('data-select-all-type');
            document.querySelectorAll('[data-access-type="' + type + '"]').forEach((input) => {
                input.checked = true;
            });
            refreshPreview();
        });
    });

    document.querySelectorAll('[data-clear-type]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.getAttribute('data-clear-type');
            document.querySelectorAll('[data-access-type="' + type + '"]').forEach((input) => {
                input.checked = false;
            });
            refreshPreview();
        });
    });

    searchInputs.forEach((input) => {
        input.addEventListener('input', () => {
            const tab = input.closest('.tab-panel')?.getAttribute('data-tab-panel');
            if (tab) {
                filterItems(tab);
            }
        });
    });

    accessInputs.forEach((input) => {
        input.addEventListener('change', refreshPreview);
    });

    roleSelector.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('role_name', roleSelector.value);
        window.location.href = url.toString();
    });

    // Ensure each tab filter starts from the active panel.
    ['menu', 'page', 'system_builder'].forEach(filterItems);
    refreshPreview();
})();
</script>
