<?php
/**
 * Script untuk debug menu form_id
 * Jalankan: php yii debug/check-menu
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\MasterMenu;

class DebugController extends Controller
{
    public function actionCheckMenu()
    {
        echo "=== CHECKING MASTER_MENU TABLE ===\n\n";
        
        // 1. Check if form_id column exists
        $db = Yii::$app->db;
        $schema = $db->getTableSchema('master_menu');
        
        echo "Table columns:\n";
        if ($schema) {
            foreach ($schema->columns as $name => $column) {
                echo "  - $name ({$column->type})\n";
            }
        } else {
            echo "  Table schema not found!\n";
        }
        
        echo "\n=== CHECKING ALL MENUS ===\n\n";
        
        // 2. Get all menus and check their form_id
        $menus = MasterMenu::find()->all();
        
        echo "Total menus: " . count($menus) . "\n\n";
        
        foreach ($menus as $menu) {
            echo "Menu ID: {$menu->id}\n";
            echo "  Name: {$menu->name}\n";
            echo "  Type: " . ($menu->type ?? 'NULL') . "\n";
            echo "  page_id: " . ($menu->page_id ?? 'NULL') . "\n";
            echo "  form_id: " . ($menu->form_id ?? 'NULL') . "\n";
            echo "  route: " . ($menu->route ?? 'NULL') . "\n";
            
            // Check if form_id attribute exists
            echo "  Has form_id attr: " . (isset($menu->form_id) ? 'YES' : 'NO') . "\n";
            
            // Get raw data
            $rawData = $menu->getAttributes();
            echo "  Raw form_id from getAttributes: " . ($rawData['form_id'] ?? 'NULL') . "\n";
            
            echo "\n";
        }
        
        echo "\n=== TYPE 'form' MENUS ===\n\n";
        
        $formMenus = MasterMenu::find()->where(['type' => 'form'])->all();
        echo "Total form menus: " . count($formMenus) . "\n\n";
        
        foreach ($formMenus as $menu) {
            echo "Menu ID: {$menu->id}, Name: {$menu->name}, form_id: " . ($menu->form_id ?? 'NULL') . "\n";
        }
        
        echo "\nDone!\n";
    }
    
    public function actionFixFormId()
    {
        echo "=== FIXING FORM_ID COLUMN ===\n\n";
        
        $db = Yii::$app->db;
        $schema = $db->getTableSchema('master_menu');
        
        if (!$schema) {
            echo "Table not found!\n";
            return;
        }
        
        if (!isset($schema->columns['form_id'])) {
            echo "form_id column doesn't exist. Adding it...\n";
            
            try {
                $db->createCommand()
                    ->addColumn('master_menu', 'form_id', 'INTEGER NULL')
                    ->execute();
                echo "form_id column added successfully!\n";
            } catch (\Exception $e) {
                echo "Error adding column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "form_id column exists.\n";
        }
        
        echo "\nDone!\n";
    }
    
    public function actionFixTypeEnum()
    {
        echo "=== FIXING TYPE ENUM ===\n\n";
        
        $db = Yii::$app->db;
        
        // Check current ENUM values
        $schema = $db->getTableSchema('master_menu');
        echo "Current ENUM values: " . implode(', ', $schema->columns['type']->enumValues) . "\n\n";
        
        // Alter table to add 'form', 'button', 'divider' to ENUM
        try {
            $db->createCommand()
                ->alterColumn('master_menu', 'type', "ENUM('group','page','route','form','button','divider') NOT NULL DEFAULT 'page'")
                ->execute();
            echo "Type column ENUM updated successfully!\n";
            
            // Verify
            $schema = $db->getTableSchema('master_menu');
            echo "New ENUM values: " . implode(', ', $schema->columns['type']->enumValues) . "\n";
        } catch (\Exception $e) {
            echo "Error updating ENUM: " . $e->getMessage() . "\n";
        }
        
        echo "\nDone!\n";
    }
    
    public function actionCreateTestForm()
    {
        echo "=== CREATING TEST FORM MENU ===\n\n";
        
        // Get first form
        $forms = \app\models\MasterForm::find()->all();
        if (empty($forms)) {
            echo "No forms found!\n";
            return;
        }
        
        $form = $forms[0];
        echo "Using form: {$form->id} - {$form->form_name}\n\n";
        
        // Create test menu
        $menu = new \app\models\MasterMenu();
        $menu->name = 'Test Form Menu ' . date('Y-m-d H:i:s');
        $menu->type = 'form';
        $menu->form_id = $form->id;
        $menu->is_active = 1;
        $menu->sort_order = 999;
        
        echo "Before save: type={$menu->type}, form_id={$menu->form_id}\n";
        
        if ($menu->save()) {
            echo "SAVED! id={$menu->id}\n";
            
            // Verify
            $saved = \app\models\MasterMenu::findOne($menu->id);
            echo "Verify from DB: type=" . ($saved->type ?? 'NULL') . ", form_id=" . ($saved->form_id ?? 'NULL') . "\n";
        } else {
            echo "FAILED: " . json_encode($menu->getErrors()) . "\n";
        }
    }
}