<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);

$this->title = 'Commander Login';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        surface: '#f7f9fd',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#f2f4f8',
                        'surface-container-high': '#e6e8ec',
                        primary: '#000000',
                        secondary: '#585f6c',
                        'on-surface': '#191c1f',
                        'on-surface-variant': '#444748',
                        'on-primary': '#ffffff',
                        'outline-variant': '#c4c7c7',
                        'on-tertiary-container': '#828485',
                        error: '#ba1a1a',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0px 4px 20px rgba(0,0,0,0.04)',
                    },
                    maxWidth: {
                        container: '440px',
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-surface font-sans text-on-surface antialiased min-h-screen flex flex-col">
<?php $this->beginBody() ?>
    
    <!-- Main Content -->
    <main class="flex-grow flex flex-col items-center justify-center px-4 py-8">
        
        <div class="mb-8 text-center">
            <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-card">
                <span class="material-symbols-outlined text-on-primary text-3xl">lock</span>
            </div>
            <h1 class="text-[32px] font-bold text-on-surface mb-1" style="line-height: 1.2; letter-spacing: -0.02em;">Commander</h1>
            <p class="text-base text-secondary">Masuk ke pusat project dan workspace</p>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 w-full max-w-container shadow-card">
            <?= $content ?>
        </div>
        
    </main>

    <script>
    function togglePassword() {
        var passwordField = document.getElementById('password-field');
        var visibilityIcon = document.getElementById('visibility-icon');
        if (passwordField && visibilityIcon) {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                visibilityIcon.textContent = 'visibility_off';
            } else {
                passwordField.type = 'password';
                visibilityIcon.textContent = 'visibility';
            }
        }
    }
    </script>
    
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
