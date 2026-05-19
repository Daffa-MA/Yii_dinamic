<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use app\components\DomainContext;

/* @var $project_id int */
/* @var $project array */
/* @var $menuTree array */

$this->title = 'Dashboard - ' . Html::encode($project['name']);

// Pass data to JavaScript
$menuTreeJson = Json::encode($menuTree);
$projectId = $project_id;
$projectListUrl = (new DomainContext())->projectListUrl();
?>

<div class="flex h-screen bg-gray-100" id="main-container">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col" id="sidebar">
        <!-- Project Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white">folder</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="font-bold text-gray-900 truncate"><?= Html::encode($project['name']) ?></h2>
                    <p class="text-xs text-gray-500">Project Dashboard</p>
                </div>
            </div>
        </div>

        <!-- Menu -->
        <nav class="flex-1 overflow-y-auto p-4" id="sidebar-menu">
            <?php if (empty($menuTree)): ?>
                <div class="text-center text-gray-500 py-8">
                    <span class="material-symbols-outlined text-4xl mb-2">menu_open</span>
                    <p class="text-sm">Belum ada menu</p>
                    <p class="text-xs mt-1">Buat menu di Master Menu</p>
                </div>
            <?php else: ?>
                <div class="space-y-1">
                    <?= $this->render('_menu_items', ['items' => $menuTree, 'project_id' => $project_id]) ?>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Footer -->
        <?php if ((new \app\components\CommanderAuthContext())->isSuperAdmin()): ?>
            <div class="p-4 border-t border-gray-200">
                <?= Html::a(
                    '<span class="material-symbols-outlined">arrow_back</span> Kembali ke Projects',
                    $projectListUrl,
                    ['class' => 'flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 no-underline']
                ) ?>
            </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900" id="page-title">
                        Welcome to <?= Html::encode($project['name']) ?>
                    </h1>
                    <p class="text-sm text-gray-500" id="page-description">
                        Klik menu di sidebar untuk menampilkan halaman
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">
                        Project ID: <?= $project_id ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto p-6" id="page-content">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <div class="text-center text-gray-500">
                    <span class="material-symbols-outlined text-6xl mb-4 text-gray-300">touch_app</span>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Pilih Menu</h3>
                    <p class="text-sm">Klik menu di sidebar untuk menampilkan halaman</p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
$handleMenuUrl = Url::to(['/workspace-dashboard/handle-menu']);
$script = <<<JS
var currentProjectId = {$projectId};
var menuTree = {$menuTreeJson};
var handleMenuUrl = '{$handleMenuUrl}';

console.log('Dashboard initialized for project:', currentProjectId);
console.log('Menu tree:', menuTree);

// Function to handle menu click
function handleMenuClick(menuId, menuType, menuName) {
    console.log('Menu clicked:', menuId, menuType, menuName);
    
    // Update header
    document.getElementById('page-title').textContent = menuName;
    document.getElementById('page-description').textContent = 'Memuat...';
    
    if (menuType === 'group') {
        // Group - toggle dropdown (handled by UI)
        return;
    }
    
    if (menuType === 'route') {
        // Route - redirect (handled by link)
        return;
    }
    
    if (menuType === 'page' || menuType === 'form') {
        // Page/Form - resolve via AJAX
        fetch(handleMenuUrl + '?project_id=' + currentProjectId + '&menu_id=' + menuId)
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                handlePageResponse(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Gagal memuat halaman');
            });
    }
}

// Handle page response
function handlePageResponse(response) {
    var content = document.getElementById('page-content');
    var title = document.getElementById('page-title');
    var desc = document.getElementById('page-description');
    
    if (!response.success) {
        // Error case
        content.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-4">' +
            '<div class="flex items-center gap-3">' +
            '<span class="material-symbols-outlined text-red-600">error</span>' +
            '<div>' +
            '<p class="font-semibold text-red-800">' + (response.error || 'Terjadi kesalahan') + '</p>' +
            (response.suggestion ? '<p class="text-sm text-red-600 mt-1">' + response.suggestion + '</p>' : '') +
            '</div>' +
            '</div>' +
            '</div>';
        
        if (response.code === 'PAGE_INACTIVE' && response.activate_url) {
            content.innerHTML += '<div class="mt-4">' +
                '<a href="' + response.activate_url + '" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">' +
                '<span class="material-symbols-outlined">check_circle</span>' +
                'Aktifkan Halaman' +
                '</a>' +
                '</div>';
        }
        
        desc.textContent = response.error || 'Terjadi kesalahan';
        return;
    }
    
    if (response.type === 'route' || response.type === 'form' || response.action === 'redirect') {
        // Redirect
        window.location.href = response.redirect_url;
        return;
    }
    
    if (response.type === 'group') {
        desc.textContent = 'Pilih submenu';
        return;
    }
    
    if (response.type === 'page') {
        // Render page content
        renderPageContent(response);
    }
}

// Render page content
function renderPageContent(data) {
    var content = document.getElementById('page-content');
    var desc = document.getElementById('page-description');
    
    desc.textContent = data.page.description || '';
    
    var html = '';
    
    // Page header
    html += '<div class="mb-6">';
    html += '<h2 class="text-2xl font-bold text-gray-900">' + data.page.title + '</h2>';
    if (data.page.description) {
        html += '<p class="text-gray-600 mt-1">' + data.page.description + '</p>';
    }
    html += '</div>';
    
    // Render based on mode
    if (data.render.mode === 'empty') {
        html += '<div class="bg-amber-50 border border-amber-200 rounded-xl p-4">' +
            '<div class="flex items-center gap-3">' +
            '<span class="material-symbols-outlined text-amber-600">info</span>' +
            '<div>' +
            '<p class="font-semibold text-amber-800">Halaman Kosong</p>' +
            '<p class="text-sm text-amber-700">Belum ada form di halaman ini</p>' +
            '</div>' +
            '</div>' +
            '</div>';
    }
    else if (data.render.mode === 'single') {
        // Single form - render directly
        var form = data.forms[0];
        html += '<div class="bg-white rounded-xl border border-gray-200 p-6">';
        html += '<h3 class="text-lg font-semibold mb-4">' + form.name + '</h3>';
        
        if (form.schema && form.schema.length > 0) {
            form.schema.forEach(function(field) {
                html += renderFormField(field);
            });
        } else {
            html += '<pre class="bg-gray-100 p-4 rounded-lg text-sm overflow-x-auto">' + 
                form.schema_json + '</pre>';
        }
        
        html += '</div>';
    }
    else if (data.render.mode === 'tabs') {
        // Multiple forms - render as tabs
        html += '<div class="tabs-container">';
        
        // Tab buttons
        html += '<div class="flex border-b mb-4">';
        data.render.tabs.forEach(function(tab, index) {
            html += '<button class="tab-btn px-4 py-2 font-medium ' + 
                (index === 0 ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700') + 
                '" data-tab="' + tab.id + '">' + tab.name + '</button>';
        });
        html += '</div>';
        
        // Tab contents
        html += '<div class="tab-contents">';
        data.forms.forEach(function(form, index) {
            html += '<div class="tab-panel ' + (index === 0 ? '' : 'hidden') + '" id="tab-content-' + form.id + '">';
            html += '<div class="bg-white rounded-xl border border-gray-200 p-6">';
            html += '<h3 class="text-lg font-semibold mb-4">' + form.name + '</h3>';
            
            if (form.schema && form.schema.length > 0) {
                form.schema.forEach(function(field) {
                    html += renderFormField(field);
                });
            } else {
                html += '<pre class="bg-gray-100 p-4 rounded-lg text-sm overflow-x-auto">' + 
                    form.schema_json + '</pre>';
            }
            
            html += '</div></div>';
        });
        html += '</div>';
        
        // Add tab switching script
        html += '<script>' +
            'document.querySelectorAll(".tab-btn").forEach(function(btn) {' +
            'btn.addEventListener("click", function() {' +
            'var tabId = this.getAttribute("data-tab");' +
            'document.querySelectorAll(".tab-btn").forEach(function(b) {' +
            'b.classList.remove("text-blue-600", "border-b-2", "border-blue-600");' +
            'b.classList.add("text-gray-500");' +
            '});' +
            'this.classList.add("text-blue-600", "border-b-2", "border-blue-600");' +
            'this.classList.remove("text-gray-500");' +
            'document.querySelectorAll(".tab-panel").forEach(function(p) { p.classList.add("hidden"); });' +
            'document.getElementById("tab-content-" + tabId).classList.remove("hidden");' +
            '});' +
            '});' +
            '<\/script>';
    }
    
    content.innerHTML = html;
}

// Render form field from schema
function renderFormField(field) {
    var html = '<div class="mb-4">';
    html += '<label class="block text-sm font-medium text-gray-700 mb-1">' + 
        (field.label || field.name) + 
        (field.required ? ' <span class="text-red-500">*</span>' : '') + 
        '</label>';
    
    var placeholder = field.placeholder || '';
    var required = field.required ? ' required' : '';
    
    switch (field.type) {
        case 'text-input':
        case 'text':
            html += '<input type="text" name="' + field.name + '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"' + required + '>';
            break;
        case 'number':
            html += '<input type="number" name="' + field.name + '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"' + required + '>';
            break;
        case 'textarea':
            html += '<textarea name="' + field.name + '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"' + required + '></textarea>';
            break;
        default:
            html += '<input type="text" name="' + field.name + '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"' + required + '>';
    }
    
    html += '</div>';
    return html;
}

// Show error message
function showError(message) {
    var content = document.getElementById('page-content');
    content.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-4">' +
        '<div class="flex items-center gap-3">' +
        '<span class="material-symbols-outlined text-red-600">error</span>' +
        '<p class="font-semibold text-red-800">' + message + '</p>' +
        '</div>' +
        '</div>';
}

// Initialize dropdown toggles
document.addEventListener('DOMContentLoaded', function() {
    // Toggle submenu
    document.querySelectorAll('.menu-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var submenu = this.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('hidden');
                var arrow = this.querySelector('.menu-arrow');
                if (arrow) {
                    arrow.classList.toggle('rotate-180');
                }
            }
        });
    });
});
JS;
$this->registerJs($script);
?>
