<?php

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$pages = \app\models\MasterPage::find()
    ->where(['like', 'custom_html', '```html'])
    ->all();

if (empty($pages)) {
    echo "No pages found with ```html\n";
    exit;
}

foreach ($pages as $page) {
    echo "Found page: ID={$page->id}, Name={$page->name}, Slug={$page->slug}\n";

    $oldHtml = $page->custom_html;
    $newHtml = str_replace('```html', '', $oldHtml);

    if ($oldHtml !== $newHtml) {
        $page->custom_html = $newHtml;
        if ($page->save(false)) {
            echo "  -> Fixed! Updated custom_html\n";
        } else {
            echo "  -> ERROR saving: " . print_r($page->errors, true) . "\n";
        }
    } else {
        echo "  -> No change needed\n";
    }
    echo "\n";
}

echo "Done!\n";