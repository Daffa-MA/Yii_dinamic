<?php
/* @var $this yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\web\View;

AppAsset::register($this);

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap', ['position' => View::POS_HEAD]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => View::POS_HEAD]);

$currentRoute = Yii::$app->controller->route;
$activeMenu = 'dashboard';
if ($currentRoute === 'site/dashboard') {
    $activeMenu = 'dashboard';
} elseif ($currentRoute === 'site/profile') {
    $activeMenu = 'profile';
} else {
    $activeMenu = (string) Yii::$app->session->get('active_menu', 'dashboard');
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-full">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e40af">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'on-surface': '#0b1c30',
                        'on-surface-variant': '#464555',
                        'surface': '#fafbfe',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#f8fafd',
                        'surface-container': '#f0f4f9',
                        'surface-container-high': '#e8eef7',
                        'primary-container': '#4f46e5',
                        'primary': '#3525cd',
                        'secondary': '#006c49',
                        'tertiary': '#7e3000',
                        'surface-tint': '#4d44e3',
                        'outline-variant': '#c7c4d8',
                        'outline': '#777587',
                        'error': '#ba1a1a',
                    },
                    fontFamily: {
                        headline: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    }
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: 400;
            font-style: normal;
            font-size: 24px;
            display: inline-flex;
            line-height: 1;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="dashboard-main-page font-body text-on-surface antialiased min-h-full bg-[#f9fafb]">
<?php $this->beginBody() ?>

<div class="dashboard-layout flex min-h-screen">
    <?= $this->render('_sidebar', ['activeMenu' => $activeMenu, 'sidebarVariant' => 'full']) ?>

    <main class="dashboard-main min-w-0 flex-1 py-4 pl-[var(--app-sidebar-width,16rem)] transition-[padding-left] duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]" id="main">
        <div class="container-fluid">
            <?= $content ?>
        </div>
    </main>
</div>

<style>
    body.has-app-sidebar {
        --app-sidebar-expanded-width: 16rem;
        --app-sidebar-collapsed-width: 5.25rem;
        --app-sidebar-width: var(--app-sidebar-expanded-width);
    }
    body.has-app-sidebar.app-sidebar-collapsed {
        --app-sidebar-width: var(--app-sidebar-collapsed-width);
    }
</style>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
