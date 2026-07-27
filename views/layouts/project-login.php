<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\models\WorkspaceSettings;
use yii\helpers\Html;

$wsSettings = new WorkspaceSettings();
$wsSettings->loadFromDatabase();
$faviconAsset = $wsSettings->getFaviconAsset();
$faviconUrl = (string)($faviconAsset['url'] ?? '');
if ($faviconUrl === '') {
    $faviconUrl = \yii\helpers\Url::to('/favicon.ico');
}
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => $faviconUrl]);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);

$this->title = $this->title ?: 'Login Aplikasi';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        :root {
            color-scheme: dark;
        }
        html, body {
            min-height: 100%;
            margin: 0;
        }
        body.project-login-layout {
            min-height: 100vh;
            overflow-x: hidden;
            background: #07111f;
        }
        .project-login-bg-noise {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .18;
            background-image:
                radial-gradient(circle at 18% 20%, rgba(255,255,255,.18), transparent 28%),
                radial-gradient(circle at 82% 14%, rgba(255,255,255,.11), transparent 24%),
                radial-gradient(circle at 50% 78%, rgba(255,255,255,.09), transparent 32%);
            mix-blend-mode: screen;
        }
        .project-login-page-shell {
            position: relative;
            min-height: 100vh;
        }
        .project-login-page-shell a {
            color: inherit;
        }
    </style>
</head>
<body class="project-login-layout">
<?php $this->beginBody() ?>
<div class="project-login-bg-noise"></div>
<div class="project-login-page-shell">
    <?= $content ?>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
