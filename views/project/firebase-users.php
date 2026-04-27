<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var array<int, array<string, mixed>> $firebaseUsers */
/** @var yii\data\Pagination $pagination */
/** @var int $totalFirebaseUsers */

use yii\bootstrap5\Html;
use yii\widgets\LinkPager;

$this->title = 'User Firebase - ' . Html::encode($project->name);
$this->registerJs("document.body.classList.add('dashboard-main-page');", \yii\web\View::POS_READY);
?>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'firebase-users', 'sidebarVariant' => 'full']) ?>

<main class="app-shell-main project-home-shell" style="padding-left: var(--app-sidebar-width, 16rem); min-height: 100vh; padding-top: 2rem; padding-bottom: 2rem; transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
    <div class="container-lg" style="max-width: 1280px;">
        <section class="firebase-hero">
            <div>
                <p class="firebase-kicker">
                    <span class="material-symbols-outlined">groups</span>
                    User Firebase Public Form
                </p>
                <h1>Riwayat login user publik</h1>
                <p>Project aktif: <strong><?= Html::encode($project->name) ?></strong></p>
            </div>
            <div class="firebase-count-chip">
                <span class="material-symbols-outlined">badge</span>
                <?= (int)$totalFirebaseUsers ?> user
            </div>
        </section>

        <section class="firebase-panel">
            <?php if (empty($firebaseUsers)): ?>
                <div class="firebase-empty">
                    <span class="material-symbols-outlined">inbox</span>
                    <h3>Belum ada login Firebase</h3>
                    <p>Data user akan muncul setelah ada user yang login di halaman public render dan mengirim submission.</p>
                </div>
            <?php else: ?>
                <div class="firebase-table-wrap">
                    <table class="firebase-table">
                        <thead>
                            <tr>
                                <th style="width:72px;">#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Firebase UID</th>
                                <th style="width:120px;">Forms</th>
                                <th style="width:140px;">Submissions</th>
                                <th style="width:190px;">Terakhir Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($firebaseUsers as $index => $item): ?>
                                <?php
                                $rowNumber = (int)$pagination->offset + $index + 1;
                                $firebaseName = trim((string)($item['firebase_name'] ?? ''));
                                $firebaseEmail = trim((string)($item['firebase_email'] ?? ''));
                                $firebaseUid = trim((string)($item['firebase_uid'] ?? ''));
                                $submissionCount = (int)($item['submission_count'] ?? 0);
                                $formCount = (int)($item['form_count'] ?? 0);
                                $lastLogin = trim((string)($item['last_login_at'] ?? ''));
                                $lastLoginLabel = $lastLogin !== '' && strtotime($lastLogin) !== false
                                    ? date('d M Y H:i', strtotime($lastLogin))
                                    : '-';
                                $displayUid = strlen($firebaseUid) > 20
                                    ? substr($firebaseUid, 0, 10) . '...' . substr($firebaseUid, -6)
                                    : $firebaseUid;
                                ?>
                                <tr>
                                    <td><?= $rowNumber ?></td>
                                    <td><?= Html::encode($firebaseName !== '' ? $firebaseName : 'Tanpa nama') ?></td>
                                    <td><?= Html::encode($firebaseEmail !== '' ? $firebaseEmail : '-') ?></td>
                                    <td><code title="<?= Html::encode($firebaseUid) ?>"><?= Html::encode($displayUid !== '' ? $displayUid : '-') ?></code></td>
                                    <td><?= $formCount ?></td>
                                    <td><?= $submissionCount ?></td>
                                    <td><?= Html::encode($lastLoginLabel) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="firebase-pagination">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                        'options' => ['class' => 'pagination pagination-sm justify-content-center'],
                        'linkOptions' => ['class' => 'page-link'],
                    ]) ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
    .firebase-hero {
        background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 55%, #e9f2ff 100%);
        color: #0b1c30;
        border-radius: 20px;
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        margin-bottom: 20px;
    }

    .firebase-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin: 0 0 8px;
        opacity: .8;
    }

    .firebase-hero h1 {
        margin: 0 0 6px;
        font-size: 28px;
        font-weight: 800;
    }

    .firebase-hero p {
        margin: 0;
        opacity: .92;
    }

    .firebase-count-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(79, 70, 229, .08);
        border: 1px solid rgba(79, 70, 229, .22);
        font-weight: 700;
        white-space: nowrap;
    }

    .firebase-panel {
        background: #fff;
        border-radius: 20px;
        border: 1px solid #e5eeff;
        padding: 20px;
        box-shadow: 0 18px 32px rgba(11, 28, 48, 0.06);
    }

    .firebase-empty {
        text-align: center;
        padding: 52px 20px;
        color: #464555;
    }

    .firebase-empty .material-symbols-outlined {
        font-size: 52px;
        color: #94a3b8;
        display: inline-flex;
        margin-bottom: 10px;
    }

    .firebase-empty h3 {
        margin: 0 0 8px;
        color: #0b1c30;
    }

    .firebase-empty p {
        margin: 0;
    }

    .firebase-table-wrap {
        overflow: auto;
    }

    .firebase-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 960px;
    }

    .firebase-table th,
    .firebase-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #eef2f7;
        font-size: 14px;
        text-align: left;
        color: #0b1c30;
        vertical-align: middle;
    }

    .firebase-table th {
        font-size: 12px;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748b;
        background: #f8fbff;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .firebase-table code {
        font-size: 12px;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 4px 8px;
        color: #334155;
    }

    .firebase-pagination {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #eef2f7;
    }

    @media (max-width: 768px) {
        .firebase-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .firebase-hero h1 {
            font-size: 22px;
        }
    }
</style>
