<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\MasterPage;

class FixCustomHtmlController extends Controller
{
    public function actionIndex($id = null)
    {
        if ($id) {
            $page = MasterPage::findOne((int)$id);
            if (!$page) {
                echo "Page ID {$id} not found\n";
                return;
            }
            
            echo "Page ID {$page->id}\n";
            echo "Title: " . ($page->title ?? 'n/a') . "\n";
            echo "Slug: " . ($page->slug ?? 'n/a') . "\n";
            echo "Layout: " . ($page->layout ?? 'n/a') . "\n";
            echo "custom_html length: " . strlen($page->custom_html ?? '') . "\n";
            echo "layout_json length: " . strlen($page->layout_json ?? '') . "\n";
            
            if (strpos($page->custom_html ?? '', '```html') !== false) {
                echo "\n-> Found ```html in custom_html!\n";
                $newHtml = str_replace('```html', '', $page->custom_html);
                $page->custom_html = $newHtml;
                if ($page->save(false)) {
                    echo "  Fixed!\n";
                }
            }
            
            if (strpos($page->layout_json ?? '', '```html') !== false) {
                echo "\n-> Found ```html in layout_json!\n";
                $newLj = str_replace('```html', '', $page->layout_json);
                $page->layout_json = $newLj;
                if ($page->save(false)) {
                    echo "  Fixed!\n";
                }
            }
            
            return;
        }
        
        echo "Usage: yii fix-custom-html <id>\n";
    }
}