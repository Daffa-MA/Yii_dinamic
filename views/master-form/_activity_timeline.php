<?php

use yii\helpers\Html;

/* @var $activityLogs app\models\MasterFormActivityLog[] */
?>
<div class="view-card" style="margin-bottom:24px;">
    <div class="view-card-header">
        <div class="view-card-icon"><span class="material-symbols-outlined">timeline</span></div>
        <div class="view-card-title">Activity Timeline</div>
    </div>
    <div class="view-card-body" style="padding:16px 24px;">
        <?php if (empty($activityLogs)): ?>
            <div class="view-empty-state" style="padding:24px 0;">
                <p>No activity logs yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($activityLogs as $log): ?>
                <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                    <div style="width:10px;height:10px;border-radius:999px;margin-top:6px;background:<?= $log->status === 'success' ? '#22c55e' : ($log->status === 'failed' ? '#ef4444' : '#f59e0b') ?>;"></div>
                    <div style="flex:1;">
                        <div style="font-size:12px;font-weight:600;color:#0f172a;"><?= Html::encode((string)$log->event_type) ?></div>
                        <div style="font-size:12px;color:#475569;"><?= Html::encode((string)$log->message) ?></div>
                        <div style="font-size:11px;color:#94a3b8;"><?= Html::encode((string)$log->created_at) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

