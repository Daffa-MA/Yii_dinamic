<?php
/* @var $this yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\web\View;
use app\models\WorkspaceSettings;

AppAsset::register($this);

$workspaceSettings = new WorkspaceSettings();
$workspaceSettings->loadFromDatabase();
$cssVars = $workspaceSettings->getCssVars();

// Generate CSS custom properties
$customProps = [
    'workspace-logo-bg' => $cssVars['workspace-logo-bg'] ?? '#4f46e5',
    'sidebar-bg-start' => $cssVars['sidebar-bg-start'] ?? '#07111f',
    'sidebar-bg-end' => $cssVars['sidebar-bg-end'] ?? '#111827',
    'sidebar-border-color' => $cssVars['sidebar-border-color'] ?? 'rgba(148, 163, 184, 0.16)',
    'sidebar-text-color' => $cssVars['sidebar-text-color'] ?? '#e2e8f0',
    'sidebar-text-muted' => $cssVars['sidebar-text-muted'] ?? '#94a3b8',
    'sidebar-hover-bg' => $cssVars['sidebar-hover-bg'] ?? 'rgba(255, 255, 255, 0.08)',
    'sidebar-hover-text' => $cssVars['sidebar-hover-text'] ?? '#ffffff',
    'sidebar-active-bg-start' => $cssVars['sidebar-active-bg-start'] ?? '#2563eb',
    'sidebar-active-bg-end' => $cssVars['sidebar-active-bg-end'] ?? '#06b6d4',
    'sidebar-active-text' => $cssVars['sidebar-active-text'] ?? '#ffffff',
    'sidebar-active-shadow' => $cssVars['sidebar-active-shadow'] ?? '0 8px 24px rgba(37, 99, 235, 0.28)',
    'light-sidebar-bg' => $cssVars['light-sidebar-bg'] ?? '#f8fafc',
    'light-sidebar-border' => $cssVars['light-sidebar-border'] ?? 'rgba(148, 163, 184, 0.2)',
    'topnav-bg' => $cssVars['topnav-bg'] ?? '#ffffff',
    'topnav-border-color' => $cssVars['topnav-border-color'] ?? '#e2e8f0',
    'topnav-text-color' => $cssVars['topnav-text-color'] ?? '#1e293b',
];

$customPropsStyle = '.dashboard-layout, .dynamic-workspace-layout, body.dashboard-main-page {';
foreach ($customProps as $key => $value) {
    $customPropsStyle .= "--ws-{$key}: " . Html::encode($value) . "; ";
}
$customPropsStyle .= '}';

// Scope theme injection: only register workspace theme for dynamic layouts.
// Exclude hardcoded pages that must remain independent (e.g., project listing).
$currentRouteForTheme = Yii::$app->controller->route ?? '';
$themeExclusions = ['project/index', 'project-list/index'];
if (!in_array($currentRouteForTheme, $themeExclusions, true)) {
    $this->registerCss($customPropsStyle, ['position' => View::POS_BEGIN]);
}

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap', ['position' => View::POS_HEAD]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => View::POS_HEAD]);

$currentRoute = Yii::$app->controller->route;
$rolePageHero = new \app\components\RolePageHero();
$roleHeroData = $rolePageHero->build($this->title ?? '');
$shouldRenderRoleHero = !empty($roleHeroData['should_render']);
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

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-main {
            min-width: 0;
            flex: 1 1 auto;
            padding-top: 80px;
            padding-bottom: 1rem;
            padding-left: var(--app-sidebar-width, 16rem);
            transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .dashboard-main.no-topnav {
            padding-top: 1rem;
        }
    </style>
</head>
<body class="dashboard-main-page font-body text-on-surface antialiased min-h-full bg-[#f9fafb]">
<?php $this->beginBody() ?>

<div class="dashboard-layout">
    <?= $this->render('_sidebar', ['activeMenu' => $activeMenu, 'sidebarVariant' => 'full']) ?>
    <?= $this->render('_topnav') ?>

    <main class="dashboard-main" id="main">
        <div class="container-fluid">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                <?php
                $color = 'rgba(59, 130, 246, 0.12)';
                $textColor = '#1e3a8a';
                $borderColor = 'rgba(59, 130, 246, 0.22)';
                if ($type === 'success') {
                    $color = 'rgba(16, 185, 129, 0.12)';
                    $textColor = '#065f46';
                    $borderColor = 'rgba(16, 185, 129, 0.22)';
                } elseif ($type === 'error') {
                    $color = 'rgba(239, 68, 68, 0.12)';
                    $textColor = '#7f1d1d';
                    $borderColor = 'rgba(239, 68, 68, 0.22)';
                } elseif ($type === 'warning') {
                    $color = 'rgba(245, 158, 11, 0.12)';
                    $textColor = '#78350f';
                    $borderColor = 'rgba(245, 158, 11, 0.22)';
                }
                ?>
                <div style="margin:0 0 1rem;padding:.9rem 1rem;border-radius:16px;background:<?= Html::encode($color) ?>;color:<?= Html::encode($textColor) ?>;border:1px solid <?= Html::encode($borderColor) ?>;font-weight:600;box-shadow:0 8px 24px rgba(15,23,42,.05);">
                    <?= is_array($message) ? implode(' ', array_map('yii\helpers\Html::encode', $message)) : Html::encode((string)$message) ?>
                </div>
            <?php endforeach; ?>
            <?php if ($shouldRenderRoleHero): ?>
                <?= $this->render('_role_page_hero', ['hero' => $roleHeroData]) ?>
            <?php endif; ?>
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
