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

    <footer id="footer" class="mt-auto py-4">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-12">
                    <p class="mb-0">
                        <i class="fas fa-copyright"></i> <?= date('Y') ?> <?= Yii::$app->name ?>
                        <span style="margin: 0 8px; opacity: 0.5;">•</span>
                        Powered by <strong>Yii Framework</strong>
                    </p>
                    <p style="font-size: 12px; opacity: 0.7; margin-top: 8px;">
                        Built with <i class="fas fa-heart" style="color: #ef4444;"></i> for modern web forms
                    </p>
                </div>
            </div>
        </div>
    </footer>

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