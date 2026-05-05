<?php
/**
 * Layout Admin dengan Sidebar System (HARDCODED)
 * 
 * UNTUK HALAMAN ADMIN/BUILDER:
 * - Master Menu
 * - Master Page
 * - Master Form
 * 
 * Sidebar TIDAK dari database - HARDCODED
 */

use yii\helpers\Html;

// Konfigurasi halaman
$this->title = 'Admin - ' . ($this->title ?? 'Dashboard');
$content = $content ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f1f5f9; 
        }
        
        /* Layout Admin */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar System (HARDCODED) */
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
            padding: 0;
            flex-shrink: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            padding: 24px;
        }
        
        /* Sidebar Header */
        .admin-sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #334155;
        }
        
        .admin-sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
        }
        
        .admin-sidebar-header p {
            font-size: 12px;
            color: #94a3b8;
        }
        
        /* Sidebar Navigation */
        .admin-sidebar-nav {
            padding: 16px 12px;
        }
        
        .nav-section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            padding: 16px 12px 8px;
            font-weight: 600;
        }
        
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
        }
        
        .admin-nav-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            transform: translateX(4px);
        }
        
        .admin-nav-item.active {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }
        
        .admin-nav-item .material-symbols-outlined {
            font-size: 20px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Logout */
        .admin-sidebar-footer {
            padding: 16px;
            border-top: 1px solid #334155;
            margin-top: auto;
        }
        
        .admin-logout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #fca5a5;
            text-decoration: none;
            border-radius: 10px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
        }
        
        .admin-logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- SIDEBAR SYSTEM (HARDCODED) -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2>System Builder</h2>
                <p>CMS Administration</p>
            </div>
            
            <nav class="admin-sidebar-nav">
                <div class="nav-section-title">Data Management</div>
                
                <!-- MASTER MENU - CRUD untuk tabel master_menu -->
                <a href="<?= \yii\helpers\Url::to(['master-menu/index']) ?>" 
                   class="admin-nav-item <?= strpos(Yii::$app->controller->route, 'master-menu') === 0 ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">list_alt</span>
                    <span>Master Menu</span>
                </a>
                
                <!-- MASTER PAGE - CRUD untuk tabel master_page -->
                <a href="<?= \yii\helpers\Url::to(['master-page/index']) ?>" 
                   class="admin-nav-item <?= strpos(Yii::$app->controller->route, 'master-page') === 0 ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">description</span>
                    <span>Master Page</span>
                </a>
                
                <!-- MASTER FORM - CRUD untuk tabel master_form -->
                <a href="<?= \yii\helpers\Url::to(['master-form/index']) ?>" 
                   class="admin-nav-item <?= strpos(Yii::$app->controller->route, 'master-form') === 0 ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">dynamic_form</span>
                    <span>Master Form</span>
                </a>
                
                <div class="nav-section-title">Utilities</div>
                
                <!-- Table Builder -->
                <a href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>" 
                   class="admin-nav-item <?= strpos(Yii::$app->controller->route, 'table-builder') === 0 ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">table_chart</span>
                    <span>Table Builder</span>
                </a>
                
                <!-- Projects -->
                <a href="<?= \yii\helpers\Url::to(['project/index']) ?>" 
                   class="admin-nav-item <?= strpos(Yii::$app->controller->route, 'project') === 0 ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">folder_open</span>
                    <span>Projects</span>
                </a>
            </nav>
            
            <div class="admin-sidebar-footer">
                <?= Html::beginForm(['/site/logout'], 'post') ?>
                    <button type="submit" class="admin-logout-btn">
                        <span class="material-symbols-outlined">logout</span>
                        <span>Sign Out</span>
                    </button>
                <?= Html::endForm() ?>
            </div>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <?= $content ?>
        </main>
    </div>
</body>
</html>