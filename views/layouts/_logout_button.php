<?php

use yii\helpers\Html;

$url = $url ?? ['/site/logout'];
$label = $label ?? 'Logout';
$icon = $icon ?? 'logout';
$buttonClass = $buttonClass ?? 'app-logout-button';
$buttonStyle = $buttonStyle ?? '';
$formStyle = $formStyle ?? 'margin:0;';
$buttonType = $buttonType ?? 'submit';
?>

<?= Html::beginForm($url, 'post', ['style' => $formStyle]) ?>
    <button type="<?= Html::encode($buttonType) ?>" class="<?= Html::encode($buttonClass) ?>" style="<?= Html::encode($buttonStyle) ?>">
        <?php if ($icon !== ''): ?>
            <span class="material-symbols-outlined"><?= Html::encode($icon) ?></span>
        <?php endif; ?>
        <span class="app-sidebar-link-text"><?= Html::encode($label) ?></span>
    </button>
<?= Html::endForm() ?>
