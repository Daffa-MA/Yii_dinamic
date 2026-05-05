<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== MENUS WITH PAGE_ID ===\n\n";
$menus = \app\models\MasterMenu::find()
    ->where(['is_active' => 1])
    ->andWhere(['not', ['page_id' => null]])
    ->all();
foreach ($menus as $m) {
    echo "ID: {$m->id} | Name: {$m->name} | Type: {$m->type} | PageID: {$m->page_id}\n";
}

echo "\n=== ALL PAGES ===\n\n";
$pages = \app\models\MasterPage::find()->all();
foreach ($pages as $p) {
    echo "ID: {$p->id} | Title: {$p->title} | Slug: {$p->slug}\n";
}

echo "\n=== PAGE FORMS ===\n\n";
$pageForms = \app\models\PageForms::find()->with('form')->all();
foreach ($pageForms as $pf) {
    $formName = $pf->form ? $pf->form->name : 'N/A';
    echo "PageID: {$pf->page_id} | FormID: {$pf->form_id} | Form: {$formName} | Order: {$pf->order}\n";
}