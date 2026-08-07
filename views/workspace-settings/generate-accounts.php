<?php

/** @var yii\web\View $this */
/** @var \app\services\GenerateAccountsService $service */
/** @var array $tables */
/** @var array $columns */
/** @var string $selectedTable */
/** @var array $roles */
/** @var string $role */
/** @var string $usernameColumn */
/** @var string $emailDomain */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Generate Accounts';

$tables = is_array($tables ?? null) ? $tables : [];
$columns = is_array($columns ?? null) ? $columns : [];
$roles = is_array($roles ?? null) ? $roles : [];
$selectedTable = is_string($selectedTable ?? null) ? $selectedTable : '';
$role = is_string($role ?? null) ? $role : '';
$usernameColumn = (string)($usernameColumn ?? '');
$emailDomain = (string)($emailDomain ?? '');

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();

?>

<div class="generate-accounts-page">
    <style>
        .generate-accounts-page {
            min-height: 100vh;
            padding: 28px;
            background: #f6f8fb;
            color: #0f172a;
            font-family: Inter, system-ui, sans-serif;
        }
        .shell { max-width: 960px; margin: 0 auto; }
        .hero { margin-bottom: 20px; }
        .eyebrow {
            font-size: 11px; letter-spacing: .18em; text-transform: uppercase;
            font-weight: 800; color: #64748b;
        }
        .title { margin: 6px 0 0; font-size: clamp(1.8rem, 2vw, 2.25rem); font-weight: 850; letter-spacing: -.04em; line-height: 1.1; }
        .subtitle { margin: 10px 0 0; max-width: 68rem; color: #64748b; line-height: 1.7; }
        .panel {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 24px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .04); overflow: hidden;
        }
        .steps {
            display: grid; grid-template-columns: repeat(6, 1fr);
            border-bottom: 1px solid #eef2f7; background: #fafbfd;
        }
        .step {
            padding: 14px 12px; text-align: center; font-size: .76rem; font-weight: 800;
            color: #94a3b8; letter-spacing: .02em;
        }
        .step .num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 999px; background: #e2e8f0;
            color: #475569; font-size: .72rem; margin-bottom: 4px;
        }
        .step.active { color: #0f172a; }
        .step.active .num { background: #0f172a; color: #fff; }
        .pane { padding: 24px; display: grid; gap: 20px; }
        .form-group { display: grid; gap: 6px; }
        .form-group label { font-size: .84rem; font-weight: 700; color: #334155; }
        .input, select.input {
            width: 100%; min-height: 46px; border-radius: 14px; border: 1px solid #d7dce5;
            background: #fff; color: #0f172a; padding: 0 14px; outline: none;
        }
        .input:focus, select.input:focus { border-color: #94a3b8; }
        .mini-note { color: #64748b; font-size: .84rem; line-height: 1.5; }
        .radio-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .radio-card {
            flex: 1 1 200px; border: 1px solid #dbe3ef; border-radius: 14px; padding: 14px;
            cursor: pointer; background: #fff; min-width: 0;
        }
        .radio-card.selected { border-color: #0f172a; background: #f8fafc; }
        .radio-card input { accent-color: #0f172a; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 4px; }
        .button {
            min-height: 44px; padding: 0 18px; border-radius: 12px; border: 1px solid #d7dce5;
            background: #fff; color: #0f172a; font-weight: 700; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .button.primary { background: #0f172a; color: #fff; border-color: #0f172a; }
        .button[disabled] { opacity: .5; cursor: not-allowed; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .stat { border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; background: #fbfdff; }
        .stat .k { font-size: .74rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
        .stat .v { font-size: 1.7rem; font-weight: 850; margin-top: 4px; color: #0f172a; }
        .stat.v-create .v { color: #047857; }
        .stat.v-skip .v { color: #b45309; }
        .stat.v-link .v { color: #1d4ed8; }
        .status-bar {
            border-radius: 16px; padding: 18px; background: #f8fafc; border: 1px solid #e2e8f0;
            display: grid; gap: 10px;
        }
        .status-bar.ok { background: #ecfdf5; border-color: #d1fae5; }
        .status-bar.err { background: #fef2f2; border-color: #fecaca; }
        .status-line { font-weight: 800; color: #0f172a; }
        .ex-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
        .ex-table th, .ex-table td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eef2f7; }
        .ex-table th { color: #64748b; font-weight: 700; }
        .empty-state {
            padding: 16px; border-radius: 18px; border: 1px dashed #d7dce5; background: #fbfdff;
            color: #64748b; line-height: 1.7; font-size: .9rem;
        }
        .meter { height: 10px; border-radius: 999px; background: #eef2f7; overflow: hidden; display: flex; }
        .meter > div { height: 100%; }
        .meter .c-create { background: #10b981; }
        .meter .c-skip { background: #f59e0b; }
        .meter .c-null { background: #cbd5e1; }
        @media (max-width: 720px) {
            .generate-accounts-page { padding: 16px; }
            .summary { grid-template-columns: 1fr; }
            .steps { grid-template-columns: repeat(3, 1fr); }
        }
    </style>

    <div class="shell">
        <div class="hero">
            <a href="<?= Url::to(['users']) ?>" class="button" style="margin-bottom:14px;">← Kembali ke Users</a>
            <div class="eyebrow">Workspace Settings · Users</div>
            <h1 class="title">Generate Accounts</h1>
            <p class="subtitle">Buat akun login secara massal dari tabel data (siswa, guru, pegawai, customer, anggota, dll). Hubungan dibuat otomatis melalui kolom <code>user_id</code> (Foreign Key) menuju <code>users.id</code> — tanpa mapping akun satu per satu.</p>
        </div>

        <div class="panel">
            <div class="steps">
                <?php
                $stepLabels = ['Sumber Tabel', 'Kolom Username', 'Password', 'Role', 'Preview', 'Generate'];
                foreach ($stepLabels as $i => $label):
                ?>
                    <div class="step <?= $i === 0 ? 'active' : '' ?>">
                        <div class="num"><?= $i + 1 ?></div>
                        <div><?= Html::encode($label) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pane">
                <form id="generateForm" style="display:grid;gap:20px;">
                    <?= Html::hiddenInput($csrfParam, $csrfToken) ?>
                    <input type="hidden" id="genTable" name="table" value="<?= Html::encode($selectedTable) ?>">
                    <input type="hidden" id="genColumn" name="username_column" value="<?= Html::encode($usernameColumn) ?>">

                    <div class="form-group">
                        <label for="genTableSelect">Langkah 1 — Pilih Sumber Tabel</label>
                        <?php if (empty($tables)): ?>
                            <div class="empty-state">
                                Belum ada tabel data yang dapat dijadikan sumber akun. Framework membaca tabel non-sistem dari metadata — setelah tabel dibuat di Table Builder, tabel akan muncul di sini.
                            </div>
                        <?php else: ?>
                            <select id="genTableSelect" class="input">
                                <option value="">— Pilih tabel —</option>
                                <?php foreach ($tables as $table): ?>
                                    <?php
                                    $tName = (string)$table['name'];
                                    $recordCount = (int)($table['total_records'] ?? 0);
                                    $hasUserId = !empty($table['has_user_id']);
                                    ?>
                                    <option value="<?= Html::encode($tName) ?>"
                                        data-records="<?= $recordCount ?>"
                                        data-linked="<?= $hasUserId ? '1' : '0' ?>"
                                        <?= $selectedTable === $tName ? 'selected' : '' ?>>
                                        <?= Html::encode(($table['label'] ?? $tName) . ' (' . $tName . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="mini-note" id="tableHelp">Pilih tabel mana yang datanya akan dibuatkan akun login. Hanya tabel non-sistem yang ditampilkan.</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="genColumnSelect">Langkah 2 — Kolom Username</label>
                        <select id="genColumnSelect" class="input" disabled>
                            <option value="">— Pilih kolom username —</option>
                        </select>
                        <span class="mini-note">Kolom dibaca dari metadata kolom tabel (tidak hardcode). Contoh: nis, email, username, no_hp.</span>
                    </div>

                    <div class="form-group">
                        <label>Langkah 3 — Password Awal</label>
                        <div class="radio-row">
                            <label class="radio-card selected" data-password-card="fixed">
                                <input type="radio" name="password_mode" value="fixed" checked>
                                <strong>Password Tetap</strong>
                                <div style="margin-top:8px;">
                                    <input id="fixedPassword" class="input" type="text" value="123456" placeholder="Password awal">
                                </div>
                            </label>
                            <label class="radio-card" data-password-card="random">
                                <input type="radio" name="password_mode" value="random">
                                <strong>Generate Random</strong>
                                <div class="mini-note" style="margin-top:6px;">Password acak unik dibuat untuk setiap akun.</div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="genRole">Langkah 4 — Role</label>
                        <select id="genRole" name="role" class="input" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= Html::encode($r['name']) ?>" <?= $role === $r['name'] ? 'selected' : '' ?>><?= Html::encode($r['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="mini-note">Role dibaca dari nilai <code>users.role</code> yang dipakai runtime (bukan tabel role).</span>
                    </div>

                    <div class="form-group">
                        <label for="genEmailDomain">Email Akun (domain)</label>
                        <input id="genEmailDomain" class="input" type="text" value="<?= Html::encode($emailDomain) ?>" placeholder="contoh: appforge.web.id">
                        <span class="mini-note">Email setiap akun dibuat otomatis sebagai <code>username@</code><strong>domain ini</strong> — misal <code>bud1@<?= Html::encode($emailDomain !== '' ? $emailDomain : 'domain') ?></code>. Diisi otomatis dari domain aplikasi.</span>
                    </div>

                    <div id="previewSection" style="display:grid;gap:14px;">
                        <div class="actions">
                            <button type="button" id="previewBtn" class="button primary">Langkah 5 — Lihat Preview</button>
                            <button type="submit" id="generateBtn" class="button" disabled>Langkah 6 — Generate</button>
                        </div>
                        <div id="previewResult" style="display:none;"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('generateForm');
            if (!form) return;

            var csrfToken = '<?= Html::encode($csrfToken) ?>';
            var tableEl = document.getElementById('genTable');
            var columnEl = document.getElementById('genColumn');
            var tableSelect = document.getElementById('genTableSelect');
            var columnSelect = document.getElementById('genColumnSelect');
            var previewBtn = document.getElementById('previewBtn');
            var generateBtn = document.getElementById('generateBtn');
            var previewResult = document.getElementById('previewResult');
            var fixedPassword = document.getElementById('fixedPassword');
            var emailDomain = document.getElementById('genEmailDomain');
            var tableHelp = document.getElementById('tableHelp');

            var columnsUrl = '<?= Url::to(['generate-account-columns']) ?>';
            var previewUrl = '<?= Url::to(['generate-accounts-preview']) ?>';
            var runUrl = '<?= Url::to(['generate-accounts-run']) ?>';

            var lastPreview = null;

            function postJson(url, data) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(data)
                }).then(function (r) { return r.json(); });
            }

            function esc(s) {
                var div = document.createElement('div');
                div.textContent = s == null ? '' : String(s);
                return div.innerHTML;
            }

            function setColumnOptions(cols) {
                columnSelect.innerHTML = '';
                var empty = document.createElement('option');
                empty.value = '';
                empty.textContent = '— Pilih kolom username —';
                columnSelect.appendChild(empty);
                (Array.isArray(cols) ? cols : []).forEach(function (c) {
                    var o = document.createElement('option');
                    o.value = c.name;
                    o.textContent = (c.label || c.name) + ' (' + c.name + ')';
                    columnSelect.appendChild(o);
                });
                columnSelect.disabled = cols.length === 0;
                columnEl.value = '';
                generateBtn.disabled = true;
                previewResult.style.display = 'none';
                lastPreview = null;
            }

            if (tableSelect) {
                tableSelect.addEventListener('change', function () {
                    var t = this.value;
                    tableEl.value = t;
                    var opt = this.options[this.selectedIndex];
                    var recs = opt ? (opt.getAttribute('data-records') || 0) : 0;
                    var linked = opt ? (opt.getAttribute('data-linked') === '1') : false;
                    if (tableHelp) {
                        tableHelp.textContent = linked
                            ? 'Tabel ini sudah memiliki kolom user_id. ' + recs + ' baris data pada tabel.'
                            : 'Kolom user_id belum ada pada tabel ini — akan dibuat otomatis saat Generate. ' + recs + ' baris data pada tabel.';
                    }
                    if (!t) {
                        columnSelect.disabled = true;
                        columnSelect.innerHTML = '<option value="">— Pilih kolom username —</option>';
                        generateBtn.disabled = true;
                        return;
                    }
                    columnSelect.disabled = true;
                    columnSelect.innerHTML = '<option value="">Memuat kolom...</option>';
                    fetch(columnsUrl + '?table=' + encodeURIComponent(t) + '&X-Requested-With=XMLHttpRequest')
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data || data.success !== true) {
                                columnSelect.disabled = true;
                                columnSelect.innerHTML = '<option value="">Kolom tidak dapat dimuat</option>';
                                return;
                            }
                            setColumnOptions(data.columns || []);
                        });
                });
            }

            if (columnSelect) {
                columnSelect.addEventListener('change', function () {
                    columnEl.value = this.value;
                    generateBtn.disabled = !(tableEl.value && this.value);
                    previewResult.style.display = 'none';
                    lastPreview = null;
                });
            }

            var radios = document.querySelectorAll('input[name="password_mode"]');
            Array.prototype.forEach.call(radios, function (radio) {
                radio.addEventListener('change', function () {
                    Array.prototype.forEach.call(document.querySelectorAll('[data-password-card]'), function (card) {
                        card.classList.toggle('selected', card.getAttribute('data-password-card') === this.value);
                    }.bind(this));
                }.bind(this));
            });

            function readPassword() {
                var checked = form.querySelector('input[name="password_mode"]:checked');
                if (checked && checked.value === 'random') {
                    return { mode: 'random', password: '' };
                }
                return { mode: 'fixed', password: fixedPassword.value || '123456' };
            }

            function readPayload() {
                var p = readPassword();
                return {
                    table: tableEl.value,
                    username_column: columnEl.value,
                    role: document.getElementById('genRole').value,
                    password_mode: p.mode,
                    password: p.password,
                    email_domain: emailDomain.value.trim()
                };
            }

            if (previewBtn) {
                previewBtn.addEventListener('click', function () {
                    if (!tableEl.value || !columnEl.value) {
                        alert('Pilih sumber tabel dan kolom username terlebih dahulu.');
                        return;
                    }
                    previewBtn.disabled = true;
                    previewBtn.textContent = 'Menghitung...';
                    postJson(previewUrl, readPayload()).then(function (d) {
                        previewBtn.disabled = false;
                        previewBtn.textContent = 'Langkah 5 — Lihat Preview';
                        lastPreview = d;
                        renderPreview(d);
                        generateBtn.disabled = !(d && d.success === true && d.to_create > 0);
                    }).catch(function () {
                        previewBtn.disabled = false;
                        previewBtn.textContent = 'Langkah 5 — Lihat Preview';
                        renderPreview(null);
                    });
                });
            }

            function renderPreview(d) {
                previewResult.style.display = 'block';
                if (!d || d.success !== true) {
                    previewResult.className = 'status-bar err';
                    previewResult.innerHTML = '<div class="status-line">Preview gagal</div>' +
                        '<div class="mini-note">' + esc(d && d.message) + '</div>';
                    return;
                }
                var total = d.total || 0;
                var toCreate = d.to_create || 0;
                var linked = d.already_linked || 0;
                var skip = d.skipped || 0;
                var createPct = total ? ((toCreate / total) * 100) : 0;
                var skipPct = total ? ((skip / total) * 100) : 0;
                var nullPct = total ? ((linked / total) * 100) : 0;

                previewResult.className = 'status-bar' + (toCreate > 0 ? ' ok' : '');
                previewResult.innerHTML = '<div class="status-line">Preview — ' + esc(d.table || '') + '</div>'
                    + '<div class="summary">'
                    + '<div class="stat"><div class="k">Data Ditemukan</div><div class="v">' + total + '</div></div>'
                    + '<div class="stat v-create"><div class="k">Akun Baru</div><div class="v">' + toCreate + '</div></div>'
                    + '<div class="stat v-skip"><div class="k">Sudah Ada / Skip</div><div class="v">' + (skip + (d.username_exists || 0)) + '</div></div>'
                    + '</div>'
                    + '<div class="meter"><div class="c-create" style="width:' + createPct.toFixed(1) + '%"></div>'
                    + '<div class="c-skip" style="width:' + skipPct.toFixed(1) + '%"></div>'
                    + '<div class="c-null" style="width:' + nullPct.toFixed(1) + '%"></div></div>'
                    + '<div class="mini-note">'
                    + 'Sudah terhubung (user_id terisi): ' + d.already_linked + '. '
                    + 'Username sudah dipakai di users: ' + d.username_exists + '. '
                    + 'Duplikat / tanpa username (skip): ' + skip + '. '
                    + 'Akun baru tidak akan dibuat ganda.'
                    + '</div>';
            }

            if (generateBtn) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!tableEl.value || !columnEl.value || generateBtn.disabled) return;
                    if (!lastPreview || lastPreview.success !== true) {
                        alert('Lihat preview terlebih dahulu.');
                        return;
                    }
                    if (!window.confirm('Generate ' + lastPreview.to_create + ' akun baru? Proses membuat akun dan mengisi kolom user_id pada tabel sumber.')) return;
                    generateBtn.disabled = true;
                    generateBtn.textContent = 'Membuat akun...';
                    postJson(runUrl, readPayload()).then(function (d) {
                        generateBtn.disabled = false;
                        generateBtn.textContent = 'Langkah 6 — Generate';
                        renderResult(d);
                        lastPreview = null;
                    }).catch(function () {
                        generateBtn.disabled = false;
                        generateBtn.textContent = 'Langkah 6 — Generate';
                        alert('Gagal memproses permintaan.');
                    });
                });
            }

            function renderResult(d) {
                previewResult.style.display = 'block';
                previewResult.className = 'status-bar ' + (d && d.success ? 'ok' : 'err');
                if (!d || d.success !== true) {
                    previewResult.innerHTML = '<div class="status-line">Generate gagal</div>' +
                        '<div class="mini-note">' + esc(d.message) + '</div>';
                    return;
                }
                var rows = '';
                (d.examples || []).forEach(function (ex) {
                    rows += '<tr><td>' + esc(ex.username) + '</td><td>' + esc(ex.user_id) + '</td><td>' + esc(d.table) + ' #' + esc(ex.pk) + '</td></tr>';
                });
                previewResult.innerHTML =
                    '<div class="status-line">Generate selesai</div>'
                    + '<div class="summary">'
                    + '<div class="stat v-create"><div class="k">Akun Dibuat</div><div class="v">' + (d.created || 0) + '</div></div>'
                    + '<div class="stat v-skip"><div class="k">Username Sudah Ada</div><div class="v">' + (d.skipped_existing || 0) + '</div></div>'
                    + '<div class="stat v-link"><div class="k">Role</div><div class="v">' + esc(d.role || '') + '</div></div>'
                    + '</div>'
                    + '<div class="mini-note">Username kosong / duplikat dilewati: ' + (d.skipped_no_username || 0) + '.</div>'
                    + (rows ? '<div class="ex-table"><table style="width:100%"><thead><tr><th>Username</th><th>ID Akun</th><th>Terhubung Ke</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '')
                    + '<div class="mini-note">Setiap akun baru terhubung ke data melalui kolom FK <code>user_id</code> pada tabel <code>' + esc(d.table) + '</code>.</div>';
            }
        })();
    </script>
</div>