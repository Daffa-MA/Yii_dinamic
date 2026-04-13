<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;

AppAsset::register($this);

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
        /* Body Styles */
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
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

        /* Alert Styles */
        #alert-container .alert {
            animation: slideDown 0.3s ease-out;
            border-left: 4px solid;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #alert-container .alert-success {
            border-left-color: #198754;
            background: linear-gradient(135deg, #d1f2eb 0%, #a3e4d7 100%);
        }

        #alert-container .alert-danger {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }

        #alert-container .alert-warning {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
        }

        #alert-container .alert-info {
            border-left-color: #0dcaf0;
            background: linear-gradient(135deg, #cff4fc 0%, #b6effb 100%);
        }
    </style>
</head>

<body class="d-flex flex-column h-100<?= $this->context->route === 'site/login' ? ' login-page' : '' ?>">
    <?php $this->beginBody() ?>

    <main id="main" class="flex-shrink-0" role="main">
        <div class="container" style="max-width: 1400px; position: relative;">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            <?php endif ?>
            <div id="alert-container" style="position: sticky; top: 80px; z-index: 1050; margin-bottom: 20px;">
                <?= Alert::widget() ?>
            </div>
            <?= $content ?>
        </div>
    </main>

    <footer id="footer" class="mt-auto">
        <div style="background: linear-gradient(180deg, #ffffff 0%, #e5e9f0 100%);">
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

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.15);
            }
        }
    </style>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>