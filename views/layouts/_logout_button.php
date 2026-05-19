<?php

use yii\helpers\Html;

$label = $label ?? 'Logout';
$icon = $icon ?? 'logout';
$buttonClass = $buttonClass ?? 'app-logout-button';
$buttonStyle = $buttonStyle ?? '';
$formStyle = $formStyle ?? 'margin:0;';
?>

<form method="post" action="/site/logout" style="<?= Html::encode($formStyle) ?>">
    <input type="hidden" name="<?= Html::encode(Yii::$app->request->csrfParam) ?>" value="<?= Html::encode(Yii::$app->request->csrfToken) ?>">
    <button type="submit" class="<?= Html::encode($buttonClass) ?>" style="<?= Html::encode($buttonStyle) ?>">
        <?php if ($icon !== ''): ?>
            <span class="material-symbols-outlined"><?= Html::encode($icon) ?></span>
        <?php endif; ?>
        <span class="app-sidebar-link-text"><?= Html::encode($label) ?></span>
    </button>
</form>
