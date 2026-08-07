<?php

/** @var yii\web\View $this */
/** @var array $diagnosis */
/** @var \app\models\ProjectUser|null $authUser */
/** @var bool $componentAvailable */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Identity Debug';

$diagnosis = is_array($diagnosis ?? null) ? $diagnosis : [];
$componentAvailable = (bool)($componentAvailable ?? false);
$authUser = $authUser ?? null;

$projectId = $diagnosis['project_id'] ?? null;
$status = (string)($diagnosis['status'] ?? 'unknown');
$reason = (string)($diagnosis['reason'] ?? '');
$config = is_array($diagnosis['config'] ?? null) ? $diagnosis['config'] : [];
$identity = is_array($diagnosis['identity'] ?? null) ? $diagnosis['identity'] : null;

$isResolved = $status === 'resolved' && $identity !== null;
$statusLabel = $isResolved ? 'Current Identity tersedia' : 'Current Identity tidak tersedia';

$configEnabled = isset($config['identity_table']) && (string)$config['identity_table'] !== '';
$configTable = (string)($config['identity_table'] ?? '');
$configRecordId = (string)($config['identity_record_id'] ?? '');

function identity_debug_variant(string $status, bool $isResolved): array
{
    if ($isResolved) {
        return ['bg' => '#ecfdf5', 'bd' => '#d1fae5', 'fg' => '#047857', 'icon' => 'check_circle'];
    }
    if (in_array($status, ['error'], true)) {
        return ['bg' => '#fef2f2', 'bd' => '#fecaca', 'fg' => '#b91c1c', 'icon' => 'error'];
    }
    return ['bg' => '#fff7ed', 'bd' => '#fed7aa', 'fg' => '#c2410c', 'icon' => 'info'];
}
$variant = identity_debug_variant($status, $isResolved);

function identity_debug_row(string $label, string $value): string
{
    return '<div style="display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:.9rem;">'
        . '<span style="color:#64748b;flex:0 0 220px;">' . Html::encode($label) . '</span>'
        . '<span style="color:#0f172a;text-align:right;word-break:break-word;">' . $value . '</span>'
        . '</div>';
}

function identity_debug_value($value): string
{
    if ($value === null || $value === '') {
        return '<span style="color:#94a3b8;">—</span>';
    }
    return Html::encode((string)$value);
}
?>

<div style="max-width:920px;margin:0 auto;padding:32px 20px 64px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;line-height:1.5;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.4rem;margin:0 0 4px;">Identity Debug</h1>
            <p style="margin:0;color:#64748b;font-size:.92rem;">Panel validasi Current Identity. Hanya membaca, tidak mengubah apa pun.</p>
        </div>
        <a href="<?= Html::encode(Url::to(['index'])) ?>" style="color:#1d4ed8;font-size:.9rem;text-decoration:none;">&larr; Kembali ke Workspace Settings</a>
    </div>

    <?php if (!$componentAvailable): ?>
        <div style="margin-top:24px;padding:18px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;">
            Komponen <code>currentIdentity</code> tidak terdaftar pada aplikasi ini. Periksa <code>config/web.php</code>.
        </div>
    <?php else: ?>

        <div style="margin-top:24px;padding:16px 18px;border-radius:12px;background:<?= Html::encode($variant['bg']) ?>;border:1px solid <?= Html::encode($variant['bd']) ?>;color:<?= Html::encode($variant['fg']) ?>;display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="font-size:22px;"><?= Html::encode($variant['icon']) ?></span>
            <div>
                <strong style="display:block;"><?= Html::encode($statusLabel) ?></strong>
                <span style="font-size:.88rem;"><?= Html::encode($reason) ?></span>
            </div>
        </div>

        <div style="margin-top:24px;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;">
            <h2 style="font-size:1.05rem;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;color:#1d4ed8;">lock</span> Authentication
            </h2>
            <?= identity_debug_row('Status login', $authUser !== null
                ? '<span style="color:#047857;">&#10003; Login berhasil</span>'
                : '<span style="color:#94a3b8;">Tidak terautentikasi</span>') ?>
            <?= identity_debug_row('Project ID', identity_debug_value($projectId)) ?>
            <?= identity_debug_row('User ID', $authUser !== null ? identity_debug_value($authUser->id) : '—') ?>
            <?= identity_debug_row('Username', $authUser !== null ? identity_debug_value($authUser->username) : '—') ?>
            <?= identity_debug_row('Role', $authUser !== null ? identity_debug_value($authUser->role) : '—') ?>
            <?= identity_debug_row('E-mail', $authUser !== null ? identity_debug_value($authUser->email) : '—') ?>
        </div>

        <div style="margin-top:16px;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;">
            <h2 style="font-size:1.05rem;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;color:#1d4ed8;">shield_person</span> User Mapping (Konfigurasi Akun)
            </h2>
            <?= identity_debug_row('Terhubung', $configEnabled ? '<span style="color:#047857;">Ya</span>' : '<span style="color:#b91c1c;">Tidak</span>') ?>
            <?= identity_debug_row('Entity Table', identity_debug_value($configTable)) ?>
            <?= identity_debug_row('Record ID', identity_debug_value($configRecordId)) ?>
        </div>

        <div style="margin-top:16px;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;">
            <h2 style="font-size:1.05rem;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;color:#1d4ed8;">badge</span> Current Identity (Hasil Resolve)
            </h2>
            <?php if ($identity === null): ?>
                <p style="color:#64748b;margin:0;font-size:.92rem;">
                    Current Identity bernilai <code>null</code>.
                    <?php if (!$configEnabled): ?>Akun belum dihubungkan dengan data domain (User Mapping belum diatur).<?php endif; ?>
                    Auth dan sesi login tidak terpengaruh.
                </p>
            <?php else: ?>
                <?= identity_debug_row('Identity Table', identity_debug_value($identity['table_name'] ?? null)) ?>
                <?= identity_debug_row('Identity Record ID', identity_debug_value($identity['identity_record_id'] ?? null)) ?>
                <?= identity_debug_row('User ID (pemicu)', identity_debug_value($identity['user_id'] ?? null)) ?>
            <?php endif; ?>
        </div>

        <?php if ($identity !== null): ?>
            <div style="margin-top:16px;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;">
                <h2 style="font-size:1.05rem;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:18px;color:#1d4ed8;">database</span> Resolved Record
                </h2>
                <pre style="margin:0;padding:16px;background:#0f172a;color:#e2e8f0;border-radius:10px;overflow:auto;font-size:.82rem;line-height:1.55;"><?= Html::encode(json_encode($identity['record'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        <?php endif; ?>

        <div style="margin-top:16px;padding:14px 18px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:.85rem;">
            <strong>Catatan:</strong> Panel ini hanya membaca <code>Yii::$app-&gt;currentIdentity</code>. Identity di-resolve sekali per request
            (di-cache), dan seluruh resolusi dilakukan oleh <code>UserMappingService::resolveCurrentIdentity()</code> — pembacaan O(1)
            dari mapping akun, tanpa query duplikat dan tanpa resolver kedua.
        </div>

    <?php endif; ?>
</div>
