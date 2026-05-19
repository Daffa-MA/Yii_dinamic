<?php
/* @var $this yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\helpers\Url;

$currentRoute = Yii::$app->controller->route;

function isActive($route) {
    global $currentRoute;
    return strpos($currentRoute, $route) === 0;
}
?>

<!-- ============================================
     SIDEBAR SYSTEM (HARDCODED)
     TIDAK DIAMBIL DARI DATABASE
     UNTUK ADMIN/BUILDER
============================================ -->
<aside class="sidebar-system" style="
    width: 250px;
    background: #1e293b;
    color: #e2e8f0;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px 0;
">
    <!-- Header -->
    <div style="padding: 0 20px 20px; border-bottom: 1px solid #334155;">
        <h3 style="margin: 0; font-size: 18px; color: white;">System Builder</h3>
        <p style="margin: 5px 0 0; font-size: 12px; color: #94a3b8;">CMS Administration</p>
    </div>

    <!-- Navigation -->
    <nav style="padding: 16px 12px;">
        <!-- MASTER MENU -->
        <a href="<?= Url::to(['master-menu/index']) ?>" 
           style="
               display: flex;
               align-items: center;
               gap: 12px;
               padding: 12px 16px;
               color: <?= isActive('master-menu') ? 'white' : '#cbd5e1' ?>;
               text-decoration: none;
               border-radius: 8px;
               margin-bottom: 4px;
               background: <?= isActive('master-menu') ? '#4f46e5' : 'transparent' ?>;
               transition: all 0.2s;
           ">
            <span class="material-symbols-outlined" style="font-size: 20px;">list_alt</span>
            <span>Master Menu</span>
        </a>

        <!-- MASTER PAGE -->
        <a href="<?= Url::to(['master-page/index']) ?>" 
           style="
               display: flex;
               align-items: center;
               gap: 12px;
               padding: 12px 16px;
               color: <?= isActive('master-page') ? 'white' : '#cbd5e1' ?>;
               text-decoration: none;
               border-radius: 8px;
               margin-bottom: 4px;
               background: <?= isActive('master-page') ? '#4f46e5' : 'transparent' ?>;
               transition: all 0.2s;
           ">
            <span class="material-symbols-outlined" style="font-size: 20px;">description</span>
            <span>Master Page</span>
        </a>

        <!-- MASTER FORM -->
        <a href="<?= Url::to(['master-form/index']) ?>" 
           style="
               display: flex;
               align-items: center;
               gap: 12px;
               padding: 12px 16px;
               color: <?= isActive('master-form') ? 'white' : '#cbd5e1' ?>;
               text-decoration: none;
               border-radius: 8px;
               margin-bottom: 4px;
               background: <?= isActive('master-form') ? '#4f46e5' : 'transparent' ?>;
               transition: all 0.2s;
           ">
            <span class="material-symbols-outlined" style="font-size: 20px;">dynamic_form</span>
            <span>Master Form</span>
        </a>

        <!-- MASTER TABLE -->
        <a href="<?= Url::to(['table-builder/index']) ?>" 
           style="
               display: flex;
               align-items: center;
               gap: 12px;
               padding: 12px 16px;
               color: <?= isActive('table-builder') ? 'white' : '#cbd5e1' ?>;
               text-decoration: none;
               border-radius: 8px;
               margin-bottom: 4px;
               background: <?= isActive('table-builder') ? '#4f46e5' : 'transparent' ?>;
               transition: all 0.2s;
           ">
            <span class="material-symbols-outlined" style="font-size: 20px;">table_chart</span>
            <span>Master Table</span>
        </a>
    </nav>

    <!-- Logout -->
    <div style="padding: 16px; border-top: 1px solid #334155; margin-top: auto;">
        <?= $this->render('_logout_button', [
            'url' => ['/site/logout'],
            'label' => 'Sign Out',
            'icon' => 'logout',
            'buttonClass' => '',
            'formStyle' => 'margin:0;',
            'buttonStyle' => '
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                width: 100%;
                color: #fca5a5;
                background: rgba(239, 68, 68, 0.1);
                border: 1px solid rgba(239, 68, 68, 0.2);
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
            ',
        ]) ?>
    </div>
</aside>

<!-- Main Content -->
<main style="margin-left: 250px; padding: 24px;">
    <?= $content ?>
</main>
