<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

// Register FontAwesome globally
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        /* Modern Navigation */
        #header .navbar {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }

        #header .navbar-brand {
            font-weight: 700;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        #header .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.8) !important;
        }

        #header .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        /* Body Styles */
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
        }

        main {
            padding-top: 80px !important;
        }

        /* Footer */
        #footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%) !important;
            color: white;
            margin-top: 60px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        #footer .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        /* Smooth Animations */
        main {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100<?= $this->context->route === 'site/login' ? ' login-page' : '' ?>">
    <?php $this->beginBody() ?>

    <header id="header">
        <?php if ($this->context->route !== 'site/dashboard'): ?>
        <?php
        NavBar::begin([
            'brandLabel' => '<i class="fas fa-rocket"></i> ' . Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark fixed-top']
        ]);
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav ms-auto'],
            'items' => [
                ['label' => '<i class="fas fa-home"></i> Home', 'url' => ['/site/index'], 'encode' => false],
                ['label' => '<i class="fas fa-chart-line"></i> Dashboard', 'url' => ['/site/dashboard'], 'encode' => false],
                ['label' => '<i class="fas fa-file-alt"></i> Forms', 'url' => ['/form/index'], 'encode' => false],
                ['label' => '<i class="fas fa-table"></i> Tables', 'url' => ['/table-builder/index'], 'encode' => false],
                Yii::$app->user->isGuest
                    ? ['label' => '<i class="fas fa-sign-in-alt"></i> Login', 'url' => ['/site/login'], 'encode' => false]
                    : '<li class="nav-item"><a class="nav-link" href="/site/profile"><i class="fas fa-user-circle"></i> Profile</a></li>'
                    . '<li class="nav-item">'
                    . '<a class="nav-link" href="/site/logout" data-method="post" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>'
                    . '</li>'
            ]
        ]);
        NavBar::end();
        ?>
        <?php endif; ?>
    </header>

    <main id="main" class="flex-shrink-0" role="main">
        <div class="container" style="max-width: 1400px;">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            <?php endif ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    </main>

    <footer id="footer" class="mt-auto">
        <div style="background: linear-gradient(180deg, #ffffff 0%, #f8f9ff 100%);">
            <div class="container" style="max-width: 1400px;">
                <div style="border-top: 1px solid rgba(79, 70, 229, 0.1); padding: 32px 0;">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
                        <!-- Left Section -->
                        <div style="display: flex; align-items: center; gap: 24px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #4f46e5, #6366f1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-rocket" style="font-size: 16px;"></i>
                                </div>
                                <div>
                                    <span style="font-weight: 600; font-size: 15px; color: #0b1c30;"><?= Yii::$app->name ?></span>
                                    <span style="display: block; font-size: 12px; color: #464555;">Intelligent Form Builder</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Center Section -->
                        <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #464555;">
                                <i class="fas fa-copyright" style="font-size: 12px;"></i>
                                <span><?= date('Y') ?></span>
                            </div>
                            <div style="width: 4px; height: 4px; background: #c7c4d8; border-radius: 50%;"></div>
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #464555;">
                                <span>Powered by</span>
                                <a href="https://www.yiiframework.com" target="_blank" style="color: #4f46e5; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                    <i class="fab fa-yii" style="font-size: 14px;"></i>
                                    Yii Framework
                                </a>
                            </div>
                            <div style="width: 4px; height: 4px; background: #c7c4d8; border-radius: 50%;"></div>
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #464555;">
                                <span>Built with</span>
                                <i class="fas fa-heart" style="color: #006c49; font-size: 12px; animation: heartbeat 1.5s ease infinite;"></i>
                                <span>for modern forms</span>
                            </div>
                        </div>
                        
                        <!-- Right Section -->
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <a href="#" style="width: 36px; height: 36px; border-radius: 8px; background: #e5eeff; display: flex; align-items: center; justify-content: center; color: #464555; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#4f46e5'; this.style.color='white'" onmouseout="this.style.background='#e5eeff'; this.style.color='#464555'">
                                <i class="fab fa-github" style="font-size: 16px;"></i>
                            </a>
                            <a href="#" style="width: 36px; height: 36px; border-radius: 8px; background: #e5eeff; display: flex; align-items: center; justify-content: center; color: #464555; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#4f46e5'; this.style.color='white'" onmouseout="this.style.background='#e5eeff'; this.style.color='#464555'">
                                <i class="fab fa-twitter" style="font-size: 16px;"></i>
                            </a>
                            <a href="#" style="width: 36px; height: 36px; border-radius: 8px; background: #e5eeff; display: flex; align-items: center; justify-content: center; color: #464555; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#4f46e5'; this.style.color='white'" onmouseout="this.style.background='#e5eeff'; this.style.color='#464555'">
                                <i class="fas fa-envelope" style="font-size: 16px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
    </style>

    <?php $this->endBody() ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutLink = document.getElementById('logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Create a form and submit it
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.href;

                    // Add CSRF token
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_csrf';
                        csrfInput.value = csrfMeta.getAttribute('content');
                        form.appendChild(csrfInput);
                    }

                    document.body.appendChild(form);
                    form.submit();
                });
            }
        });
    </script>
</body>

</html>
<?php $this->endPage() ?>