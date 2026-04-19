<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var app\models\Project $project */
/** @var int $totalForms */
/** @var int $totalSubmissions */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'My Profile - ' . Html::encode($project->name);
$this->registerJs("document.body.classList.add('project-welcome-page');", \yii\web\View::POS_READY);

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
?>

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .profile-hero {
        background: linear-gradient(135deg, #3525cd 0%, #4f46e5 50%, #667eea 100%);
        position: relative;
        overflow: hidden;
        padding: 4rem 2rem;
        margin-bottom: 3rem;
        color: white;
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .profile-hero::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -5%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
    }

    .profile-hero-content {
        position: relative;
        z-index: 10;
    }

    .profile-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .profile-hero h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        font-family: 'Manrope', sans-serif;
    }

    .profile-hero p {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .profile-card {
        background: #ffffff;
        border-radius: 1.5rem;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid #e5eeff;
        box-shadow: 0 20px 40px rgba(11,28,48,0.03);
    }

    .profile-card:hover {
        box-shadow: 0 20px 50px rgba(11,28,48,0.08);
    }

    .profile-card-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e5eeff;
    }

    .profile-card-header-icon {
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        font-size: 1.5rem;
    }

    .profile-card-header h2 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
        font-family: 'Manrope', sans-serif;
        color: #0b1c30;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .profile-stat-box {
        background: #f0f4f9;
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
    }

    .profile-stat-number {
        font-size: 2rem;
        font-weight: 800;
        font-family: 'Manrope', sans-serif;
        margin-bottom: 0.5rem;
    }

    .profile-stat-label {
        font-size: 0.875rem;
        color: #464555;
        font-weight: 500;
    }

    .profile-info-row {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid #e5eeff;
    }

    .profile-info-row:last-child {
        border-bottom: none;
    }

    .profile-info-label {
        font-weight: 600;
        color: #464555;
        font-size: 0.875rem;
    }

    .profile-info-value {
        font-weight: 600;
        color: #0b1c30;
    }

    .profile-form-group {
        margin-bottom: 1.5rem;
    }

    .profile-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #0b1c30;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .profile-form-group input,
    .profile-form-group textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #c7c4d8;
        border-radius: 0.75rem;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
    }

    .profile-form-group input:focus,
    .profile-form-group textarea:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .profile-btn {
        background: linear-gradient(135deg, #3525cd 0%, #4f46e5 100%);
        color: white;
        padding: 0.875rem 1.75rem;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .profile-btn:hover {
        box-shadow: 0 10px 25px rgba(53, 37, 205, 0.3);
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    .profile-btn-secondary {
        background: #e5eeff;
        color: #0b1c30;
    }

    .profile-btn-secondary:hover {
        background: #dce9ff;
        color: #0b1c30;
    }

    .profile-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    @media (max-width: 768px) {
        .profile-grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'profile', 'sidebarVariant' => 'minimal']) ?>

<main class="app-shell-main project-home-shell" style="padding-left: var(--app-sidebar-width, 16rem); min-height: 100vh; padding-top: 2rem; padding-bottom: 2rem; transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
    <!-- Hero Section -->
    <div class="profile-hero">
        <div class="profile-hero-content">
            <div class="profile-badge">PROJECT WORKSPACE</div>
            <h1><?= Html::encode($project->name) ?></h1>
            <p>Welcome back, <strong><?= Html::encode($user->username) ?></strong> • User ID: <?= $user->id ?></p>
        </div>
    </div>

    <div class="container-lg" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
        <!-- Stats Row -->
        <div class="profile-grid">
            <div class="profile-stat-box">
                <div class="profile-stat-number" style="color: #4f46e5;"><?= $totalForms ?></div>
                <div class="profile-stat-label">Total Forms</div>
            </div>
            <div class="profile-stat-box">
                <div class="profile-stat-number" style="color: #006c49;"><?= $totalSubmissions ?></div>
                <div class="profile-stat-label">Total Submissions</div>
            </div>
            <div class="profile-stat-box">
                <div class="profile-stat-number" style="color: #4d44e3;"><?= date('M Y', strtotime($user->created_at)) ?></div>
                <div class="profile-stat-label">Member Since</div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="profile-grid-2">
            <!-- Account Details -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="profile-card-header-icon" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                        <span class="material-symbols-outlined">account_circle</span>
                    </div>
                    <h2>Account Details</h2>
                </div>
                <div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Username</span>
                        <span class="profile-info-value"><?= Html::encode($user->username) ?></span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">User ID</span>
                        <span class="profile-info-value"><code style="background: #f0f4f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem;"><?= $user->id ?></code></span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Account Status</span>
                        <span class="profile-info-value" style="color: #006c49;">
                            <span style="display: inline-block; width: 8px; height: 8px; background: #006c49; border-radius: 50%; margin-right: 0.5rem;"></span>Active
                        </span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Last Updated</span>
                        <span class="profile-info-value"><?= date('M d, Y H:i', strtotime($user->updated_at)) ?></span>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="profile-card-header-icon" style="background: rgba(126, 48, 0, 0.1); color: #7e3000;">
                        <span class="material-symbols-outlined">lock</span>
                    </div>
                    <h2>Change Password</h2>
                </div>
                <?php $form = ActiveForm::begin([
                    'action' => ['site/change-password'],
                    'method' => 'post',
                ]); ?>
                <div class="profile-form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter your current password" required>
                </div>
                <div class="profile-form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Enter new password" minlength="6" required>
                    <small style="color: #464555; display: block; margin-top: 0.25rem;">Minimum 6 characters</small>
                </div>
                <div class="profile-form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" minlength="6" required>
                </div>
                <button type="submit" class="profile-btn">
                    <span class="material-symbols-outlined">lock</span> Update Password
                </button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>

        <!-- Project Info & Actions Row -->
        <div class="profile-grid-2" style="margin-top: 2rem;">
            <!-- Project Information -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="profile-card-header-icon" style="background: rgba(0, 108, 73, 0.1); color: #006c49;">
                        <span class="material-symbols-outlined">folder</span>
                    </div>
                    <h2>Active Project</h2>
                </div>
                <div>
                    <div style="margin-bottom: 1.5rem;">
                        <div style="font-size: 0.75rem; color: #464555; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">Project Name</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #0b1c30; font-family: 'Manrope', sans-serif;"><?= Html::encode($project->name) ?></div>
                    </div>
                    <div style="padding-top: 1.5rem; border-top: 1px solid #e5eeff;">
                        <div style="font-size: 0.75rem; color: #464555; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1rem;">Project Statistics</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="profile-stat-box" style="margin: 0;">
                                <div class="profile-stat-number" style="color: #4f46e5; font-size: 1.5rem;"><?= $totalForms ?></div>
                                <div class="profile-stat-label">Forms</div>
                            </div>
                            <div class="profile-stat-box" style="margin: 0;">
                                <div class="profile-stat-number" style="color: #006c49; font-size: 1.5rem;"><?= $totalSubmissions ?></div>
                                <div class="profile-stat-label">Submissions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="profile-card-header-icon" style="background: rgba(77, 68, 227, 0.1); color: #4d44e3;">
                        <span class="material-symbols-outlined">lightning_bolt</span>
                    </div>
                    <h2>Quick Actions</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?= Html::a('<span class="material-symbols-outlined" style="font-size: 1.25rem;">add</span> Create New Form', ['form/create'], [
                        'class' => 'profile-btn'
                    ]) ?>
                    <?= Html::a('<span class="material-symbols-outlined" style="font-size: 1.25rem;">table_chart</span> Create Table', ['table-builder/create'], [
                        'class' => 'profile-btn profile-btn-secondary'
                    ]) ?>
                    <?= Html::a('<span class="material-symbols-outlined" style="font-size: 1.25rem;">description</span> View Forms', ['form/index'], [
                        'class' => 'profile-btn profile-btn-secondary'
                    ]) ?>
                    <?= Html::a('<span class="material-symbols-outlined" style="font-size: 1.25rem;">table_chart</span> View Tables', ['table-builder/index'], [
                        'class' => 'profile-btn profile-btn-secondary'
                    ]) ?>
                    <?= Html::a('<span class="material-symbols-outlined" style="font-size: 1.25rem;">home</span> Back to Projects', ['project/index'], [
                        'class' => 'profile-btn profile-btn-secondary'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</main>
