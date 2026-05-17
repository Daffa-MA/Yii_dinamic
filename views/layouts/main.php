<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\components\CommanderAuthContext;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\helpers\Url;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);

$isGuest = Yii::$app->user->isGuest;
$canOpenProjectList = (new CommanderAuthContext())->isSuperAdmin();
$brandUrl = $isGuest ? ['site/index'] : ($canOpenProjectList ? ['project/index'] : ['site/dashboard']);
$footerPrimaryLinks = $isGuest
    ? [
        ['label' => 'Beranda', 'url' => ['site/index']],
        ['label' => 'Tentang', 'url' => ['site/about']],
        ['label' => 'Kontak', 'url' => ['site/contact']],
        ['label' => 'Masuk', 'url' => ['site/login']],
    ]
    : [
        ['label' => 'Dashboard', 'url' => ['site/dashboard']],
        ['label' => 'Forms', 'url' => ['form/index']],
        ['label' => 'Tables', 'url' => ['table-builder/index']],
    ];
if (!$isGuest && $canOpenProjectList) {
    array_unshift($footerPrimaryLinks, ['label' => 'Projects', 'url' => ['project/index']]);
}
$footerWorkspaceLinks = $isGuest
    ? [
        ['label' => 'Keunggulan', 'url' => ['site/about']],
        ['label' => 'Hubungi Tim', 'url' => ['site/contact']],
        ['label' => 'Yii Framework', 'url' => 'https://www.yiiframework.com'],
    ]
    : [
        ['label' => 'Profil', 'url' => ['site/profile']],
        ['label' => 'Data Form', 'url' => ['published-form/index']],
        ['label' => 'Hubungi Tim', 'url' => ['site/contact']],
        ['label' => 'Tentang', 'url' => ['site/about']],
    ];
$footerSupportLinks = [
    ['label' => 'Dokumentasi Yii', 'url' => 'https://www.yiiframework.com/doc/guide/2.0/id'],
    ['label' => 'API Reference', 'url' => 'https://www.yiiframework.com/doc/api/2.0'],
    ['label' => 'Best Practices', 'url' => 'https://www.yiiframework.com/doc/guide/2.0/id/tutorial-core-validators'],
];
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#006c49',
                        tertiary: '#7e3000',
                        'surface-tint': '#4d44e3',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#f9fafb',
                        'surface-container-high': '#f3f4f6',
                        'on-surface': '#0b1c30',
                        'on-surface-variant': '#4a4a6a',
                        outline: '#6b7280',
                        'outline-variant': '#d1d5db',
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: 400;
            font-style: normal;
            font-size: 24px;
            display: inline-flex;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        body {
            background:
                radial-gradient(circle at top, rgba(15, 118, 110, 0.06), transparent 22%),
                linear-gradient(180deg, #f8fafc 0%, #f4f7fb 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

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

        #alert-container .alert {
            animation: slideDown 0.3s ease-out;
            border-left: 4px solid;
            width: fit-content;
            min-width: 280px;
            max-width: 420px;
        }

        body:not(.login-page) #alert-container {
            position: relative;
            z-index: 1;
            margin: 0 0 20px var(--app-sidebar-width, 16rem);
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.login-page #alert-container {
            position: relative;
            z-index: 1;
            margin: 0 0 20px;
        }

        @media (max-width: 768px) {
            body:not(.login-page) #alert-container {
                margin-left: 0;
            }
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
            <div id="alert-container">
                <?= Alert::widget() ?>
            </div>
            <?= $content ?>
        </div>
    </main>

    <footer id="footer" class="site-footer mt-auto">
        <div class="site-footer__inner">
            <div class="site-footer__hero">
                <div class="site-footer__brand-block">
                    <span class="site-footer__eyebrow">Professional Workspace</span>
                    <a href="<?= Url::to($brandUrl) ?>" class="site-footer__brand-link">
                        <span class="site-footer__brand-icon">
                            <span class="material-symbols-outlined">stacks</span>
                        </span>
                        <span>
                            <strong><?= Html::encode(Yii::$app->name) ?></strong>
                            <small>Dynamic form and workspace system</small>
                        </span>
                    </a>
                    <p class="site-footer__description">
                        Workspace untuk project, form, tabel, dan submission dengan tampilan yang lebih clean,
                        struktur yang lebih rapi, dan alur kerja yang lebih profesional.
                    </p>
                </div>

                <div class="site-footer__spotlight">
                    <span class="site-footer__spotlight-tag">
                        <span class="material-symbols-outlined">verified</span>
                        System Ready
                    </span>
                    <h2>Project, form, dan data sekarang terasa satu ekosistem yang utuh.</h2>
                    <p>
                        Gunakan satu workspace untuk memilih project aktif, membangun form, dan menjaga data tetap
                        terisolasi dengan tampilan yang jauh lebih presisi.
                    </p>
                </div>
            </div>

            <div class="row g-4 site-footer__grid">
                <div class="col-12 col-lg-4">
                    <div class="site-footer__card">
                        <h3 class="site-footer__heading">Ringkasan</h3>
                        <p class="site-footer__card-text">
                            Dibangun untuk alur kerja internal yang butuh UI bersih, navigasi cepat, dan struktur data
                            yang tetap mudah dikendalikan.
                        </p>
                        <div class="site-footer__pills">
                            <span class="site-footer__pill">
                                <span class="material-symbols-outlined">database</span>
                                Isolated DB
                            </span>
                            <span class="site-footer__pill">
                                <span class="material-symbols-outlined">dashboard</span>
                                Clean UI
                            </span>
                            <span class="site-footer__pill">
                                <span class="material-symbols-outlined">bolt</span>
                                Fast workflow
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="site-footer__card">
                        <h3 class="site-footer__heading">Navigasi</h3>
                        <ul class="site-footer__links">
                            <?php foreach ($footerPrimaryLinks as $link): ?>
                                <li><?= Html::a(Html::encode($link['label']), $link['url'], ['class' => 'site-footer__link']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="site-footer__card">
                        <h3 class="site-footer__heading">Workspace</h3>
                        <ul class="site-footer__links">
                            <?php foreach ($footerWorkspaceLinks as $link): ?>
                                <?php $isExternal = is_string($link['url']); ?>
                                <li>
                                    <?= Html::a(
                                        Html::encode($link['label']),
                                        $link['url'],
                                        array_filter([
                                            'class' => 'site-footer__link',
                                            'target' => $isExternal ? '_blank' : null,
                                            'rel' => $isExternal ? 'noopener noreferrer' : null,
                                        ])
                                    ) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-lg-3">
                    <div class="site-footer__card">
                        <h3 class="site-footer__heading">Referensi</h3>
                        <ul class="site-footer__links">
                            <?php foreach ($footerSupportLinks as $link): ?>
                                <li>
                                    <?= Html::a(
                                        Html::encode($link['label']),
                                        $link['url'],
                                        [
                                            'class' => 'site-footer__link',
                                            'target' => '_blank',
                                            'rel' => 'noopener noreferrer',
                                        ]
                                    ) ?>
                                </li>
                            <?php endforeach; ?>
                            <li><?= Html::a('Halaman Kontak', ['site/contact'], ['class' => 'site-footer__link']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="site-footer__bottom">
                <p class="site-footer__copyright">
                    &copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?>. All rights reserved.
                </p>
                <div class="site-footer__bottom-links">
                    <?= Html::a('Powered by Yii Framework', 'https://www.yiiframework.com', [
                        'class' => 'site-footer__bottom-link',
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                    ]) ?>
                    <?= Html::a('Tentang Platform', ['site/about'], ['class' => 'site-footer__bottom-link']) ?>
                    <?= Html::a('Kontak', ['site/contact'], ['class' => 'site-footer__bottom-link']) ?>
                </div>
            </div>
        </div>
    </footer>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>
