<?php
use yii\helpers\Html;
use yii\helpers\Url;

$currentRoute = Yii::$app->controller->route;
$isActive = function($route) use ($currentRoute) {
    return strpos($currentRoute, $route) === 0;
};
?>

<aside class="admin-sidebar" style="width:250px;background:#1e293b;color:white;padding:20px;min-height:100vh;">
    <h3 style="margin:0 0 5px;">System Builder</h3>
    <p style="margin:0 0 20px;color:#94a3b8;font-size:12px;">CMS Administration</p>
    
    <ul style="list-style:none;padding:0;margin:0;">
        <li style="margin-bottom:4px;">
            <a href="<?= Url::to(['master-menu/index']) ?>" 
               style="display:block;padding:12px 16px;color:#cbd5e1;text-decoration:none;border-radius:8px;<?= $isActive('master-menu') ? 'background:#4f46e5;color:white;' : '' ?>">
                Master Menu
            </a>
        </li>
        <li style="margin-bottom:4px;">
            <a href="<?= Url::to(['master-page/index']) ?>" 
               style="display:block;padding:12px 16px;color:#cbd5e1;text-decoration:none;border-radius:8px;<?= $isActive('master-page') ? 'background:#4f46e5;color:white;' : '' ?>">
                Master Page
            </a>
        </li>
        <li style="margin-bottom:4px;">
            <a href="<?= Url::to(['master-form/index']) ?>" 
               style="display:block;padding:12px 16px;color:#cbd5e1;text-decoration:none;border-radius:8px;<?= $isActive('master-form') ? 'background:#4f46e5;color:white;' : '' ?>">
                Master Form
            </a>
        </li>
        <li style="margin-bottom:4px;">
            <a href="<?= Url::to(['table-builder/index']) ?>" 
               style="display:block;padding:12px 16px;color:#cbd5e1;text-decoration:none;border-radius:8px;<?= $isActive('table-builder') ? 'background:#4f46e5;color:white;' : '' ?>">
                Master Table
            </a>
        </li>
    </ul>
</aside>