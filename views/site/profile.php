<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var int $totalForms */
/** @var int $totalSubmissions */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'My Profile';
?>

<div class="user-profile">
    <h1 class="mb-4"><i class="bi bi-person-circle"></i> My Profile</h1>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Profile Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Username</th>
                            <td><?= Html::encode($user->username) ?></td>
                        </tr>
                        <tr>
                            <th>User ID</th>
                            <td><code><?= $user->id ?></code></td>
                        </tr>
                        <tr>
                            <th>Member Since</th>
                            <td><?= date('F d, Y', strtotime($user->created_at)) ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?= date('F d, Y H:i', strtotime($user->updated_at)) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="display-4 text-primary"><?= $totalForms ?></div>
                                <div class="text-muted">Total Forms</div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="display-4 text-success"><?= $totalSubmissions ?></div>
                                <div class="text-muted">Total Submissions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'action' => ['site/change-password'],
                        'method' => 'post',
                    ]); ?>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Change Password
                    </button>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?= Html::a('<i class="bi bi-speedometer2"></i> Dashboard', ['site/dashboard'], [
                            'class' => 'list-group-item list-group-item-action'
                        ]) ?>
                        <?= Html::a('<i class="bi bi-file-earmark-text"></i> My Forms', ['form/index'], [
                            'class' => 'list-group-item list-group-item-action'
                        ]) ?>
                        <?= Html::a('<i class="bi bi-plus-circle"></i> Create New Form', ['form/create'], [
                            'class' => 'list-group-item list-group-item-action'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
