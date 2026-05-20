<?php

use yii\helpers\Url;

/**
 * @var \app\models\MasterPage $model
 * @var array $initialState
 * @var array $forms
 */

$this->title = $model->isNewRecord ? 'Buat Halaman Baru' : 'Edit Halaman: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Halaman', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Register Assets
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_END]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css');
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js', ['position' => \yii\web\View::POS_END]);

$initialState = $initialState ?? [];
$forms = $forms ?? [];
$permissionContext = $permissionContext ?? [];
$canAccessBuilder = (bool)($permissionContext['canAccessBuilder'] ?? true);
$canAccessPalette = (bool)($permissionContext['canAccessPalette'] ?? $canAccessBuilder);
$canAccessTools = (bool)($permissionContext['canAccessTools'] ?? $canAccessBuilder);
$canDragComponents = (bool)($permissionContext['canDragComponents'] ?? $canAccessPalette);
$canAccessActions = (bool)($permissionContext['canAccessActions'] ?? $canAccessBuilder);
$canAccessForms = (bool)($permissionContext['canAccessForms'] ?? $canAccessBuilder);
$canCreatePage = (bool)($permissionContext['canCreatePage'] ?? $canAccessActions);
$canEditPage = (bool)($permissionContext['canEditPage'] ?? $canAccessActions);
?>

<!-- TEMPLATE SELECTOR MODAL -->
<?php if ($model->isNewRecord): ?>
    <style>
        :root {
            --tpl-bg: #ffffff;
            --tpl-bg-secondary: #f5f5f3;
            --tpl-bg-tertiary: #f0efea;
            --tpl-text-primary: #111110;
            --tpl-text-secondary: #6b6b68;
            --tpl-text-tertiary: #9d9d9a;
            --tpl-border: rgba(0, 0, 0, 0.09);
            --tpl-border-md: rgba(0, 0, 0, 0.16);
            --tpl-accent: #534AB7;
            --tpl-accent-light: #EEEDFE;
            --tpl-accent-dark: #3C3489;
            --tpl-radius-md: 8px;
            --tpl-radius-lg: 12px;
            --tpl-radius-xl: 16px;
            --tpl-font: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .template-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: var(--tpl-font);
        }

        .template-modal {
            background: #ffffff;
            border-radius: var(--tpl-radius-xl);
            border: 1px solid #e5e7eb;
            width: 100%;
            max-width: 1040px;
            height: min(720px, 92vh);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .template-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid #334155;
            padding: 0 2rem;
            height: auto;
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            gap: 16px;
        }

        .template-header-left {
            flex: 1;
        }

        .template-header h2 {
            margin: 0;
            color: white;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .template-header p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
        }

        .template-header-right {
            flex-shrink: 0;
        }

        .template-search-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--tpl-radius-md);
            height: 36px;
            width: 260px;
            padding: 0 12px;
            transition: all 0.2s;
        }

        .template-search-wrap:focus-within {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }

        .template-search-wrap i {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .template-search {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: white;
            font: 13px var(--tpl-font);
        }

        .template-search::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .template-filterbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 2rem;
            height: 48px;
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            flex-shrink: 0;
        }

        .template-tab {
            border: 1px solid transparent;
            background: transparent;
            color: #6b7280;
            border-radius: var(--tpl-radius-md);
            cursor: pointer;
            font: 13px var(--tpl-font);
            padding: 8px 14px;
            white-space: nowrap;
            transition: all 0.15s;
            font-weight: 500;
        }

        .template-tab:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .template-tab.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
            font-weight: 600;
        }

        .template-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 6px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            color: #3b82f6;
            margin-left: 6px;
        }

        .template-tab.active .template-tab-count {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .template-tab:not(.active) .template-tab-count {
            background: #f3f4f6;
            color: #6b7280;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 16px;
        }

        .template-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: var(--tpl-radius-lg);
            cursor: pointer;
            overflow: hidden;
            position: relative;
            transition: all 0.2s ease;
        }

        .template-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
            transform: translateY(-4px);
        }

        .template-card.selected {
            border: 2px solid #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .template-preview {
            height: 120px;
            border-bottom: 1px solid #e5e7eb;
            overflow: hidden;
            position: relative;
            background: #f9fafb;
        }

        .template-preview-content {
            width: 100%;
            height: 100%;
            padding: 8px;
            overflow: hidden;
            font-size: 8px;
            line-height: 1.4;
            color: #6b7280;
            transform: scale(0.98);
            transform-origin: top center;
        }

        .template-preview-content h1,
        .template-preview-content h2,
        .template-preview-content h3 {
            color: #0f172a;
            margin: 0 0 4px 0;
            font-weight: 700;
        }

        .template-preview-content p {
            margin: 0;
        }

        .template-info {
            padding: 12px 14px;
            background: #ffffff;
        }

        .template-info h4 {
            margin: 0 0 3px;
            color: #1f2937;
            font-size: 13px;
            font-weight: 600;
        }

        .template-info p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.4;
        }

        .template-card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
        }

        .template-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 11px;
            font-weight: 600;
            border-radius: 999px;
        }

        .template-preview-btn {
            border: 0;
            background: none;
            color: #3b82f6;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            font: 12px var(--tpl-font);
            padding: 0;
            font-weight: 500;
            transition: all 0.2s;
        }

        .template-preview-btn:hover {
            color: #1e40af;
        }

        .template-check {
            display: none;
            position: absolute;
            top: 8px;
            left: 8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #3b82f6;
            color: #fff;
            align-items: center;
            justify-content: center;
            z-index: 2;
            font-size: 14px;
        }

        .template-card.selected .template-check {
            display: flex;
        }

        .template-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 2rem;
        }

        .template-empty-state {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5rem 2rem;
            color: #d1d5db;
            text-align: center;
        }

        .template-empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .template-empty-state p {
            font-size: 14px;
        }

        .template-actions {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 0 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .template-selected-info {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
        }

        .template-selected-info span {
            color: #1f2937;
            font-weight: 700;
        }

        .template-action-buttons {
            display: flex;
            gap: 12px;
        }

        .template-modal .btn-preview,
        .template-modal .btn-save,
        .template-preview-modal .btn-preview,
        .template-preview-modal .btn-save {
            border-radius: var(--tpl-radius-md);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font: 13px var(--tpl-font);
            padding: 8px 16px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .template-modal .btn-preview,
        .template-preview-modal .btn-preview {
            background: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
        }

        .template-modal .btn-preview:hover,
        .template-preview-modal .btn-preview:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: none;
        }

        .template-modal .btn-save,
        .template-preview-modal .btn-save {
            background: #3b82f6;
            border: 0;
            color: #fff;
            font-weight: 600;
        }

        .template-modal .btn-save:hover,
        .template-preview-modal .btn-save:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .template-modal .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .template-modal .btn-save:disabled:hover {
            background: #3b82f6;
            transform: none;
            box-shadow: none;
        }

        .template-preview-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: var(--tpl-font);
        }

        .template-preview-overlay.open {
            display: flex;
        }

        .template-preview-modal {
            background: #ffffff;
            border-radius: var(--tpl-radius-xl);
            border: 1px solid #e5e7eb;
            width: 100%;
            max-width: 960px;
            height: min(620px, 90vh);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .template-preview-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            gap: 16px;
        }

        .template-preview-title {
            color: #1f2937;
            font-size: 16px;
            font-weight: 700;
        }

        .template-preview-desc {
            color: #6b7280;
            font-size: 13px;
            margin-top: 2px;
        }

        .template-close-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--tpl-radius-md);
            border: 1px solid #e5e7eb;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .template-close-btn:hover {
            background: #f9fafb;
            color: #374151;
            border-color: #d1d5db;
        }

        .template-preview-body {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .template-preview-sidebar {
            width: 180px;
            border-right: 1px solid #e5e7eb;
            padding: 16px;
            overflow-y: auto;
            flex-shrink: 0;
            background: #f9fafb;
        }

        .template-sidebar-label {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .template-sec-item {
            padding: 8px 12px;
            border-radius: var(--tpl-radius-md);
            font-size: 13px;
            color: #6b7280;
            cursor: pointer;
            border: 1px solid transparent;
            background: transparent;
            text-align: left;
            width: 100%;
            font-family: var(--tpl-font);
            margin-bottom: 4px;
            transition: all 0.15s;
        }

        .template-sec-item:hover {
            background: #ffffff;
            color: #374151;
        }

        .template-sec-item.active {
            background: #eff6ff;
            color: #1e40af;
            font-weight: 600;
            border-color: #3b82f6;
        }

        .template-preview-frame {
            flex: 1;
            overflow-y: auto;
            background: #ffffff;
            padding: 24px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .template-preview-inner {
            background: #fff;
            border-radius: var(--tpl-radius-lg);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            width: 100%;
            min-height: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .template-preview-inner .template-preview-content {
            padding: 24px;
            transform: none;
            font-size: 13px;
            height: auto;
            min-height: 500px;
            overflow: visible;
        }

        .template-preview-footer {
            padding: 14px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            background: #f9fafb;
        }

        .template-nav-buttons,
        .template-preview-actions {
            display: flex;
            gap: 10px;
        }

        .template-nav-btn {
            padding: 8px 14px;
            border-radius: var(--tpl-radius-md);
            border: 1px solid #d1d5db;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font: 13px var(--tpl-font);
            transition: all 0.15s;
            font-weight: 500;
        }

        .template-nav-btn:hover {
            background: #ffffff;
            color: #374151;
            border-color: #9ca3af;
        }

        @media (max-width: 760px) {

            .template-modal-overlay,
            .template-preview-overlay {
                padding: 1rem;
            }

            .template-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                min-height: auto;
                padding-top: 16px;
                padding-bottom: 16px;
            }

            .template-header-left {
                width: 100%;
            }

            .template-header h2 {
                font-size: 20px;
            }

            .template-search-wrap {
                width: 100%;
            }

            .template-filterbar {
                padding: 0 1rem;
            }

            .template-body {
                padding: 1.5rem;
            }

            .template-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }

            .template-actions,
            .template-preview-footer {
                height: auto;
                gap: 12px;
                padding-top: 12px;
                padding-bottom: 12px;
                align-items: stretch;
                flex-direction: column;
            }

            .template-action-buttons,
            .template-preview-actions {
                justify-content: stretch;
                width: 100%;
            }

            .template-action-buttons button,
            .template-preview-actions button {
                flex: 1;
            }

            .template-preview-sidebar {
                display: none;
            }

            .template-modal {
                max-width: 100%;
                height: auto;
                min-height: 600px;
            }

            .template-preview-modal {
                max-width: 100%;
                height: auto;
                min-height: 500px;
            }
        }
    </style>

    <!-- MODAL (professional template selector) -->
    <div id="templateModal" class="template-modal-overlay open">
        <div class="template-modal">
            <div class="template-header">
                <div class="template-header-left">
                    <h2>Pilih Template Halaman</h2>
                    <p>Pilih template awal atau mulai dari halaman kosong</p>
                </div>
                <div class="template-header-right">
                    <div class="template-search-wrap">
                        <i class="ti ti-search"></i>
                        <input id="templateSearchInput" class="template-search" type="text" placeholder="Cari template..." oninput="filterTemplatesClient()" />
                    </div>
                </div>
            </div>

            <nav class="template-filterbar" id="templateFilterbar"></nav>

            <div class="template-body">
                <div class="template-grid" id="templateGrid"></div>
                <div class="template-empty-state" id="templateEmptyState">
                    <i class="ti ti-search-off"></i>
                    <p>Tidak ada template yang cocok</p>
                </div>
            </div>

            <div class="template-actions">
                <div class="template-selected-info">
                    Template dipilih: <span id="templateSelectedName">-</span>
                </div>
                <div class="template-action-buttons">
                    <?php if ($canCreatePage): ?>
                        <button class="btn-preview" onclick="startBlankTemplate()">
                            <i class="ti ti-file-plus"></i> Mulai Kosong
                        </button>
                    <?php endif; ?>
                    <?php if ($canCreatePage || $canEditPage): ?>
                        <button class="btn-save" onclick="confirmTemplate()" id="templateUseBtn" disabled>
                            <i class="ti ti-check"></i> Pakai Template
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="templatePreviewOverlay" class="template-preview-overlay" onclick="handleTemplatePreviewOverlay(event)">
        <div class="template-preview-modal" onclick="event.stopPropagation()">
            <div class="template-preview-header">
                <div>
                    <div class="template-preview-title" id="templatePreviewTitle">Preview</div>
                    <div class="template-preview-desc" id="templatePreviewDesc"></div>
                </div>
                <button class="template-close-btn" onclick="closeTemplatePreview()" aria-label="Tutup">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="template-preview-body">
                <aside class="template-preview-sidebar">
                    <div class="template-sidebar-label">Bagian Halaman</div>
                    <div id="templatePreviewSections"></div>
                </aside>
                <div class="template-preview-frame">
                    <div class="template-preview-inner" id="templatePreviewInner"></div>
                </div>
            </div>
            <div class="template-preview-footer">
                <div class="template-nav-buttons">
                    <button class="template-nav-btn" onclick="navTemplatePreview(-1)">
                        <i class="ti ti-chevron-left"></i> Sebelumnya
                    </button>
                    <button class="template-nav-btn" onclick="navTemplatePreview(1)">
                        Berikutnya <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                <div class="template-preview-actions">
                    <button class="btn-preview" onclick="closeTemplatePreview()">Tutup</button>
                    <?php if ($canCreatePage || $canEditPage): ?>
                        <button class="btn-save" onclick="selectPreviewTemplate()">
                            <i class="ti ti-check"></i> Pilih Template Ini
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: 400;
        font-style: normal;
        font-size: 24px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-feature-settings: 'liga';
        -webkit-font-smoothing: antialiased;
        font-feature-settings: 'liga';
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    /* Button URL Validation Styles */
    .text-red-500 { color: #ef4444 !important; }
    .text-gray-500 { color: #6b7280 !important; }
    .text-green-500 { color: #22c55e !important; }
    .border-red-500 { border-color: #ef4444 !important; }
    .prop-input.url-warning { 
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }
    .prop-input.url-valid { 
        border-color: #22c55e !important;
        background-color: #f0fdf4 !important;
    }

    /* Custom Warning Modal */
    .warning-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
    }
    .warning-modal-overlay.open {
        opacity: 1;
        visibility: visible;
    }
    .warning-modal {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(0.95);
        transition: transform 0.2s ease;
    }
    .warning-modal-overlay.open .warning-modal {
        transform: scale(1);
    }
    .warning-modal-header {
        padding: 24px 24px 0;
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    .warning-modal-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #fef3c7;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .warning-modal-icon svg {
        width: 24px;
        height: 24px;
        color: #f59e0b;
    }
    .warning-modal-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 4px;
    }
    .warning-modal-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
    }
    .warning-modal-body {
        padding: 16px 24px;
    }
    .warning-modal-list {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 12px 16px;
        max-height: 200px;
        overflow-y: auto;
    }
    .warning-modal-list-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        border-bottom: 1px solid #fecaca;
        font-size: 14px;
        color: #991b1b;
    }
    .warning-modal-list-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .warning-modal-list-item::before {
        content: "⚠️";
        font-size: 12px;
    }
    .warning-modal-footer {
        padding: 0 24px 24px;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    .warning-modal-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.15s ease;
    }
    .warning-modal-btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    .warning-modal-btn-cancel:hover {
        background: #e5e7eb;
    }
    .warning-modal-btn-proceed {
        background: #6366f1;
        color: white;
    }
    .warning-modal-btn-proceed:hover {
        background: #4f46e5;
    }

    .page-builder {
        height: calc(100vh - 56px);
        display: flex;
        background: #0f172a;
        overflow: hidden;
    }

    .builder-sidebar-left {
        width: 280px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        border-right: 1px solid #e5e7eb;
        overflow-y: auto;
    }

    .builder-canvas {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f1f5f9;
        min-width: 0;
        min-height: 0;
        overflow: hidden;
    }

    .canvas-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        flex-shrink: 0;
    }

    .canvas-toolbar-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .canvas-toolbar-right {
        font-size: 12px;
    }

    .canvas-device-switcher {
        display: flex;
        align-items: center;
        gap: 4px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 4px;
        border-radius: 8px;
    }

    .device-btn {
        border: none;
        background: transparent;
        color: #64748b;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .device-btn:hover {
        background: #e2e8f0;
        color: #475569;
    }

    .device-btn.active {
        background: #ffffff;
        color: #3b82f6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .device-btn .material-symbols-outlined {
        font-size: 18px;
    }

    .canvas-wrapper {
        flex: 1;
        display: flex;
        justify-content: center;
        padding: 20px;
        min-height: 0;
        overflow-x: auto;
        overflow-y: auto;
        background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .canvas-wrapper::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .canvas-wrapper::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 999px;
    }

    .canvas-wrapper::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
        border: 2px solid #e2e8f0;
    }

    .canvas-wrapper::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .canvas-wrapper.component-scroll-mode {
        padding: 24px 28px;
    }

    .canvas-wrapper.page-scroll-mode {
        padding: 20px;
    }

    .canvas-frame {
        width: 100%;
        max-width: 100%;
        min-height: 600px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        transition: width 0.25s ease, max-width 0.25s ease;
        overflow: hidden;
    }

    .canvas-frame.device-tablet {
        width: 820px;
        max-width: 820px;
    }

    .canvas-frame.device-mobile {
        width: 390px;
        max-width: 390px;
    }

    .canvas-content {
        min-height: calc(100vh - 180px);
        padding: 20px;
        background: #ffffff;
    }

    .canvas-frame.component-scroll-mode {
        height: calc(100vh - 170px);
        min-height: calc(100vh - 170px);
        display: flex;
        flex-direction: column;
    }

    .canvas-frame.component-scroll-mode .canvas-content {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .canvas-frame.component-scroll-mode .canvas-content::-webkit-scrollbar {
        width: 10px;
    }

    .canvas-frame.component-scroll-mode .canvas-content::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 999px;
    }

    .canvas-frame.component-scroll-mode .canvas-content::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
        border: 2px solid #f1f5f9;
    }

    .canvas-frame.component-scroll-mode .canvas-content::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .builder-properties {
        width: 380px;
        background: #ffffff;
        border-left: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .builder-properties::-webkit-scrollbar {
        width: 6px;
    }

    .builder-properties::-webkit-scrollbar-track {
        background: #f9fafb;
    }

    .builder-properties::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .builder-properties::-webkit-scrollbar-thumb:hover {
        background: #94a3af;
    }

    .component-item {
        padding: 14px 16px;
        margin: 4px 8px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #f9fafb;
        cursor: grab;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #374151;
    }

    .component-item:hover {
        background: #f3f4f6;
        border-color: #3b82f6;
        transform: translateX(4px);
    }

    .component-item:active {
        cursor: grabbing;
    }

    .component-item .material-symbols-outlined {
        font-size: 22px;
        color: #6366f1;
    }

    .component-item span:last-child {
        font-size: 14px;
        font-weight: 500;
    }

    .canvas-drop-zone {
        min-height: calc(100vh - 120px);
        padding: 16px;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        border-radius: 12px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .builder-workspace {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .builder-workspace-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: rgba(255, 255, 255, 0.92);
    }

    .builder-workspace-hint {
        font-size: 12px;
        color: #64748b;
        text-align: right;
    }

    .builder-canvas-surface {
        min-height: 360px;
        padding: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .preview-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }

    .preview-toolbar-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #1f2937;
    }

    .preview-device-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        padding: 4px;
        border-radius: 999px;
    }

    .preview-device-btn {
        border: 0;
        background: transparent;
        color: #64748b;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .preview-device-btn:hover {
        background: rgba(255, 255, 255, 0.8);
        color: #1d4ed8;
    }

    .preview-device-btn.active {
        background: #ffffff;
        color: #1d4ed8;
        box-shadow: 0 1px 4px rgba(37, 99, 235, 0.15);
    }

    .preview-device-btn .material-symbols-outlined {
        font-size: 18px;
    }

    .preview-stage {
        flex: 1;
        min-height: 0;
        overflow: auto;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 12px;
    }

    .live-preview-frame {
        width: 100%;
        min-height: calc(100vh - 210px);
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        transition: width 0.2s ease, max-width 0.2s ease;
    }

    .live-preview-frame.preview-tablet {
        width: min(100%, 820px);
    }

    .live-preview-frame.preview-mobile {
        width: min(100%, 390px);
    }

    .live-preview-frame iframe {
        width: 100%;
        min-height: calc(100vh - 210px);
        border: 0;
        display: block;
        background: white;
    }

    .builder-block {
        position: relative;
        margin-bottom: 8px;
        border: 2px solid transparent;
        border-radius: 8px;
        transition: all 0.15s;
        background: white;
    }

    .builder-block:hover {
        border-color: #6366f1;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    }

    .builder-block.selected {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .builder-block .block-actions {
        position: absolute;
        top: -12px;
        right: -12px;
        display: none;
        gap: 6px;
        z-index: 10;
    }

    .builder-block:hover .block-actions {
        display: flex;
    }

    .btn-save:disabled {
        opacity: 0.38;
        cursor: not-allowed;
    }

    .block-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f3f4f6;
        border: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s;
    }

    .block-action-btn:hover {
        background: #f9fafb;
        border-color: #3b82f6;
    }

    .block-action-btn.delete:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: white;
    }

    /* ============ PROPERTIES PANEL STYLES ============ */
    .prop-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1f2937;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .prop-header .material-symbols-outlined {
        font-size: 18px;
        color: #3b82f6;
    }

    .prop-section {
        padding: 16px 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    .prop-section-title {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        padding: 0 8px;
    }

    .prop-group {
        margin-bottom: 16px;
    }

    .prop-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .code-editor-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        padding: 10px 15px;
        background: #1e293b;
        border-bottom: 1px solid #334155;
    }

    .code-scope-buttons {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .code-scope-btn {
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #475569;
        background: transparent;
        color: #94a3b8;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s;
    }

    .code-scope-btn:hover {
        background: #334155;
        color: #ffffff;
    }

    .code-scope-btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #ffffff;
    }

    .code-editor-tools {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .code-lang-buttons {
        display: flex;
        gap: 4px;
    }

    .code-mode-hint {
        padding: 8px 12px;
        background: #0f172a;
        color: #cbd5e1;
        border-bottom: 1px solid #334155;
        font-size: 11px;
        line-height: 1.5;
    }

    .is-hidden {
        display: none !important;
    }

    .code-lang-btn {
        padding: 5px 12px;
        border-radius: 4px;
        border: 1px solid #475569;
        background: transparent;
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }

    .code-lang-btn:hover {
        background: #334155;
        color: white;
    }

    .code-lang-btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .btn-reset-base {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: 4px;
        border: 1px solid #475569;
        background: transparent;
        color: #94a3b8;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-reset-base:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: white;
    }

    .prop-group small {
        display: block;
        font-size: 10px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .prop-input,
    .prop-select,
    .prop-textarea {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 13px;
        background: #f9fafb;
        color: #374151;
        transition: all 0.2s;
    }

    .prop-input:focus,
    .prop-select:focus,
    .prop-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: #ffffff;
    }

    .prop-textarea {
        resize: vertical;
        font-family: inherit;
        min-height: 60px;
    }

    .prop-checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }

    .prop-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #3b82f6;
        cursor: pointer;
    }

    .prop-color-picker {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .prop-color-input {
        width: 50px;
        height: 36px;
        padding: 4px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        cursor: pointer;
        background: transparent;
    }

    .prop-color-value {
        flex: 1;
        padding: 8px 10px;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        color: #374151;
        font-size: 12px;
        font-family: monospace;
    }

    .prop-slider {
        width: 100%;
        height: 6px;
        accent-color: #3b82f6;
    }

    .prop-slider-value {
        display: inline-block;
        background: #3b82f6;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }

    .prop-option-group {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .prop-option-btn {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        background: #f9fafb;
        color: #6b7280;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }

    .prop-option-btn:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .prop-option-btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .prop-spacing-input {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .prop-spacing-input input {
        padding: 8px 10px;
        border: 1px solid #d1d5db;
        background: #f9fafb;
        color: #374151;
        border-radius: 6px;
        font-size: 12px;
    }

    .no-selection {
        padding: 40px 20px;
        text-align: center;
        color: #94a3b8;
    }

    .no-selection .material-symbols-outlined {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .builder-toolbar {
        height: 56px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
    }

    .builder-toolbar-title {
        font-weight: 700;
        color: #1f2937;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .builder-toolbar-title .material-symbols-outlined {
        color: #3b82f6;
    }

    .builder-toolbar-actions {
        display: flex;
        gap: 12px;
    }

    .btn-save {
        padding: 10px 24px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s;
    }

    .btn-save:hover {
        background: #1e40af;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-preview {
        padding: 10px 24px;
        background: #f9fafb;
        color: #6b7280;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-preview:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .save-dialog-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 12000;
        padding: 16px;
    }

    .save-dialog-overlay.open {
        display: flex;
    }

    .save-dialog {
        width: min(520px, 100%);
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 30px 80px rgba(15, 23, 42, 0.28);
        overflow: hidden;
    }

    .save-dialog-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .save-dialog-title {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }

    .save-dialog-body {
        padding: 18px;
    }

    .save-dialog-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 8px;
    }

    .save-dialog-input {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        color: #0f172a;
        background: #ffffff;
    }

    .save-dialog-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .save-dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 18px 18px;
    }

    .save-dialog-btn {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 9px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        background: #ffffff;
        color: #334155;
    }

    .save-dialog-btn:hover {
        background: #f8fafc;
    }

    .save-dialog-btn.primary {
        border-color: #2563eb;
        background: #2563eb;
        color: #ffffff;
    }

    .save-dialog-btn.primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .block-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 700;
        color: #1f2937;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .component-section-title {
        padding: 16px 20px 8px;
        font-size: 10px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    #properties-panel {
        display: flex;
        flex-direction: column;
        gap: 0;
        min-height: 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    #properties-panel>* {
        flex-shrink: 0;
    }
</style>

<!-- BUILDER INTERFACE -->
<?php if (!$canAccessBuilder): ?>
    <div style="max-width:720px;margin:4rem auto;padding:2rem;border-radius:24px;background:#fff;border:1px solid rgba(148,163,184,.18);box-shadow:0 18px 40px rgba(15,23,42,.06);">
        <h2 style="margin:0 0 .5rem;font-size:1.5rem;font-weight:900;color:#0f172a;">Builder tidak tersedia</h2>
        <p style="margin:0;color:#64748b;line-height:1.7;">Role Anda tidak memiliki akses ke builder. Menu, toolbar, dan palette disembunyikan total.</p>
    </div>
<?php else: ?>
<div class="page-builder" id="builderInterface" style="<?= ($model->isNewRecord && empty($initialState)) ? 'display:none;' : '' ?>">
    <!-- LEFT PANEL: Component Library -->
    <?php if ($canAccessPalette): ?>
    <div class="builder-sidebar-left">
        <div class="sidebar-header">
            <span class="material-symbols-outlined" style="font-size:20px;vertical-align:middle;margin-right:8px">widgets</span>
            Components
        </div>

        <div class="component-section-title">Layout</div>
        <div class="component-item" data-type="section">
            <span class="material-symbols-outlined">view_stream</span>
            <span>Section</span>
        </div>
        <div class="component-item" data-type="row">
            <span class="material-symbols-outlined">view_column</span>
            <span>Row</span>
        </div>
        <div class="component-item" data-type="grid">
            <span class="material-symbols-outlined">grid_view</span>
            <span>Grid</span>
        </div>

        <div class="component-section-title">Content</div>
        <div class="component-item" data-type="heading">
            <span class="material-symbols-outlined">title</span>
            <span>Heading</span>
        </div>
        <div class="component-item" data-type="text">
            <span class="material-symbols-outlined">notes</span>
            <span>Text Block</span>
        </div>
        <div class="component-item" data-type="image">
            <span class="material-symbols-outlined">image</span>
            <span>Image</span>
        </div>
        <div class="component-item" data-type="button">
            <span class="material-symbols-outlined">smart_button</span>
            <span>Button</span>
        </div>
        <div class="component-item" data-type="divider">
            <span class="material-symbols-outlined">horizontal_rule</span>
            <span>Divider</span>
        </div>

        <div class="component-section-title">Advanced</div>
        <?php if ($canAccessForms): ?>
        <div class="component-item" data-type="form">
            <span class="material-symbols-outlined">dynamic_form</span>
            <span>Form Builder</span>
        </div>
        <?php endif; ?>
        <div class="component-item" data-type="card">
            <span class="material-symbols-outlined">square</span>
            <span>Card</span>
        </div>
        <div class="component-item" data-type="video">
            <span class="material-symbols-outlined">videocam</span>
            <span>Video</span>
        </div>
        <div class="component-item" data-type="spacer">
            <span class="material-symbols-outlined">space_bar</span>
            <span>Spacer</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- CANVAS: Main Area with Unified Responsive -->
    <div class="builder-canvas">
        <!-- Responsive Switcher - DI ATAS canvas -->
        <?php if ($canAccessTools): ?>
        <div class="canvas-toolbar">
            <div class="canvas-toolbar-left">
                <span class="material-symbols-outlined" style="color:#6366f1">dashboard</span>
                <span style="font-weight:600;color:#1e2937;font-size:14px;">Canvas Builder</span>
            </div>
            <div class="canvas-device-switcher">
                <button type="button" class="device-btn active" data-device="desktop" onclick="setDevice('desktop')" title="Desktop">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h6v2H8v2h8v-2h-2v-2h6c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H3V4h18v12z" />
                    </svg>
                </button>
                <button type="button" class="device-btn" data-device="tablet" onclick="setDevice('tablet')" title="Tablet">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H3V6h18v12zM7 20h10v-2H7v2z" />
                    </svg>
                </button>
                <button type="button" class="device-btn" data-device="mobile" onclick="setDevice('mobile')" title="Mobile">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V3h10v16z" />
                    </svg>
                </button>
            </div>
            <div class="canvas-toolbar-right">
                <span style="color:#94a3b8;font-size:12px;">Drop komponen untuk membangun halaman</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Canvas Wrapper with Responsive Frame -->
        <div class="canvas-wrapper">
            <div id="main-canvas-frame" class="canvas-frame device-desktop">
                <div id="canvas" class="canvas-content"></div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: Properties -->
    <div class="builder-properties">
        <div class="prop-tabs" style="display: flex; background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
            <button class="prop-tab-btn active" data-tab="design" style="flex: 1; padding: 12px; border: none; background: none; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid #3b82f6;">Design</button>
            <?php if ($canAccessTools): ?>
                <button class="prop-tab-btn" data-tab="code" style="flex: 1; padding: 12px; border: none; background: none; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent;">Custom Code</button>
            <?php endif; ?>
        </div>

        <div id="properties-design-tab" class="prop-tab-content active">
            <div id="properties-panel">
                <div class="no-selection">
                    <span class="material-symbols-outlined">touch_app</span>
                    <p style="font-size:14px">Pilih komponen untuk edit</p>
                </div>
            </div>
        </div>

        <?php if ($canAccessTools): ?>
        <div id="properties-code-tab" class="prop-tab-content" style="display: none; flex: 1; flex-direction: column;">
            <div class="code-editor-header">
                <div class="code-scope-buttons">
                    <button type="button" class="code-scope-btn active" data-scope="component" onclick="setCodeScope('component')">Component Code</button>
                    <button type="button" class="code-scope-btn" data-scope="page" onclick="setCodeScope('page')">Page Source</button>
                </div>
                <?php if ($canAccessTools): ?>
                <div class="code-editor-tools" id="component-code-tools">
                    <div class="code-lang-buttons">
                        <button type="button" class="code-lang-btn active" data-lang="html" onclick="switchCodeLang('html')">HTML</button>
                        <button type="button" class="code-lang-btn" data-lang="css" onclick="switchCodeLang('css')">CSS</button>
                        <button type="button" class="code-lang-btn" data-lang="js" onclick="switchCodeLang('js')">JS</button>
                    </div>
                    <button type="button" class="btn-reset-base" onclick="resetBaseCode()">
                        <span class="material-symbols-outlined" style="font-size:14px">refresh</span>
                        Reset Base
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div id="code-mode-hint" class="code-mode-hint">Edit custom code untuk komponen yang dipilih (HTML/CSS/JS terpisah).</div>
            <div id="monaco-editor-container" style="flex: 1; min-height: 400px; background: #1e1e1e;"></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
    // Ensure template modal handlers exist before inline onclick attributes execute.
    // This avoids runtime errors like: "confirmTemplate is not defined".
    window.builderPermissionContext = <?= json_encode([
        'canAccessBuilder' => $canAccessBuilder,
        'canAccessPalette' => $canAccessPalette,
        'canAccessTools' => $canAccessTools,
        'canDragComponents' => $canDragComponents,
        'canAccessActions' => $canAccessActions,
        'canAccessForms' => $canAccessForms,
        'canCreatePage' => $canCreatePage,
        'canEditPage' => $canEditPage,
    ]) ?>;

    function selectTemplate(id) {
        selectedTemplateId = id;
        renderTemplates();
    }

    function confirmTemplate() {
        let newState = [];
        if (selectedTemplateId && selectedTemplateId !== 'blank') {
            const template = templates.find(t => t.id === selectedTemplateId);
            if (template) newState = JSON.parse(JSON.stringify(template.state));
        }
        window.pageState = newState;

        const modal = document.getElementById('templateModal');
        if (modal) modal.remove();
        const builder = document.getElementById('builderInterface');
        if (builder) builder.style.display = 'flex';

        setTimeout(() => {
            renderBuilder(window.pageState);
            if (window.pageState && window.pageState.length > 0) {
                selectBlock(window.pageState[window.pageState.length - 1].id);
            } else {
                renderProperties(null);
            }
        }, 0);
    }

    const COMPONENT_DEFAULTS = {
        heading: {
            level: 'h1',
            text: 'Judul Heading',
            align: 'left',
            fontSize: '32',
            color: '#1e293b'
        },
        text: {
            content: 'Teks paragraf Anda di sini',
            fontSize: '15',
            lineHeight: '1.6',
            color: '#475569'
        },
        image: {
            src: '',
            alt: 'Image',
            width: '100',
            height: 'auto',
            align: 'center',
            borderRadius: '8'
        },
        button: {
            text: 'Klik Saya',
            url: '',
            style: 'primary',
            size: 'md',
            align: 'center',
            fullWidth: false
        },
        form: {
            formId: null,
            showTitle: true,
            fields: []
        },
        card: {
            title: 'Card Title',
            content: 'Konten card',
            showShadow: true,
            bgColor: '#ffffff',
            padding: '20'
        },
        spacer: {
            height: '32'
        },
        divider: {
            color: '#e2e8f0',
            thickness: '2',
            margin: '16'
        },
        video: {
            url: '',
            width: '100',
            aspectRatio: '16/9'
        },
        grid: {
            columns: '3',
            gap: '16',
            padding: '20'
        },
        section: {
            background: '#ffffff',
            padding: '40',
            margin: '0'
        },
        row: {
            gap: '16',
            padding: '0'
        }
    };

    // Base Code Templates per Component Type
    const COMPONENT_BASE_CODE = {
        heading: {
            html: '<h1 class="heading-{id}">{text}</h1>',
            css: '.heading-{id} {\n  font-size: 32px;\n  font-weight: 700;\n  color: #1e293b;\n  margin: 0 0 16px;\n  line-height: 1.2;\n}',
            js: ''
        },
        text: {
            html: '<p class="text-{id}">{content}</p>',
            css: '.text-{id} {\n  font-size: 16px;\n  line-height: 1.6;\n  color: #475569;\n  margin: 0;\n}',
            js: ''
        },
        image: {
            html: '<img class="image-{id}" src="{src}" alt="{alt}" />',
            css: '.image-{id} {\n  max-width: 100%;\n  height: auto;\n  border-radius: 8px;\n  display: block;\n}',
            js: ''
        },
        button: {
            html: '<a href="{url}" class="btn-{id}">{text}</a>',
            css: '.btn-{id} {\n  display: inline-block;\n  padding: 12px 24px;\n  background: #6366f1;\n  color: white;\n  text-decoration: none;\n  border-radius: 8px;\n  font-weight: 600;\n  transition: all 0.2s;\n}\n.btn-{id}:hover {\n  background: #4f46e5;\n  transform: translateY(-2px);\n}',
            js: ''
        },
        card: {
            html: '<div class="card-{id}">\n  <h3 class="card-title">{title}</h3>\n  <p class="card-content">{content}</p>\n</div>',
            css: '.card-{id} {\n  padding: 24px;\n  background: white;\n  border-radius: 12px;\n  box-shadow: 0 4px 12px rgba(0,0,0,0.08);\n}\n.card-title {\n  margin: 0 0 8px;\n  font-size: 18px;\n  font-weight: 600;\n  color: #1e293b;\n}\n.card-content {\n  margin: 0;\n  color: #64748b;\n  font-size: 14px;\n}',
            js: ''
        },
        form: {
            html: '<form class="form-{id}" method="post" action="{action}">\n  <div class="form-fields"></div>\n</form>',
            css: '.form-{id} {\n  padding: 24px;\n  background: #f8fafc;\n  border-radius: 12px;\n}\n.form-fields {\n  display: flex;\n  flex-direction: column;\n  gap: 16px;\n}',
            js: ''
        },
        grid: {
            html: '<div class="grid-{id}">\n  <div class="grid-item">Kolom 1</div>\n  <div class="grid-item">Kolom 2</div>\n  <div class="grid-item">Kolom 3</div>\n</div>',
            css: '.grid-{id} {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 16px;\n  padding: 20px;\n}\n.grid-item {\n  padding: 20px;\n  background: white;\n  border-radius: 8px;\n  text-align: center;\n}',
            js: ''
        },
        row: {
            html: '<div class="row-{id}">\n  <div class="row-item">Item 1</div>\n  <div class="row-item">Item 2</div>\n</div>',
            css: '.row-{id} {\n  display: flex;\n  gap: 16px;\n  align-items: center;\n}\n.row-item {\n  flex: 1;\n}',
            js: ''
        },
        section: {
            html: '<section class="section-{id}">\n  <div class="section-content"></div>\n</section>',
            css: '.section-{id} {\n  padding: 60px 20px;\n  background: white;\n}\n.section-content {\n  max-width: 1200px;\n  margin: 0 auto;\n}',
            js: ''
        },
        spacer: {
            html: '<div class="spacer-{id}"></div>',
            css: '.spacer-{id} {\n  height: 32px;\n}',
            js: ''
        },
        divider: {
            html: '<hr class="divider-{id}" />',
            css: '.divider-{id} {\n  border: none;\n  border-top: 2px solid #e2e8f0;\n  margin: 16px 0;\n}',
            js: ''
        },
        video: {
            html: '<div class="video-{id}">\n  <iframe src="{url}" frameborder="0" allowfullscreen></iframe>\n</div>',
            css: '.video-{id} {\n  position: relative;\n  padding-bottom: 56.25%;\n  height: 0;\n}\n.video-{id} iframe {\n  position: absolute;\n  top: 0;\n  left: 0;\n  width: 100%;\n  height: 100%;\n}',
            js: ''
        }
    };

    window.pageState = <?= json_encode($initialState) ?>;
    window.availableForms = <?= json_encode($forms ?? []) ?>;
    window.dynamicFormPreviewEndpoint = <?= json_encode(Url::to(['master-page/form-preview'])) ?>;
    window.dynamicFormPreviewCache = {};
    window.dynamicFormPreviewPending = {};
    window.workspacePages = [];
    window.workspacePagesLoaded = false;
    window.workspacePagesLoading = false;
    let selectedBlockId = null;
    let isAddingBlock = false;
    const PAGE_TYPE_BUILDER = 'builder';
    const PAGE_TYPE_CUSTOM_CODE = 'custom_code';
    const initialPageTypeValue = <?= json_encode((!empty($model->use_page_custom_code) ? 'custom_code' : (string) ($model->page_type ?? 'builder'))) ?>;
    const initialCustomHtml = <?= json_encode((string) (($model->page_custom_html ?? '') !== '' ? $model->page_custom_html : ($model->custom_html ?? ''))) ?>;
    const initialCustomCss = <?= json_encode((string) (($model->page_custom_css ?? '') !== '' ? $model->page_custom_css : ($model->custom_css ?? ''))) ?>;
    const initialCustomJs = <?= json_encode((string) (($model->page_custom_js ?? '') !== '' ? $model->page_custom_js : ($model->custom_js ?? ''))) ?>;
    const hasInitialFullPageSource =
        (initialCustomHtml || '').trim() !== '' ||
        (initialCustomCss || '').trim() !== '' ||
        (initialCustomJs || '').trim() !== '';
    let activeCodeScope = initialPageTypeValue === PAGE_TYPE_CUSTOM_CODE ? 'page' : 'component';
    let fullPageSource = '';
    let fullPageSourceDerivedFromBuilder = !hasInitialFullPageSource;

    function normalizeButtonLinkData(blocks) {
        (blocks || []).forEach(block => {
            if (!block || block.type !== 'button') {
                if (Array.isArray(block?.children)) {
                    normalizeButtonLinkData(block.children);
                }
                return;
            }

            block.props = block.props || {};
            if (block.props.url === '#') {
                block.props.url = '';
            }
            if (block.props.linkMode === 'none' || block.props.linkMode === 'ui' || block.props.uiOnly === true) {
                block.props.linkMode = 'ui_only';
                block.props.url = '';
                block.props.pageId = '';
                block.props.pageSlug = '';
                block.props.uiOnly = true;
            }
            if (block.props.pageId && !block.props.linkMode) {
                block.props.linkMode = 'page';
            }
            if (block.props.linkMode === 'page' && !block.props.pageId) {
                block.props.linkMode = 'manual';
            }
        });
    }

    normalizeButtonLinkData(window.pageState);

    function generateId() {
        return 'block-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    function renderBuilder(state) {
        const canvas = document.getElementById('canvas');
        canvas.innerHTML = '';

        if (activeCodeScope === 'page') {
            renderFullPageSourceCanvas();
            return;
        }

        if (state.length === 0) {
            canvas.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 60px 20px;"><span style="font-size:48px;display:block;margin-bottom:16px">🎨</span>Drag komponen dari panel kiri ke sini</p>';
            return;
        }

        state.forEach(block => {
            const el = createBlockElement(block);
            canvas.appendChild(el);
        });

        if (window.sortableInstance) {
            window.sortableInstance.destroy();
            window.sortableInstance = null;
        }

        if (window.builderPermissionContext?.canDragComponents) {
            window.sortableInstance = new window.Sortable(canvas, {
                animation: 150,
                handle: '.block-action-btn.move',
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    const item = window.pageState.splice(evt.oldIndex, 1)[0];
                    window.pageState.splice(evt.newIndex, 0, item);
                    renderBuilder(window.pageState);
                }
            });
        }

        scheduleLivePreviewUpdate();
        scheduleFullPageSourceSyncFromBuilder();
    }

    function createBlockElement(block) {
        const div = document.createElement('div');
        div.className = 'builder-block';
        div.dataset.id = block.id;
        div.dataset.type = block.type;
        if (block.id === selectedBlockId) div.classList.add('selected');

        const canAccessActions = !!window.builderPermissionContext?.canAccessActions;
        const canDragComponents = !!window.builderPermissionContext?.canDragComponents;
        const moveButton = canDragComponents
            ? '<button class="block-action-btn move" title="Move"><span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8">drag_indicator</span></button>'
            : '';
        const actionButtons = canAccessActions
            ? `${moveButton}<button class="block-action-btn duplicate" title="Duplicate"><span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8">content_copy</span></button><button class="block-action-btn delete" title="Delete"><span class="material-symbols-outlined" style="font-size:16px">delete</span></button>`
            : '';

        div.innerHTML = `
        ${canAccessActions ? `<div class="block-actions">${actionButtons}</div>` : ''}
        <div class="block-content" onclick="selectBlock('${block.id}')">
            ${renderBlockContent(block)}
        </div>
    `;

        const deleteButton = div.querySelector('.delete');
        if (deleteButton) {
            deleteButton.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteBlock(block.id);
            });
        }

        const duplicateButton = div.querySelector('.duplicate');
        if (duplicateButton) {
            duplicateButton.addEventListener('click', (e) => {
                e.stopPropagation();
                duplicateBlock(block);
            });
        }

        return div;
    }

    function renderBlockContent(block) {
        const props = block.props || {};
        switch (block.type) {
            case 'heading':
                const tag = props.level || 'h2';
                return `<${tag} style="text-align:${props.align || 'left'};margin:0;padding:12px;font-size:${props.fontSize || '24'}px;font-weight:700;color:${props.color || '#1e293b'}">${props.text || 'Heading'}</${tag}>`;
            case 'text':
                return `<div style="color:${props.color || '#475569'};line-height:${props.lineHeight || '1.6'};padding:12px;font-size:${props.fontSize || '15'}px;">${props.content || 'Text block'}</div>`;
            case 'image':
                if (!props.src) return `<div style="padding:30px;background:#f1f5f9;border-radius:8px;text-align:center;color:#94a3b8;border:2px dashed #cbd5e1;"><span style="font-size:32px">🖼️</span><br>Gambar belum dipilih</div>`;
                return `<div style="padding:12px;text-align:${props.align || 'center'}"><img src="${props.src}" alt="${props.alt || ''}" style="width:${props.width || '100'}%;border-radius:${props.borderRadius || '8'}px;max-width:100%"></div>`;
            case 'button':
                const colors = {
                    primary: '#6366f1',
                    secondary: '#64748b',
                    outline: '#6366f1',
                    ghost: '#6366f1'
                };
                const sizes = {
                    sm: '8px 16px',
                    md: '12px 24px',
                    lg: '16px 32px'
                };
                const style = props.style || 'primary';
                const linkMode = props.linkMode || (props.pageId ? 'page' : 'manual');
                const isUiOnly = linkMode === 'ui_only';
                const hasUrl = isUiOnly || (linkMode === 'page' ? !!props.pageId : !!(props.url && props.url.trim() !== '' && props.url.trim() !== '#'));
                const uiHint = isUiOnly ? '<span style="display:block;font-size:10px;color:#64748b;margin-top:4px">UI only: tidak ada aksi saat diklik</span>' : '';
                const urlWarning = !hasUrl && !isUiOnly ? '<span style="display:block;font-size:10px;color:#ef4444;margin-top:4px">⚠️ URL kosong</span>' : '';
                const wrapperStyle = isUiOnly ? '' : (!hasUrl ? 'border:1px dashed #ef4444;border-radius:8px;background:#fef2f2;' : '');
                const emptyNotice = !hasUrl && !isUiOnly ? '<div style="color:#ef4444;font-size:11px;margin-bottom:4px">⚠️ URL belum diatur</div>' : '';
                return `<div style="text-align:${props.align || 'center'};padding:12px;${wrapperStyle}">${emptyNotice}<button style="background:${colors[style]};color:white;border:none;border-radius:8px;padding:${sizes[props.size || 'md']};cursor:pointer;font-weight:600;font-size:14px;width:${props.fullWidth ? '100%' : 'auto'}">${props.text || 'Button'}</button>${uiHint}${urlWarning}</div>`;
            case 'card':
                return `<div style="border-radius:12px;padding:${props.padding || '20'}px;box-shadow:${props.showShadow ? '0 10px 15px -3px rgba(0,0,0,0.1)' : 'none'};background:${props.bgColor || '#ffffff'};border:${!props.showShadow ? '1px solid #e2e8f0' : 'none'}"><h4 style="margin:0 0 8px;font-weight:700;color:#1e293b;font-size:16px">${props.title || 'Card'}</h4><p style="margin:0;color:#64748b;font-size:14px">${props.content || ''}</p></div>`;
            case 'spacer':
                return `<div style="height:${props.height || '32'}px;background:#f8fafc;border-radius:4px;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center"><span style="font-size:10px;color:#94a3b8">Spacer</span></div>`;
            case 'divider':
                return `<hr style="border:none;border-top:${props.thickness || '2'}px solid ${props.color || '#e2e8f0'};margin:${props.margin || '16'}px 0;">`;
            case 'video':
                if (!props.url) return `<div style="padding:50px;background:#f1f5f9;border-radius:12px;text-align:center;color:#94a3b8;"><span style="font-size:48px">🎬</span><br>Masukkan URL video</div>`;
                return `<div style="width:${props.width || '100'}%;aspect-ratio:${props.aspectRatio || '16/9'};background:#000;border-radius:12px;display:flex;align-items:center;justify-content:center;color:white;">▶️ Video</div>`;
            case 'grid':
                return `<div style="display:grid;grid-template-columns:repeat(${props.columns || 3},1fr);gap:${props.gap || '16'}px;padding:${props.padding || '20'}px;background:#f8fafc;border-radius:8px;"><div style="padding:30px;background:white;border:2px dashed #e2e8f0;border-radius:8px;text-align:center;color:#94a3b8;">Kolom</div></div>`;
            case 'form':
                const form = (window.availableForms || []).find(f => f.id == props.formId);
                if (!props.formId) {
                    return `<div style="padding:24px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;text-align:center;">
                        <div style="font-size:32px;margin-bottom:12px">📝</div>
                        <div style="font-weight:700;color:#1e293b;font-size:16px">Form Belum Dipilih</div>
                        <div style="font-size:13px;color:#64748b;margin-top:4px">Pilih form di panel kanan</div>
                    </div>`;
                }

                const cacheKey = `${props.formId}|${props.showTitle ? 1 : 0}`;
                const cachedPreview = window.dynamicFormPreviewCache[cacheKey];
                if (cachedPreview) {
                    const srcDoc = buildDynamicFormPreviewSrcDoc(cachedPreview);
                    return `<div class="dynamic-form-preview-wrap" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                        <iframe
                            srcdoc="${srcDoc}"
                            style="width:100%;border:none;display:block;min-height:160px;pointer-events:none;"
                            onload="this.style.height=(this.contentWindow.document.documentElement.scrollHeight + 8) + 'px'"
                            sandbox="allow-scripts"
                        ></iframe>
                    </div>`;
                }

                requestDynamicFormPreview(block.id, props.formId, !!props.showTitle);
                return `<div style="padding:16px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;color:#64748b;">
                    <div style="font-weight:700;color:#1e293b;font-size:14px;margin-bottom:6px;">${form ? form.name : ('Form #' + props.formId)}</div>
                    <div style="font-size:12px;">Loading form preview...</div>
                </div>`;
            case 'section':
                return `<div style="padding:${props.padding || '40'}px;margin:${props.margin || '0'}px;background:${props.background || '#fff'};border-radius:8px;border:1px dashed #cbd5e1;color:#94a3b8;text-align:center;">📦 Section</div>`;
            default:
                return `<div style="padding:16px;background:#fef3c7;color:#92400e;">Unknown: ${block.type}</div>`;
        }
    }

    function requestDynamicFormPreview(blockId, formId, showTitle) {
        if (!formId) return;

        const cacheKey = `${formId}|${showTitle ? 1 : 0}`;
        if (window.dynamicFormPreviewCache[cacheKey]) return;
        if (window.dynamicFormPreviewPending[cacheKey]) return;

        window.dynamicFormPreviewPending[cacheKey] = true;
        const url = `${window.dynamicFormPreviewEndpoint}?id=${encodeURIComponent(formId)}&showTitle=${showTitle ? 1 : 0}`;

        fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async (res) => {
                const raw = await res.text();
                try {
                    return JSON.parse(raw);
                } catch (e) {
                    return {
                        success: false,
                        message: 'Preview response tidak valid.'
                    };
                }
            })
            .then(data => {
                if (!data || !data.success) {
                    window.dynamicFormPreviewCache[cacheKey] = `<div style="padding:12px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:8px;font-size:12px;">${(data && data.message) ? data.message : 'Gagal load form preview.'}</div>`;
                } else {
                    window.dynamicFormPreviewCache[cacheKey] = data.html || '';
                }
                renderBuilder(window.pageState);
            })
            .catch(() => {
                window.dynamicFormPreviewCache[cacheKey] = `<div style="padding:12px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:8px;font-size:12px;">Gagal load form preview.</div>`;
                renderBuilder(window.pageState);
            })
            .finally(() => {
                delete window.dynamicFormPreviewPending[cacheKey];
            });
    }

    function buildDynamicFormPreviewSrcDoc(contentHtml) {
        const doc = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <style>
                    html, body { margin: 0; padding: 0; background: #fff; }
                    body { font-family: Inter, Segoe UI, Arial, sans-serif; }
                </style>
            </head>
            <body>${contentHtml}</body>
            </html>
        `;
        return doc.replace(/"/g, '&quot;');
    }

    function addBlock(type) {
        if (isAddingBlock) return;
        isAddingBlock = true;

        const newBlock = {
            id: generateId(),
            type: type,
            props: JSON.parse(JSON.stringify(COMPONENT_DEFAULTS[type] || {}))
        };

        window.pageState.push(newBlock);
        selectedBlockId = newBlock.id;
        renderBuilder(window.pageState);
        renderProperties(newBlock.id);

        setTimeout(() => {
            isAddingBlock = false;
        }, 100);
    }

    function selectBlock(blockId) {
        selectedBlockId = blockId;
        document.querySelectorAll('.builder-block').forEach(el => {
            el.classList.toggle('selected', el.dataset.id === blockId);
        });
        renderProperties(blockId);
    }

    function deleteBlock(blockId) {
        window.pageState = window.pageState.filter(b => b.id !== blockId);
        if (selectedBlockId === blockId) {
            selectedBlockId = null;
            document.getElementById('properties-panel').innerHTML = '<div class="no-selection"><span class="material-symbols-outlined">touch_app</span><p>Pilih komponen untuk edit</p></div>';
        }
        renderBuilder(window.pageState);
    }

    function duplicateBlock(block) {
        const newBlock = {
            id: generateId(),
            type: block.type,
            props: JSON.parse(JSON.stringify(block.props || {}))
        };
        const index = window.pageState.findIndex(b => b.id === block.id);
        window.pageState.splice(index + 1, 0, newBlock);
        renderBuilder(window.pageState);
    }

    function renderProperties(blockId) {
        const panel = document.getElementById('properties-panel');
        const block = window.pageState.find(b => b.id === blockId);
        if (!block) return;

        const icons = {
            heading: 'title',
            text: 'notes',
            image: 'image',
            button: 'smart_button',
            form: 'dynamic_form',
            card: 'square',
            spacer: 'space_bar',
            divider: 'horizontal_rule',
            video: 'videocam',
            grid: 'grid_view',
            section: 'view_stream'
        };
        let html = `<div class="prop-header"><span class="material-symbols-outlined">${icons[block.type] || 'category'}</span><span class="block-type-badge">${block.type}</span></div>`;
        const props = block.props || {};

        switch (block.type) {
            case 'heading':
                html += `<div class="prop-section">
                <div class="prop-section-title">📝 Isi Heading</div>
                <div class="prop-group">
                    <label>Level Heading</label>
                    <select class="prop-select" onchange="updateProp('${blockId}', 'level', this.value)">
                        <option value="h1" ${props.level === 'h1' ? 'selected' : ''}>H1 - Judul Utama</option>
                        <option value="h2" ${props.level === 'h2' ? 'selected' : ''}>H2 - Sub Judul</option>
                        <option value="h3" ${props.level === 'h3' ? 'selected' : ''}>H3 - Heading 3</option>
                        <option value="h4" ${props.level === 'h4' ? 'selected' : ''}>H4 - Heading 4</option>
                        <option value="h5" ${props.level === 'h5' ? 'selected' : ''}>H5 - Heading 5</option>
                        <option value="h6" ${props.level === 'h6' ? 'selected' : ''}>H6 - Heading 6</option>
                    </select>
                    <small>Pilih tingkat heading untuk struktur SEO</small>
                </div>
                <div class="prop-group">
                    <label>Teks Heading</label>
                    <input type="text" class="prop-input" value="${props.text || ''}" onchange="updateProp('${blockId}', 'text', this.value)">
                </div>
                <div class="prop-group">
                    <label>Alignment</label>
                    <div class="prop-option-group">
                        <button class="prop-option-btn ${props.align === 'left' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'left')">Kiri</button>
                        <button class="prop-option-btn ${props.align === 'center' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'center')">Tengah</button>
                        <button class="prop-option-btn ${props.align === 'right' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'right')">Kanan</button>
                    </div>
                </div>
            </div>
            <div class="prop-section">
                <div class="prop-section-title">🎨 Styling</div>
                <div class="prop-group">
                    <label>Ukuran Font</label>
                    <input type="range" class="prop-slider" min="14" max="48" value="${props.fontSize || '32'}" onchange="updateProp('${blockId}', 'fontSize', this.value)">
                    <span class="prop-slider-value">${props.fontSize || '32'}px</span>
                </div>
                <div class="prop-group">
                    <label>Warna Teks</label>
                    <div class="prop-color-picker">
                        <input type="color" class="prop-color-input" value="${props.color || '#1e293b'}" onchange="updateProp('${blockId}', 'color', this.value)">
                        <input type="text" class="prop-color-value" value="${props.color || '#1e293b'}" onchange="updateProp('${blockId}', 'color', this.value)">
                    </div>
                </div>
            </div>`;
                break;

            case 'text':
                html += `<div class="prop-section">
                <div class="prop-section-title">📝 Konten</div>
                <div class="prop-group">
                    <label>Teks / Paragraf</label>
                    <textarea class="prop-textarea" onchange="updateProp('${blockId}', 'content', this.value)">${props.content || ''}</textarea>
                </div>
            </div>
            <div class="prop-section">
                <div class="prop-section-title">🎨 Styling</div>
                <div class="prop-group">
                    <label>Ukuran Font</label>
                    <input type="range" class="prop-slider" min="10" max="24" value="${props.fontSize || '15'}" onchange="updateProp('${blockId}', 'fontSize', this.value)">
                    <span class="prop-slider-value">${props.fontSize || '15'}px</span>
                </div>
                <div class="prop-group">
                    <label>Tinggi Baris</label>
                    <select class="prop-select" onchange="updateProp('${blockId}', 'lineHeight', this.value)">
                        <option value="1.3" ${props.lineHeight === '1.3' ? 'selected' : ''}>Rapat (1.3)</option>
                        <option value="1.5" ${props.lineHeight === '1.5' ? 'selected' : ''}>Normal (1.5)</option>
                        <option value="1.6" ${props.lineHeight === '1.6' ? 'selected' : ''}>Rileks (1.6)</option>
                        <option value="2" ${props.lineHeight === '2' ? 'selected' : ''}>Luas (2.0)</option>
                    </select>
                </div>
                <div class="prop-group">
                    <label>Warna Teks</label>
                    <div class="prop-color-picker">
                        <input type="color" class="prop-color-input" value="${props.color || '#475569'}" onchange="updateProp('${blockId}', 'color', this.value)">
                        <input type="text" class="prop-color-value" value="${props.color || '#475569'}" onchange="updateProp('${blockId}', 'color', this.value)">
                    </div>
                </div>
            </div>`;
                break;

            case 'button':
                const buttonLinkMode = getButtonLinkMode(props);
                const buttonPageId = props.pageId || props.page_id || '';
                html += `<div class="prop-section">
                <div class="prop-section-title">🔘 Teks & Link</div>
                <div class="prop-group">
                    <label>Teks Tombol</label>
                    <input type="text" class="prop-input" value="${props.text || ''}" onchange="updateProp('${blockId}', 'text', this.value)">
                </div>
                <div class="prop-group">
                    <label>Target Link</label>
                    <select class="prop-select" onchange="setButtonLinkMode('${blockId}', this.value)">
                        <option value="ui_only" ${buttonLinkMode === 'ui_only' ? 'selected' : ''}>Tanpa aksi / UI only</option>
                        <option value="manual" ${buttonLinkMode === 'manual' ? 'selected' : ''}>Link Manual</option>
                        <option value="page" ${buttonLinkMode === 'page' ? 'selected' : ''}>Page Workspace</option>
                    </select>
                    <small class="text-gray-500">Pilih tanpa aksi, page internal, atau isi link manual.</small>
                </div>
                <div class="prop-group" style="display:${buttonLinkMode === 'page' ? 'block' : 'none'};">
                    <label>Pilih Page</label>
                    <select class="prop-select" id="button-page-select-${blockId}" onchange="setButtonPageTarget('${blockId}', this.value)">
                        ${window.workspacePagesLoaded ? getWorkspacePageOptions(buttonPageId) : '<option value="">Memuat daftar page...</option>'}
                    </select>
                    <small class="text-gray-500">Halaman yang tersedia dari workspace aktif.</small>
                </div>
                <div class="prop-group" style="display:${buttonLinkMode === 'manual' ? 'block' : 'none'};">
                    <label>URL / Link</label>
                    <input type="text" class="prop-input" id="button-url-input-${blockId}" value="${props.url || ''}" onchange="validateButtonUrl('${blockId}', this.value)" placeholder="https://example.com atau /page/path">
                    <small id="button-url-help-${blockId}" class="text-gray-500">
                        ${props.url ? 'Contoh: https://example.com atau /page/path' : 'URL masih kosong. Isi manual atau pilih page.'}
                    </small>
                </div>
                <div class="prop-group" style="display:${buttonLinkMode === 'ui_only' ? 'block' : 'none'};">
                    <small class="text-gray-500">Button ini hanya untuk tampilan UI. Saat diklik tidak akan berpindah halaman.</small>
                </div>
            </div>
            <div class="prop-section">
                <div class="prop-section-title">🎨 Tampilan</div>
                <div class="prop-group">
                    <label>Style Tombol</label>
                    <div class="prop-option-group">
                        <button class="prop-option-btn ${props.style === 'primary' ? 'active' : ''}" onclick="updateProp('${blockId}', 'style', 'primary')">Primary</button>
                        <button class="prop-option-btn ${props.style === 'secondary' ? 'active' : ''}" onclick="updateProp('${blockId}', 'style', 'secondary')">Secondary</button>
                        <button class="prop-option-btn ${props.style === 'outline' ? 'active' : ''}" onclick="updateProp('${blockId}', 'style', 'outline')">Outline</button>
                        <button class="prop-option-btn ${props.style === 'ghost' ? 'active' : ''}" onclick="updateProp('${blockId}', 'style', 'ghost')">Ghost</button>
                    </div>
                </div>
                <div class="prop-group">
                    <label>Ukuran Tombol</label>
                    <select class="prop-select" onchange="updateProp('${blockId}', 'size', this.value)">
                        <option value="sm" ${props.size === 'sm' ? 'selected' : ''}>Kecil</option>
                        <option value="md" ${props.size === 'md' ? 'selected' : ''}>Sedang</option>
                        <option value="lg" ${props.size === 'lg' ? 'selected' : ''}>Besar</option>
                    </select>
                </div>
                <div class="prop-group">
                    <label>Alignment</label>
                    <div class="prop-option-group">
                        <button class="prop-option-btn ${props.align === 'left' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'left')">Kiri</button>
                        <button class="prop-option-btn ${props.align === 'center' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'center')">Tengah</button>
                        <button class="prop-option-btn ${props.align === 'right' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'right')">Kanan</button>
                    </div>
                </div>
                <div class="prop-checkbox-group">
                    <input type="checkbox" class="prop-checkbox" ${props.fullWidth ? 'checked' : ''} onchange="updateProp('${blockId}', 'fullWidth', this.checked)">
                    <label style="margin: 0; cursor: pointer;">Full Width (Lebar Penuh)</label>
                </div>
            </div>`;
                break;

            case 'card':
                html += `<div class="prop-section">
                <div class="prop-section-title">📋 Konten</div>
                <div class="prop-group">
                    <label>Judul Card</label>
                    <input type="text" class="prop-input" value="${props.title || ''}" onchange="updateProp('${blockId}', 'title', this.value)">
                </div>
                <div class="prop-group">
                    <label>Deskripsi</label>
                    <textarea class="prop-textarea" onchange="updateProp('${blockId}', 'content', this.value)">${props.content || ''}</textarea>
                </div>
            </div>
            <div class="prop-section">
                <div class="prop-section-title">🎨 Styling</div>
                <div class="prop-group">
                    <label>Padding Dalam</label>
                    <input type="range" class="prop-slider" min="0" max="40" value="${props.padding || '20'}" onchange="updateProp('${blockId}', 'padding', this.value)">
                    <span class="prop-slider-value">${props.padding || '20'}px</span>
                </div>
                <div class="prop-group">
                    <label>Warna Background</label>
                    <div class="prop-color-picker">
                        <input type="color" class="prop-color-input" value="${props.bgColor || '#ffffff'}" onchange="updateProp('${blockId}', 'bgColor', this.value)">
                        <input type="text" class="prop-color-value" value="${props.bgColor || '#ffffff'}" onchange="updateProp('${blockId}', 'bgColor', this.value)">
                    </div>
                </div>
                <div class="prop-checkbox-group">
                    <input type="checkbox" class="prop-checkbox" ${props.showShadow ? 'checked' : ''} onchange="updateProp('${blockId}', 'showShadow', this.checked)">
                    <label style="margin: 0; cursor: pointer;">Tampilkan Shadow</label>
                </div>
            </div>`;
                break;

            case 'spacer':
                html += `<div class="prop-section">
                <div class="prop-section-title">📏 Ukuran</div>
                <div class="prop-group">
                    <label>Tinggi Spacer</label>
                    <input type="range" class="prop-slider" min="8" max="128" value="${props.height || '32'}" onchange="updateProp('${blockId}', 'height', this.value)">
                    <span class="prop-slider-value">${props.height || '32'}px</span>
                </div>
            </div>`;
                break;

            case 'image':
                html += `<div class="prop-section">
                <div class="prop-section-title">🖼️ Gambar</div>
                <div class="prop-group">
                    <label>URL Gambar</label>
                    <input type="text" class="prop-input" value="${props.src || ''}" onchange="updateProp('${blockId}', 'src', this.value)" placeholder="https://example.com/image.jpg">
                    <small>Paste URL gambar di sini</small>
                </div>
                <div class="prop-group">
                    <label>Alt Text (untuk SEO & Accessibility)</label>
                    <input type="text" class="prop-input" value="${props.alt || ''}" onchange="updateProp('${blockId}', 'alt', this.value)" placeholder="Deskripsi gambar">
                </div>
            </div>
            <div class="prop-section">
                <div class="prop-section-title">⚙️ Ukuran & Posisi</div>
                <div class="prop-group">
                    <label>Lebar Gambar</label>
                    <input type="range" class="prop-slider" min="10" max="100" value="${props.width || '100'}" onchange="updateProp('${blockId}', 'width', this.value)">
                    <span class="prop-slider-value">${props.width || '100'}%</span>
                </div>
                <div class="prop-group">
                    <label>Border Radius</label>
                    <input type="range" class="prop-slider" min="0" max="50" value="${props.borderRadius || '8'}" onchange="updateProp('${blockId}', 'borderRadius', this.value)">
                    <span class="prop-slider-value">${props.borderRadius || '8'}px</span>
                </div>
                <div class="prop-group">
                    <label>Alignment</label>
                    <div class="prop-option-group">
                        <button class="prop-option-btn ${props.align === 'left' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'left')">Kiri</button>
                        <button class="prop-option-btn ${props.align === 'center' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'center')">Tengah</button>
                        <button class="prop-option-btn ${props.align === 'right' ? 'active' : ''}" onclick="updateProp('${blockId}', 'align', 'right')">Kanan</button>
                    </div>
                </div>
            </div>`;
                break;

            case 'grid':
                html += `<div class="prop-section">
                <div class="prop-section-title">⚙️ Grid Settings</div>
                <div class="prop-group">
                    <label>Jumlah Kolom</label>
                    <select class="prop-select" onchange="updateProp('${blockId}', 'columns', this.value)">
                        <option value="1" ${props.columns === '1' ? 'selected' : ''}>1 Kolom</option>
                        <option value="2" ${props.columns === '2' ? 'selected' : ''}>2 Kolom</option>
                        <option value="3" ${props.columns === '3' ? 'selected' : ''}>3 Kolom</option>
                        <option value="4" ${props.columns === '4' ? 'selected' : ''}>4 Kolom</option>
                        <option value="6" ${props.columns === '6' ? 'selected' : ''}>6 Kolom</option>
                    </select>
                </div>
                <div class="prop-group">
                    <label>Gap Antar Kolom</label>
                    <input type="range" class="prop-slider" min="0" max="32" value="${props.gap || '16'}" onchange="updateProp('${blockId}', 'gap', this.value)">
                    <span class="prop-slider-value">${props.gap || '16'}px</span>
                </div>
            </div>`;
                break;

            case 'form':
                html += `<div class="prop-section">
                <div class="prop-section-title">📋 Konfigurasi Form</div>
                <div class="prop-group">
                    <label>Pilih Form</label>
                    <select class="prop-select" onchange="updateProp('${blockId}', 'formId', this.value)">
                        <option value="">-- Pilih Form --</option>
                        ${(window.availableForms || []).map(f => `<option value="${f.id}" ${props.formId == f.id ? 'selected' : ''}>${f.name}</option>`).join('')}
                    </select>
                </div>
                <div class="prop-checkbox-group">
                    <input type="checkbox" class="prop-checkbox" ${props.showTitle ? 'checked' : ''} onchange="updateProp('${blockId}', 'showTitle', this.checked)">
                    <label style="margin: 0; cursor: pointer;">Tampilkan Judul Form</label>
                </div>
            </div>`;
                break;

            case 'divider':
                html += `<div class="prop-section">
                <div class="prop-section-title">📏 Ukuran</div>
                <div class="prop-group">
                    <label>Ketebalan</label>
                    <input type="range" class="prop-slider" min="1" max="10" value="${props.thickness || '2'}" onchange="updateProp('${blockId}', 'thickness', this.value)">
                    <span class="prop-slider-value">${props.thickness || '2'}px</span>
                </div>
                <div class="prop-group">
                    <label>Margin</label>
                    <input type="range" class="prop-slider" min="0" max="100" value="${props.margin || '16'}" onchange="updateProp('${blockId}', 'margin', this.value)">
                    <span class="prop-slider-value">${props.margin || '16'}px</span>
                </div>
            </div>
            <div class="prop-section">
                <div class="prop-section-title">🎨 Warna</div>
                <div class="prop-group">
                    <div class="prop-color-picker">
                        <input type="color" class="prop-color-input" value="${props.color || '#e2e8f0'}" onchange="updateProp('${blockId}', 'color', this.value)">
                        <input type="text" class="prop-color-value" value="${props.color || '#e2e8f0'}" onchange="updateProp('${blockId}', 'color', this.value)">
                    </div>
                </div>
            </div>`;
                break;

            case 'video':
                html += `<div class="prop-section">
                <div class="prop-section-title">📹 Video Settings</div>
                <div class="prop-group">
                    <label>Video URL (YouTube/Vimeo)</label>
                    <input type="text" class="prop-input" value="${props.url || ''}" onchange="updateProp('${blockId}', 'url', this.value)" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <div class="prop-group">
                    <label>Aspect Ratio</label>
                    <select class="prop-select" onchange="updateProp('${blockId}', 'aspectRatio', this.value)">
                        <option value="16/9" ${props.aspectRatio === '16/9' ? 'selected' : ''}>16:9 (Widescreen)</option>
                        <option value="4/3" ${props.aspectRatio === '4/3' ? 'selected' : ''}>4:3 (Standard)</option>
                        <option value="1/1" ${props.aspectRatio === '1/1' ? 'selected' : ''}>1:1 (Square)</option>
                        <option value="21/9" ${props.aspectRatio === '21/9' ? 'selected' : ''}>21:9 (Ultrawide)</option>
                    </select>
                </div>
            </div>`;
                break;

            case 'section':
                html += `<div class="prop-section">
                <div class="prop-section-title">📦 Section Settings</div>
                <div class="prop-group">
                    <label>Background Color</label>
                    <div class="prop-color-picker">
                        <input type="color" class="prop-color-input" value="${props.background || '#ffffff'}" onchange="updateProp('${blockId}', 'background', this.value)">
                        <input type="text" class="prop-color-value" value="${props.background || '#ffffff'}" onchange="updateProp('${blockId}', 'background', this.value)">
                    </div>
                </div>
                <div class="prop-group">
                    <label>Padding</label>
                    <input type="range" class="prop-slider" min="0" max="100" value="${props.padding || '40'}" onchange="updateProp('${blockId}', 'padding', this.value)">
                    <span class="prop-slider-value">${props.padding || '40'}px</span>
                </div>
                <div class="prop-group">
                    <label>Margin</label>
                    <input type="range" class="prop-slider" min="0" max="100" value="${props.margin || '0'}" onchange="updateProp('${blockId}', 'margin', this.value)">
                    <span class="prop-slider-value">${props.margin || '0'}px</span>
                </div>
            </div>`;
                break;

            default:
                html += '<div class="no-selection"><p>Tidak ada properties untuk komponen ini</p></div>';
        }

        panel.innerHTML = html;
    }

    function updateProp(blockId, key, value) {
        const block = window.pageState.find(b => b.id === blockId);
        if (!block) return;

        block.props = block.props || {};
        block.props[key] = value;
        renderBuilder(window.pageState);
        renderProperties(blockId);
    }

    function updateBlockProps(blockId, updates) {
        const block = window.pageState.find(b => b.id === blockId);
        if (!block) return;

        block.props = block.props || {};
        Object.assign(block.props, updates);
        renderBuilder(window.pageState);
        renderProperties(blockId);
    }

    function getButtonLinkMode(props) {
        if (!props) return 'manual';
        if ((props.linkMode || '').toLowerCase() === 'ui_only' || props.uiOnly === true) return 'ui_only';
        if ((props.linkMode || '').toLowerCase() === 'page') return 'page';
        if ((props.linkMode || '').toLowerCase() === 'none' || (props.linkMode || '').toLowerCase() === 'ui') return 'ui_only';
        if (props.pageId || props.pageSlug || props.page_id) return 'page';
        return 'manual';
    }

    function getWorkspacePageOptions(selectedPageId) {
        const pages = window.workspacePages || [];
        const selected = String(selectedPageId || '');

        const options = ['<option value="">Pilih page...</option>'];
        pages.forEach(page => {
            const pageId = String(page.id || '');
            const pageName = (page.name || ('Page ' + pageId)).replace(/[&<>"]/g, function(ch) {
                return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[ch] || ch);
            });
            const slugPart = page.slug ? ' (' + String(page.slug).replace(/[&<>"]/g, function(ch) {
                return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[ch] || ch);
            }) + ')' : '';
            options.push('<option value="' + pageId + '"' + (selected === pageId ? ' selected' : '') + '>' + pageName + slugPart + '</option>');
        });

        return options.join('');
    }

    async function loadWorkspacePages(force = false) {
        if (window.workspacePagesLoaded && !force) {
            return window.workspacePages;
        }

        if (window.workspacePagesLoading) {
            return window.workspacePages;
        }

        window.workspacePagesLoading = true;

        try {
            const response = await fetch('<?= Url::to(['get-pages']) ?>', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            window.workspacePages = Array.isArray(data.pages) ? data.pages : [];
        } catch (error) {
            console.warn('Failed to load workspace pages', error);
            window.workspacePages = [];
        } finally {
            window.workspacePagesLoaded = true;
            window.workspacePagesLoading = false;
        }

        if (selectedBlockId) {
            renderProperties(selectedBlockId);
        }

        return window.workspacePages;
    }

    function validateButtonUrl(blockId, value) {
        const normalizedValue = (value || '').trim() === '#' ? '' : (value || '').trim();
        updateBlockProps(blockId, {
            linkMode: 'manual',
            pageId: '',
            pageSlug: '',
            uiOnly: false,
            url: normalizedValue
        });
    }

    function setButtonLinkMode(blockId, mode) {
        const normalizedMode = ['page', 'ui_only', 'manual'].includes(mode) ? mode : 'manual';
        const block = window.pageState.find(b => b.id === blockId);
        if (!block) return;

        const props = block.props || {};
        const updates = {
            linkMode: normalizedMode
        };

        if (normalizedMode === 'page') {
            updates.url = '';
            updates.uiOnly = false;
        } else if (normalizedMode === 'ui_only') {
            updates.url = '';
            updates.pageId = '';
            updates.pageSlug = '';
            updates.uiOnly = true;
        } else {
            updates.pageId = '';
            updates.pageSlug = '';
            updates.uiOnly = false;
        }

        block.props = Object.assign({}, props, updates);
        renderBuilder(window.pageState);
        renderProperties(blockId);
    }

    function setButtonPageTarget(blockId, pageId) {
        const block = window.pageState.find(b => b.id === blockId);
        if (!block) return;

        const selectedPage = (window.workspacePages || []).find(page => String(page.id) === String(pageId));
        block.props = Object.assign({}, block.props || {}, {
            linkMode: 'page',
            pageId: pageId ? String(pageId) : '',
            pageSlug: selectedPage?.slug || '',
            uiOnly: false,
            url: ''
        });
        renderBuilder(window.pageState);
        renderProperties(blockId);
    }

    // Template Modal
    const templates = [{
            id: 'blank',
            name: 'Blank Canvas',
            description: 'Mulai dari awal dengan kanvas kosong',
            category: 'Basic',
            state: []
        },
        {
            id: 'hero-modern',
            name: 'Hero Modern',
            description: 'Hero section dengan headline & CTA yang menarik',
            category: 'Landing',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Solusi Digital Terbaik untuk Bisnis Anda',
                        align: 'center',
                        fontSize: '52',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Tingkatkan potensi bisnis Anda dengan teknologi modern. Tim profesional siap membantu transforms digital perusahaan Anda.',
                        align: 'center',
                        fontSize: '18',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 'b1',
                    type: 'button',
                    props: {
                        text: 'Konsultasi Gratis',
                        url: '',
                        style: 'primary',
                        size: 'lg'
                    }
                },
                {
                    id: 'b2',
                    type: 'button',
                    props: {
                        text: 'Pelajari Lebih Lanjut',
                        url: '',
                        style: 'outline',
                        size: 'lg'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'div1',
                    type: 'divider',
                    props: {
                        thickness: '1',
                        color: '#e2e8f0'
                    }
                }
            ]
        },
        {
            id: 'hero-split',
            name: 'Hero Split',
            description: 'Hero dengan dua kolom: teks & gambar',
            category: 'Landing',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Wujudkan Visi Digital Anda',
                        align: 'left',
                        fontSize: '44',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Kami menghadirkan solusi inovatif yang disesuaikan dengan kebutuhan unik bisnis Anda. Mulai perjalanan transformasi digital hari ini.',
                        fontSize: '17',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '20'
                    }
                },
                {
                    id: 'b1',
                    type: 'button',
                    props: {
                        text: 'Mulai Sekarang',
                        url: '',
                        style: 'primary'
                    }
                },
                {
                    id: 'b2',
                    type: 'button',
                    props: {
                        text: 'Lihat Demo',
                        url: '',
                        style: 'ghost'
                    }
                }
            ]
        },
        {
            id: 'about-corporate',
            name: 'About Corporate',
            description: 'Profil perusahaan lengkap dengan nilai',
            category: 'Company',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Tentang Perusahaan Kami',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Didirikan pada tahun 2015, kami telah dipercaya oleh ratusan klien dari berbagai industri untuk menghadirkan solusi digital yang inovatif dan berdampak.',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'h2',
                    type: 'heading',
                    props: {
                        level: 'h2',
                        text: 'Nilai-Nilai Kami',
                        align: 'center',
                        fontSize: '28',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Integritas',
                        content: 'Kami menjalankan bisnis dengan kejujuran dan transparansi penuh dalam setiap aspek.',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Profesionalisme',
                        content: 'Tim berpengalaman dengan standar kualitas tinggi dalam setiap deliverable.',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Inovasi',
                        content: 'Terus berkembang dan mengadopsi teknologi terbaru untuk solusi optimal.',
                        showShadow: true
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 'd1',
                    type: 'divider',
                    props: {
                        thickness: '1',
                        color: '#e2e8f0'
                    }
                }
            ]
        },
        {
            id: 'about-team',
            name: 'About with Team',
            description: 'Tentang perusahaan dengan profil tim',
            category: 'Company',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Mengenal Kami Lebih Dekat',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Kami adalah tim yang terdiri dari para profesional berbakat dengan keahlian di berbagai bidang teknologi dan bisnis.',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'h2',
                    type: 'heading',
                    props: {
                        level: 'h2',
                        text: 'Tim Inti Kami',
                        align: 'center',
                        fontSize: '28',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Ahmad Pratama',
                        content: 'CEO & Co-Founder\n15 tahun pengalaman di industri teknologi',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Siti Rahayu',
                        content: 'Chief Technology Officer\nExpert dalam sistem arsitektur',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Budi Santoso',
                        content: 'Head of Design\nSpesialis UX/UI Design',
                        showShadow: true
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'Diana Kusuma',
                        content: 'Project Manager\nCertified PMP',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'services-modern',
            name: 'Services Modern',
            description: 'Layanan bisnis dengan grid cards',
            category: 'Business',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Layanan Profesional Kami',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Kami menawarkan solusi komprehensif untuk memenuhi berbagai kebutuhan bisnis Anda.',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Web Development',
                        content: 'Pengembangan website responsif dengan teknologi modern seperti React, Vue, dan Laravel.',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Mobile Apps',
                        content: 'Aplikasi iOS dan Android yang performant dengan pengalaman pengguna terbaik.',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'UI/UX Design',
                        content: 'Desain antarmuka yang intuitif dan menarik untuk produk digital Anda.',
                        showShadow: true
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'Cloud Solutions',
                        content: 'Solusi cloud computing untuk skalabilitas dan efisiensi infrastruktur.',
                        showShadow: true
                    }
                },
                {
                    id: 'card5',
                    type: 'card',
                    props: {
                        title: 'Data Analytics',
                        content: 'Analisis data untuk pengambilan keputusan bisnis yang berbasis data.',
                        showShadow: true
                    }
                },
                {
                    id: 'card6',
                    type: 'card',
                    props: {
                        title: 'Cyber Security',
                        content: 'Perlindungan sistem dan data dengan standar keamanan tertinggi.',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'services-classic',
            name: 'Services Classic',
            description: 'Layanan dengan list dan deskripsi',
            category: 'Business',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Apa yang Kami Tawarkan',
                        align: 'left',
                        fontSize: '38',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Konsultasi Strategi Digital',
                        content: 'Membantu Anda merumuskan strategi digital yang tepat untuk mencapai tujuan bisnis.',
                        showShadow: false
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Implementasi Teknologi',
                        content: 'Mengimplementasikan solusi teknologi yang sesuai dengan kebutuhan spesifik perusahaan.',
                        showShadow: false
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Pendampingan & Training',
                        content: 'Memberikan pelatihan dan pendampingan untuk memastikan keberhasilan implementasi.',
                        showShadow: false
                    }
                }
            ]
        },
        {
            id: 'pricing-standard',
            name: 'Pricing Standard',
            description: 'Pricing table 3 kolom professional',
            category: 'Business',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Paket Harga',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Pilih paket yang paling sesuai dengan kebutuhan dan budget Anda',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Starter',
                        content: 'Untuk bisnis kecil yang baru memulai',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Business',
                        content: 'Solusi lengkap untuk berkembang',
                        bgColor: '#f0fdf4',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Enterprise',
                        content: 'Untuk kebutuhan skala besar',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'pricing-comparison',
            name: 'Pricing Comparison',
            description: 'Perbandingan fitur antar paket',
            category: 'Business',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Perbandingan Paket',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Lihat detail setiap paket untuk menemukan yang paling sesuai',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Basic Plan',
                        content: 'Rp 990.000/bulan\n\nInclude: 5 Users, 10GB Storage, Email Support',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Professional',
                        content: 'Rp 2.490.000/bulan\n\nInclude: Unlimited Users, 100GB Storage, Priority Support',
                        bgColor: '#eff6ff',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Enterprise',
                        content: 'Hubungi kami\n\nInclude: Custom Solutions, Dedicated Support, SLA',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'testimonials-grid',
            name: 'Testimonials Grid',
            description: 'Ulasan klien dalam grid cards',
            category: 'Social Proof',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Apa Kata Klien Kami',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Kepuasan klien adalah prioritas utama kami. Berikut beberapa testimoni dari klien yang telah bekerja sama dengan kami.',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'PT Maju Jaya',
                        content: '"Tim sangat profesional dan bertanggung jawab. Proyek selesai tepat waktu dengan hasil yang melebihi ekspektasi kami. Sangat direkomendasikan!"',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Startup Digital Indonesia',
                        content: '"Pengalaman kolaborasi yang luar biasa. Komunikasi yang baik dan pemahaman kebutuhan yang mendalam. Akan bekerja sama lagi untuk proyek berikutnya."',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'CV Nusantara Tech',
                        content: '"Solusi yang diberikan sangat tepat sasaran. Website kami sekarang jauh lebih baik dan visitor meningkat signifikan."',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'Toko Online Sejahtera',
                        content: '"Aplikasi mobile yang dikembangkan bekerja dengan sangat baik. User experience sangat memuaskan."',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'testimonials-slider',
            name: 'Testimonials Classic',
            description: 'Testimoni dengan format quote',
            category: 'Social Proof',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Testimoni Klien',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'div1',
                    type: 'divider',
                    props: {
                        thickness: '2',
                        color: '#3b82f6',
                        margin: '32'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Robert Wijaya',
                        content: '"Kualitas layanan yang luar biasa. Tim developers sangat kompeten dan selalu siap membantu. Ini adalah pengalaman kerja yang sangat menyenangkan."',
                        showShadow: false
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '20'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Lisa Permata',
                        content: '"Mereka tidak hanya menghasilkan produk yang bagus, tetapi juga memberikan nilai tambah berupa konsultasi strategis yang sangat membantu bisnis kami."',
                        showShadow: false
                    }
                },
                {
                    id: 's4',
                    type: 'spacer',
                    props: {
                        height: '20'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Hendra Setiawan',
                        content: '"Profesionalisme tim terlihat dari cara mereka menangani setiap detail proyek. Hasil akhir sangat memuaskan."',
                        showShadow: false
                    }
                }
            ]
        },
        {
            id: 'team-showcase',
            name: 'Team Showcase',
            description: 'Profil lengkap anggota tim',
            category: 'Company',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Tim Profesional Kami',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Kenali tim ahli yang siap memberikan solusi terbaik untuk Anda',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Dr. Ahmad Pratama',
                        content: 'Chief Executive Officer\nSpesialis Strategi Digital\n15+ Tahun Pengalaman',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Maya Kusumawati',
                        content: 'Chief Technology Officer\nExpert Cloud Architecture\n12+ Tahun Pengalaman',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Budi Santoso',
                        content: 'Head of Engineering\nFull Stack Developer\n10+ Tahun Pengalaman',
                        showShadow: true
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'Nina Hartati',
                        content: 'Lead UI/UX Designer\nSpesialis Product Design\n8+ Tahun Pengalaman',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'portfolio-grid',
            name: 'Portfolio Grid',
            description: 'Galeri proyek dalam grid',
            category: 'Showcase',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Portfolio Proyek',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Berikut adalah beberapa proyek yang telah berhasil kami selesaikan untuk berbagai klien',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'E-Commerce Platform',
                        content: 'Platform marketplace lengkap dengan payment gateway, inventory management, dan analytics dashboard.',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Mobile Banking App',
                        content: 'Aplikasi mobile banking dengan fitur transfer, bill payment, dan investasi.',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Corporate Website',
                        content: 'Website responsif untuk perusahaan dengan CMS custom dan SEO optimization.',
                        showShadow: true
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'HR Management System',
                        content: 'Sistem HRIS untuk manajemen karyawan, attendance, dan payroll.',
                        showShadow: true
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 'b1',
                    type: 'button',
                    props: {
                        text: 'Lihat Semua Proyek',
                        url: '',
                        style: 'primary'
                    }
                }
            ]
        },
        {
            id: 'portfolio-masonry',
            name: 'Portfolio Masonry',
            description: 'Portfolio dengan layout masonry',
            category: 'Showcase',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Karya Kami',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Berbagai proyek yang telah kami selesaikan dengan hasil yang memuaskan',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Proyek 1',
                        content: 'Web Application - 2024',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Proyek 2',
                        content: 'Mobile App - 2024',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Proyek 3',
                        content: 'UI/UX Design - 2023',
                        showShadow: true
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'Proyek 4',
                        content: 'System Integration - 2023',
                        showShadow: true
                    }
                },
                {
                    id: 'card5',
                    type: 'card',
                    props: {
                        title: 'Proyek 5',
                        content: 'Consulting - 2023',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'contact-form',
            name: 'Contact Form',
            description: 'Halaman kontak dengan form',
            category: 'Contact',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Hubungi Kami',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Apakah Anda memiliki pertanyaan? Jangan ragu untuk menghubungi kami.',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Kantor Pusat',
                        content: 'Jl. Sudirman No. 123\nJakarta Selatan 12190',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Email & Phone',
                        content: 'info@perusahaan.com\n+62 21 1234 5678',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Jam Operasional',
                        content: 'Senin - Jumat: 09.00 - 18.00\nSabtu: 09.00 - 14.00',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'faq-accordion',
            name: 'FAQ Accordion',
            description: 'FAQ dengan format pertanyaan',
            category: 'Support',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Frequently Asked Questions',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Temukan jawaban untuk pertanyaan yang sering diajukan',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Bagaimana cara memulai proyek?',
                        content: 'Anda dapat menghubungi kami melalui email atau telepon untuk konsultasi awal. Tim kami akan membantu Anda merumuskan kebutuhan dan memberikan proposal yang sesuai.',
                        showShadow: false
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Berapa lama waktu pengembangan?',
                        content: 'Waktu pengembangan tergantung pada kompleksitas proyek. Untuk website sederhana membutuhkan 2-4 minggu, sedangkan aplikasi kompleks dapat memakan waktu 2-6 bulan.',
                        showShadow: false
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Apakah ada garansi layanan?',
                        content: 'Ya, kami memberikan garansi 3 bulan untuk setiap proyek. Selama periode garansi, perbaikan bug dan masalah minor tidak dikenakan biaya tambahan.',
                        showShadow: false
                    }
                },
                {
                    id: 's4',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card4',
                    type: 'card',
                    props: {
                        title: 'Bagaimana sistem pembayaran?',
                        content: 'Kami menggunakan sistem pembayaran bertahap (milestone). DP 30% di awal, 40% saat midpoint, dan pelunasan 30% setelah proyek selesai.',
                        showShadow: false
                    }
                }
            ]
        },
        {
            id: 'blog-grid',
            name: 'Blog Grid',
            description: 'Artikel dalam grid cards',
            category: 'Content',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Artikel Terbaru',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Baca artikel terbaru tentang teknologi dan bisnis',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Strategi Digital Marketing 2024',
                        content: 'Pelajari strategi terbaru untuk meningkatkan presence digital bisnis Anda di tahun 2024...',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Mengenal Cloud Computing',
                        content: 'Apa itu cloud computing dan bagaimana implementasinya untuk bisnis skala kecil hingga besar...',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Tips Memilih Hosting',
                        content: 'Panduan memilih layanan hosting yang tepat berdasarkan kebutuhan dan budget Anda...',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'blog-list',
            name: 'Blog List',
            description: 'Artikel dalam format list',
            category: 'Content',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Latest Articles',
                        align: 'left',
                        fontSize: '38',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'The Future of AI in Business',
                        content: 'Artificial Intelligence membawa perubahan besar dalam dunia bisnis. Pelajari bagaimana AI dapat membantu bisnis Anda berkembang...',
                        showShadow: false
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Web Security Best Practices',
                        content: 'Keamanan website adalah prioritas. Berikut panduan lengkap untuk mengamankan aplikasi web Anda dari ancaman...',
                        showShadow: false
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Mobile-First Design Strategy',
                        content: 'Mengapa mobile-first approach penting untuk strategi digital Anda dan bagaimana mengimplementasikannya...',
                        showShadow: false
                    }
                }
            ]
        },
        {
            id: 'features-comparison',
            name: 'Features Comparison',
            description: 'Perbandingan fitur lengkap',
            category: 'Business',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Bandingkan Fitur',
                        align: 'center',
                        fontSize: '42',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Lihat perbandingan lengkap fitur di setiap paket',
                        align: 'center',
                        fontSize: '16',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Basic',
                        content: '1 User\n5GB Storage\nBasic Support\nStandard Features',
                        bgColor: '#f8fafc',
                        showShadow: true
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Professional',
                        content: '10 Users\n50GB Storage\nPriority Support\nAdvanced Features\nAPI Access',
                        bgColor: '#eff6ff',
                        showShadow: true
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Enterprise',
                        content: 'Unlimited Users\nUnlimited Storage\n24/7 Dedicated Support\nAll Features\nCustom Integration',
                        bgColor: '#f0fdf4',
                        showShadow: true
                    }
                }
            ]
        },
        {
            id: 'coming-soon',
            name: 'Coming Soon',
            description: 'Halaman dalam pengembangan',
            category: 'Utility',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Coming Soon',
                        align: 'center',
                        fontSize: '56',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Kami sedang bekerja keras untuk sesuatu yang luar biasa. Stay tuned!',
                        align: 'center',
                        fontSize: '18',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'div1',
                    type: 'divider',
                    props: {
                        thickness: '2',
                        color: '#3b82f6',
                        margin: '40'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 't2',
                    type: 'text',
                    props: {
                        content: 'Ikuti pembaruan terbaru:',
                        align: 'center',
                        fontSize: '14',
                        color: '#64748b'
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'b1',
                    type: 'button',
                    props: {
                        text: 'Berlangganan Info',
                        url: '',
                        style: 'primary'
                    }
                }
            ]
        },
        {
            id: 'thank-you',
            name: 'Thank You',
            description: 'Halaman terima kasih',
            category: 'Utility',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Terima Kasih',
                        align: 'center',
                        fontSize: '48',
                        color: '#059669'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Pesan Anda telah berhasil kami terima. Tim kami akan merespons dalam waktu 1x24 jam kerja.',
                        align: 'center',
                        fontSize: '18',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '48'
                    }
                },
                {
                    id: 'div1',
                    type: 'divider',
                    props: {
                        thickness: '1',
                        color: '#e2e8f0'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 't2',
                    type: 'text',
                    props: {
                        content: 'Butuh bantuan langsung? Hubungi kami di +62 21 1234 5678',
                        align: 'center',
                        fontSize: '14',
                        color: '#64748b'
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 'b1',
                    type: 'button',
                    props: {
                        text: 'Kembali ke Beranda',
                        url: '/',
                        style: 'primary'
                    }
                }
            ]
        },
        {
            id: 'error-404',
            name: 'Error 404',
            description: 'Halaman kesalahan halaman tidak ditemukan',
            category: 'Utility',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: '404',
                        align: 'center',
                        fontSize: '72',
                        color: '#0f172a'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Oops! Halaman yang Anda cari tidak ditemukan.',
                        align: 'center',
                        fontSize: '20',
                        color: '#475569'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 't2',
                    type: 'text',
                    props: {
                        content: 'Mungkin halaman telah dipindahkan atau tidak lagi tersedia.',
                        align: 'center',
                        fontSize: '14',
                        color: '#64748b'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '40'
                    }
                },
                {
                    id: 'b1',
                    type: 'button',
                    props: {
                        text: 'Kembali ke Beranda',
                        url: '/',
                        style: 'primary'
                    }
                }
            ]
        },
        {
            id: 'privacy-policy',
            name: 'Privacy Policy',
            description: 'Kebijakan privasi',
            category: 'Legal',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Kebijakan Privasi',
                        align: 'left',
                        fontSize: '36',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Terakhir diperbarui: Mei 2024',
                        fontSize: '14',
                        color: '#64748b'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Pengumpulan Data',
                        content: 'Kami mengumpulkan informasi yang Anda berikan secara s    ukarela saat menggunakan layanan kami.',
                        showShadow: false
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Penggunaan Data',
                        content: 'Data yang dikumpulkan digunakan untuk meningkatkan layanan dan pengalaman pengguna.',
                        showShadow: false
                    }
                },
                {
                    id: 's4',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Keamanan Data',
                        content: 'Kami menerapkan langkah-langkah keamanan yang ketat untuk melindungi data Anda.',
                        showShadow: false
                    }
                }
            ]
        },
        {
            id: 'terms-of-service',
            name: 'Terms of Service',
            description: 'Ketentuan layanan',
            category: 'Legal',
            state: [{
                    id: 'h1',
                    type: 'heading',
                    props: {
                        level: 'h1',
                        text: 'Ketentuan Layanan',
                        align: 'left',
                        fontSize: '36',
                        color: '#0f172a'
                    }
                },
                {
                    id: 's1',
                    type: 'spacer',
                    props: {
                        height: '24'
                    }
                },
                {
                    id: 't1',
                    type: 'text',
                    props: {
                        content: 'Dengan menggunakan layanan kami, Anda menyetujui ketentuan berikut:',
                        fontSize: '14',
                        color: '#64748b'
                    }
                },
                {
                    id: 's2',
                    type: 'spacer',
                    props: {
                        height: '32'
                    }
                },
                {
                    id: 'card1',
                    type: 'card',
                    props: {
                        title: 'Penggunaan Layanan',
                        content: 'Layanan hanya dapat digunakan untuk tujuan yang sah dan sesuai dengan ketentuan yang berlaku.',
                        showShadow: false
                    }
                },
                {
                    id: 's3',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card2',
                    type: 'card',
                    props: {
                        title: 'Batasan Tanggung Jawab',
                        content: 'Kami tidak bertanggung jawab atas kerugian yang timbul dari penggunaan layanan.',
                        showShadow: false
                    }
                },
                {
                    id: 's4',
                    type: 'spacer',
                    props: {
                        height: '16'
                    }
                },
                {
                    id: 'card3',
                    type: 'card',
                    props: {
                        title: 'Perubahan Ketentuan',
                        content: 'Ketentuan dapat berubah sewaktu-waktu dengan pemberitahuan terlebih dahulu.',
                        showShadow: false
                    }
                }
            ]
        }
    ];

    let selectedTemplateId = null;
    let previewTemplateId = null;
    let templateSearchQuery = '';
    let templateCategory = 'all';

    const templateCategories = [{
            key: 'all',
            label: 'Semua',
            categories: null
        },
        {
            key: 'beranda',
            label: 'Beranda',
            categories: ['Landing']
        },
        {
            key: 'profil',
            label: 'Profil',
            categories: ['Company', 'Business', 'Social Proof']
        },
        {
            key: 'layanan',
            label: 'Layanan',
            categories: ['Marketing', 'Support', 'Contact']
        },
        {
            key: 'konten',
            label: 'Konten',
            categories: ['Content', 'Showcase']
        },
        {
            key: 'utilitas',
            label: 'Utilitas',
            categories: ['Basic', 'Utility', 'Legal']
        }
    ];

    function getTemplateSections(template) {
        if (!template || !template.state || !template.state.length) return ['Konten bebas'];
        return template.state.map((block, index) => {
            const props = block.props || {};
            if (block.type === 'heading') return props.text || `Heading ${index + 1}`;
            if (block.type === 'text') return `Teks ${index + 1}`;
            if (block.type === 'button') return props.text || `Tombol ${index + 1}`;
            if (block.type === 'card') return props.title || `Card ${index + 1}`;
            if (block.type === 'form') return props.title || 'Formulir';
            return `${block.type.charAt(0).toUpperCase()}${block.type.slice(1)} ${index + 1}`;
        }).slice(0, 8);
    }

    function getTemplateList() {
        const query = templateSearchQuery.toLowerCase().trim();
        const activeCategory = templateCategories.find(category => category.key === templateCategory);
        return templates.filter(template => {
            const matchCategory = !activeCategory?.categories || activeCategory.categories.includes(template.category);
            const matchSearch = !query ||
                (template.name || '').toLowerCase().includes(query) ||
                (template.description || '').toLowerCase().includes(query) ||
                (template.category || '').toLowerCase().includes(query);
            return matchCategory && matchSearch;
        });
    }

    function renderPreviewContent(state, mode = 'card') {
        if (!state || state.length === 0) {
            const size = mode === 'modal' ? '48px' : '28px';
            return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;min-height:${mode === 'modal' ? '420px' : '90px'};gap:6px;color:#c8c8c5;text-align:center;font-family:Arial;">
                <i class="ti ti-file-plus" style="font-size:${size};opacity:.45;"></i>
                <span style="font-size:${mode === 'modal' ? '14px' : '9px'};">Halaman Kosong</span>
            </div>`;
        }

        if (mode === 'card') {
            const summarizeBlock = (block, index) => {
                const props = block.props || {};
                const labelMap = {
                    heading: props.text || `Heading ${index + 1}`,
                    text: props.content || `Text ${index + 1}`,
                    button: props.text || `Button ${index + 1}`,
                    card: props.title || `Card ${index + 1}`,
                    form: props.title || 'Formulir',
                    image: props.alt || 'Image',
                    divider: 'Divider',
                    spacer: 'Spacer',
                    video: 'Video',
                    grid: 'Grid',
                    section: 'Section',
                    row: 'Row',
                };

                const label = labelMap[block.type] || `${block.type.charAt(0).toUpperCase()}${block.type.slice(1)}`;
                return `<div style="padding:7px 10px;border:1px solid #ecebe5;border-radius:8px;background:#ffffff;margin-bottom:6px;font-size:9px;line-height:1.35;color:#6b6b68;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <strong style="color:#111110;font-size:9px;">${label}</strong>
                </div>`;
            };

            const visibleBlocks = state.slice(0, 4);
            const hiddenCount = Math.max(state.length - visibleBlocks.length, 0);

            return `<div style="padding:8px;background:#f9f8f4;border-radius:10px;border:1px solid #ecebe5;height:100%;overflow:hidden;font-family:Arial;">
                ${visibleBlocks.map((block, index) => summarizeBlock(block, index)).join('')}
                ${hiddenCount > 0 ? `<div style="font-size:9px;color:#9d9d9a;padding:4px 2px 0;">+${hiddenCount} lainnya</div>` : ''}
            </div>`;
        }

        return state.map(block => {
            const props = block.props || {};
            const scale = mode === 'modal' ? 1 : 0.55;
            const textSize = (value, fallback) => `${Math.max(Math.round(parseInt(value || fallback) * scale), mode === 'modal' ? 12 : 7)}px`;

            switch (block.type) {
                case 'heading': {
                    const tag = props.level || 'h2';
                    return `<${tag} style="font-size:${textSize(props.fontSize, 28)};font-weight:700;color:${props.color || '#111110'};margin:0 0 ${mode === 'modal' ? '10px' : '4px'} 0;line-height:1.18;text-align:${props.align || 'left'}">${props.text || 'Heading'}</${tag}>`;
                }
                case 'text':
                    return `<p style="font-size:${textSize(props.fontSize, 15)};color:${props.color || '#6b6b68'};line-height:${props.lineHeight || '1.5'};margin:0 0 ${mode === 'modal' ? '12px' : '4px'} 0;text-align:${props.align || 'left'}">${props.content || 'Text'}</p>`;
                case 'button':
                    return `<span style="display:inline-flex;align-items:center;margin:${mode === 'modal' ? '4px 6px 14px 0' : '2px'};padding:${mode === 'modal' ? '8px 16px' : '4px 10px'};background:#534AB7;color:#fff;border-radius:${mode === 'modal' ? '8px' : '5px'};font-size:${mode === 'modal' ? '12px' : '7px'};font-family:Arial;font-weight:600;">${props.text || 'Button'}</span>`;
                case 'card':
                    return `<div style="background:${props.bgColor || '#fff'};border:1px solid #eee;border-radius:8px;padding:${mode === 'modal' ? '14px' : '6px'};margin:0 0 ${mode === 'modal' ? '10px' : '4px'} 0;">
                        <strong style="display:block;color:#111110;font-size:${mode === 'modal' ? '14px' : '8px'};margin-bottom:3px;">${props.title || 'Card'}</strong>
                        <p style="margin:0;color:#6b6b68;font-size:${mode === 'modal' ? '12px' : '7px'};line-height:1.45;">${props.content || ''}</p>
                    </div>`;
                case 'divider':
                    return `<hr style="border:none;border-top:1px solid #e6e4dd;margin:${mode === 'modal' ? '16px 0' : '4px 0'}">`;
                case 'spacer':
                    return `<div style="height:${mode === 'modal' ? Math.max(parseInt(props.height || 16) / 2, 8) : 4}px"></div>`;
                case 'image':
                    return `<div style="height:${mode === 'modal' ? '160px' : '30px'};background:#f0efea;border:1px solid #dedbd2;border-radius:8px;margin-bottom:${mode === 'modal' ? '12px' : '4px'};display:flex;align-items:center;justify-content:center;color:#9d9d9a;font-size:${mode === 'modal' ? '12px' : '7px'};"><i class="ti ti-photo"></i></div>`;
                case 'form':
                    return `<div style="border:1px solid #eee;border-radius:8px;padding:${mode === 'modal' ? '14px' : '6px'};margin-bottom:${mode === 'modal' ? '12px' : '4px'};"><strong style="font-size:${mode === 'modal' ? '13px' : '8px'};color:#111;">${props.title || 'Formulir'}</strong><div style="height:${mode === 'modal' ? '34px' : '12px'};background:#f5f5f3;border-radius:5px;margin-top:8px;"></div></div>`;
                default:
                    return '';
            }
        }).join('');
    }

    function renderTemplateTabs() {
        const filterbar = document.getElementById('templateFilterbar');
        if (!filterbar) return;

        const visibleCategories = templateCategories.filter(category => {
            if (!category.categories) return true;
            return templates.some(template => category.categories.includes(template.category));
        });

        filterbar.innerHTML = visibleCategories.map(category => {
            const count = templates.filter(template => !category.categories || category.categories.includes(template.category)).length;
            return `<button class="template-tab ${templateCategory === category.key ? 'active' : ''}" onclick="setTemplateCategory('${category.key}')">
                ${category.label} <span class="template-tab-count">${count}</span>
            </button>`;
        }).join('');
    }

    function renderTemplates() {
        const grid = document.getElementById('templateGrid');
        const empty = document.getElementById('templateEmptyState');
        if (!grid) return;

        renderTemplateTabs();
        const list = getTemplateList();
        if (!list.length) {
            grid.innerHTML = '';
            if (empty) empty.style.display = 'flex';
            return;
        }

        if (empty) empty.style.display = 'none';
        grid.innerHTML = list.map(template => {
            const selected = selectedTemplateId === template.id;
            const sections = getTemplateSections(template);
            return `
                <div class="template-card ${selected ? 'selected' : ''}" data-template-id="${template.id}" onclick="selectTemplate('${template.id}')" ondblclick="openTemplatePreview('${template.id}')">
                    <div class="template-check"><i class="ti ti-check"></i></div>
                    <div class="template-preview">
                        <div class="template-preview-content">${renderPreviewContent(template.state)}</div>
                    </div>
                    <div class="template-info">
                        <h4>${template.name}</h4>
                        <p>${template.description}</p>
                        <div class="template-card-meta">
                            <button class="template-preview-btn" onclick="event.stopPropagation();openTemplatePreview('${template.id}')">
                                <i class="ti ti-eye"></i> Preview
                            </button>
                            <span class="template-tag">${sections.length} bagian</span>
                        </div>
                    </div>
                </div>`;
        }).join('');

        applyMaterialIconFallback(grid);
    }

    function syncTemplateSelection() {
        document.querySelectorAll('#templateGrid .template-card').forEach(card => {
            card.classList.toggle('selected', card.dataset.templateId === selectedTemplateId);
        });
        updateTemplateSelectionState();
    }

    function setTemplateCategory(category) {
        templateCategory = category;
        renderTemplates();
    }

    function filterTemplatesClient() {
        const input = document.getElementById('templateSearchInput');
        templateSearchQuery = input ? input.value : '';
        renderTemplates();
    }

    function updateTemplateSelectionState() {
        const template = templates.find(t => t.id === selectedTemplateId);
        const selectedName = document.getElementById('templateSelectedName');
        const useBtn = document.getElementById('templateUseBtn');

        if (selectedName) selectedName.textContent = template ? template.name : '-';
        if (useBtn) useBtn.disabled = !selectedTemplateId;
    }

    function selectTemplate(id) {
        selectedTemplateId = id;
        syncTemplateSelection();
    }

    function startBlankTemplate() {
        selectedTemplateId = 'blank';
        confirmTemplate();
    }

    function openTemplatePreview(id) {
        previewTemplateId = id;
        const template = templates.find(t => t.id === id);
        if (!template) return;

        document.getElementById('templatePreviewTitle').textContent = template.name;
        document.getElementById('templatePreviewDesc').textContent = template.description;
        document.getElementById('templatePreviewInner').innerHTML = `<div class="template-preview-content">${renderPreviewContent(template.state, 'modal')}</div>`;
        document.getElementById('templatePreviewSections').innerHTML = getTemplateSections(template).map((section, index) =>
            `<button class="template-sec-item ${index === 0 ? 'active' : ''}" onclick="activateTemplateSection(this)">${section}</button>`
        ).join('');
        document.getElementById('templatePreviewOverlay').classList.add('open');
        applyMaterialIconFallback(document.getElementById('templatePreviewOverlay'));
    }

    function activateTemplateSection(button) {
        document.querySelectorAll('.template-sec-item').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
    }

    function closeTemplatePreview() {
        const overlay = document.getElementById('templatePreviewOverlay');
        if (overlay) overlay.classList.remove('open');
    }

    function handleTemplatePreviewOverlay(event) {
        if (event.target && event.target.id === 'templatePreviewOverlay') closeTemplatePreview();
    }

    function navTemplatePreview(direction) {
        const list = getTemplateList();
        if (!list.length) return;
        const index = Math.max(list.findIndex(template => template.id === previewTemplateId), 0);
        const next = list[(index + direction + list.length) % list.length];
        if (next) openTemplatePreview(next.id);
    }

    function selectPreviewTemplate() {
        if (!previewTemplateId) return;
        selectTemplate(previewTemplateId);
        closeTemplatePreview();
    }

    async function confirmTemplate() {
        let newState = [];
        if (selectedTemplateId && selectedTemplateId !== 'blank') {
            const template = templates.find(t => t.id === selectedTemplateId);
            if (template) newState = JSON.parse(JSON.stringify(template.state));
        }
        window.pageState = newState;

        if (selectedTemplateId === 'blank') {
            fullPageSource = '';
            fullPageSourceDerivedFromBuilder = false;
        } else {
            fullPageSource = await generateFullSourceFromLayoutState(newState, getDefaultFullPageSource());
            fullPageSourceDerivedFromBuilder = true;
        }

        closeTemplatePreview();
        const modal = document.getElementById('templateModal');
        if (modal) modal.remove();
        document.getElementById('builderInterface').style.display = 'flex';
        setTimeout(() => {
            renderBuilder(window.pageState);
            scheduleLivePreviewUpdate();
        }, 0);
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', async () => {
        if (!window.builderPermissionContext?.canAccessBuilder) {
            return;
        }

        loadWorkspacePages();

        const hasExisting = <?= json_encode(!empty($initialState)) ?>;
        const isNewRecord = <?= json_encode($model->isNewRecord) ?>;

        // For existing pages (update mode), always show builder with saved state
        // For new pages, show template selector first
        if (isNewRecord && !hasExisting) {
            window.requestAnimationFrame(() => renderTemplates());
        } else if (!isNewRecord) {
            // Update mode: remove template modal if present, show builder
            const modal = document.getElementById('templateModal');
            if (modal) modal.remove();
            const builder = document.getElementById('builderInterface');
            if (builder) builder.style.display = 'flex';
            if (activeCodeScope === 'page' && !fullPageSource.trim()) {
                fullPageSource = await generateFullSourceFromLayoutState(window.pageState, getDefaultFullPageSource());
            }
            // Render builder with saved state from PHP
            console.log('UPDATE MODE - pageState blocks:', window.pageState ? window.pageState.length : 0);
            renderBuilder(window.pageState);
            // Select first block if any exist (component mode only)
            if (activeCodeScope !== 'page' && window.pageState && window.pageState.length > 0) {
                selectBlock(window.pageState[0].id);
            }
        } else {
            // New record with no existing data - show template selector
            window.requestAnimationFrame(() => renderTemplates());
        }

        // Setup drag & drop only when the role can manipulate components.
        const canvas = document.getElementById('canvas');
        if (window.builderPermissionContext?.canDragComponents) {
            document.querySelectorAll('.component-item').forEach(item => {
                item.draggable = true;
                item.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('type', item.dataset.type);
                });
            });

            canvas?.addEventListener('dragover', (e) => {
                if (activeCodeScope === 'page') return;
                e.preventDefault();
                canvas.style.borderColor = '#6366f1';
            });
            canvas?.addEventListener('dragleave', () => {
                canvas.style.borderColor = 'transparent';
            });
            canvas?.addEventListener('drop', (e) => {
                if (activeCodeScope === 'page') return;
                e.preventDefault();
                const type = e.dataTransfer.getData('type');
                if (type) addBlock(type);
            });
        }

        syncCodeScopeUI();
    });

    // Preview Page
    function previewPage() {
        // Show loading state in current window first
        const loadingNotification = document.createElement('div');
        loadingNotification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            z-index: 9999;
            font-size: 14px;
        `;
        loadingNotification.textContent = 'Generating preview...';
        document.body.appendChild(loadingNotification);

        if (activeCodeScope === 'page') {
            const previewWindow = window.open('', '_blank', 'width=1200,height=800');
            if (previewWindow) {
                previewWindow.document.open();
                previewWindow.document.write(injectLinkSafetyIntoHtml(fullPageSource || getDefaultFullPageSource()));
                previewWindow.document.close();
            }
            loadingNotification.remove();
            return;
        }

        // Send AJAX request to generate preview
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const previewUrl = '<?= Url::to(['preview-layout']) ?>';

        fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'layout_json': JSON.stringify(window.pageState),
                    '_csrf-frontend': csrfToken
                })
            })
            .then(response => {
                // Check if response is OK and is JSON
                const contentType = response.headers.get('content-type');
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                if (!contentType || !contentType.includes('application/json')) {
                    // If not JSON, get text and show error
                    return response.text().then(text => {
                        throw new Error('Response is not JSON: ' + (text.substring(0, 100)));
                    });
                }
                return response.json();
            })
            .then(data => {
                // Remove loading notification
                loadingNotification.remove();

                if (!data.success) {
                    throw new Error(data.message || 'Unknown error');
                }

                // Create preview window with the HTML content
                const previewWindow = window.open('', '_blank', 'width=800,height=600');
                previewWindow.document.open();

                previewWindow.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preview Halaman</title>
</head>
<body>
    <div>
        ${injectLinkSafetyIntoHtml(data.html)}
    </div>
</body>
</html>`);

                // Close document
                previewWindow.document.close();
            })
            .catch(error => {
                // Remove loading notification
                loadingNotification.remove();

                alert('Gagal memuat preview: ' + error.message + '\n\nURL: ' + previewUrl);
                console.error('Preview error:', error);
            });
    }

    // MONACO EDITOR LOGIC
    let monacoEditor = null;
    let currentCodeLang = 'html';
    let isSyncingCode = false;
    let previewLoading = false;

    function looksLikeFullHtmlDocument(source) {
        const normalized = (source || '').trim().toLowerCase();
        return normalized.startsWith('<!doctype html') || normalized.startsWith('<html');
    }

    function getDefaultFullPageSource() {
        return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
      margin: 0;
      font-family: Inter, Arial, sans-serif;
      background: #0f172a;
      color: #ffffff;
    }

    .hero {
      padding: 100px 40px;
    }
  </style>
</head>
<body>
  <section class="hero">
    <h1>Hello World</h1>
    <p>Full page source editor aktif.</p>
  </section>

  <script>
    console.log('full page source mode');
  <\/script>
</body>
</html>`;
    }

    function buildFullPageSource(html, css, js) {
        const htmlValue = (html || '').trim();
        if (looksLikeFullHtmlDocument(htmlValue)) {
            return htmlValue;
        }

        const bodyContent = htmlValue || '<div style="padding:32px">Mulai tulis halaman di sini</div>';
        const cssContent = (css || '').trim();
        const jsContent = (js || '').trim();

        return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
${cssContent}
  </style>
</head>
<body>
${bodyContent}
  <script>
${jsContent}
  <\/script>
</body>
</html>`;
    }

    function injectLinkSafetyIntoHtml(source) {
        const html = (source || '').trim();
        if (!html) {
            return html;
        }

        const script = `<script>
(function() {
    function shouldHandle(url) {
        return !!url && !/^(#|javascript:|mailto:|tel:)/i.test(url);
    }

    function openLink(url, target) {
        if (!shouldHandle(url)) {
            return;
        }

        if (target && target !== '_self') {
            window.open(url, target === '_blank' ? '_blank' : target, 'noopener,noreferrer');
            return;
        }

        try {
            if (window.top && window.top !== window) {
                window.top.location.href = url;
            } else {
                window.location.href = url;
            }
        } catch (error) {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    }

    document.addEventListener('click', function(event) {
        var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
        if (!link) return;

        var href = link.getAttribute('href') || '';
        if (!shouldHandle(href)) return;

        event.preventDefault();
        openLink(href, (link.getAttribute('target') || '').toLowerCase());
    }, true);
})();
</script>`;

        if (looksLikeFullHtmlDocument(html)) {
            if (html.toLowerCase().includes('</body>')) {
                return html.replace(/<\/body>/i, script + '\n</body>');
            }
            return html + script;
        }

        return html + script;
    }

    async function generateFullSourceFromLayoutState(state, fallback = '') {
        const normalizedState = Array.isArray(state) ? state : [];
        if (!normalizedState.length) {
            return fallback;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const response = await fetch('<?= Url::to(['preview-layout']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'layout_json': JSON.stringify(normalizedState),
                    '_csrf-frontend': csrfToken
                })
            });
            const data = await response.json();
            if (data && data.success) {
                return buildFullPageSource(data.html || '', '', '');
            }
        } catch (error) {
            console.warn('Failed to generate template full source:', error);
        }

        return fallback;
    }

    if (hasInitialFullPageSource) {
        fullPageSource = buildFullPageSource(initialCustomHtml, initialCustomCss, initialCustomJs);
        if (!fullPageSource.trim()) {
            fullPageSource = '';
        }
    }

    function renderFullPageSourceCanvas() {
        const canvas = document.getElementById('canvas');
        if (!canvas) return;

        if (window.sortableInstance) {
            window.sortableInstance.destroy();
            window.sortableInstance = null;
        }

        canvas.innerHTML = '';
        const wrap = document.createElement('div');
        wrap.style.cssText = 'width:100%;min-height:calc(100vh - 180px);background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;';

        const iframe = document.createElement('iframe');
        iframe.id = 'full-page-source-preview';
        iframe.srcdoc = injectLinkSafetyIntoHtml(fullPageSource || getDefaultFullPageSource());
        iframe.style.cssText = 'width:100%;min-height:calc(100vh - 180px);border:0;display:block;background:#fff;';
        iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation-by-user-activation');

        wrap.appendChild(iframe);
        canvas.appendChild(wrap);
        scheduleLivePreviewUpdate();
    }

    function getPreviewDoc(html) {
        return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html, body { margin: 0; padding: 0; background: #ffffff; }
        body { font-family: Inter, Arial, sans-serif; }
        * { box-sizing: border-box; }
    </style>
</head>
<body>
${injectLinkSafetyIntoHtml(html || '')}
</body>
</html>`;
    }

    // Device Switching for Unified Canvas
    let currentDevice = localStorage.getItem('builder-device') || 'desktop';

    function setDevice(device) {
        currentDevice = device;
        const frame = document.getElementById('main-canvas-frame');
        if (frame) {
            frame.classList.remove('device-desktop', 'device-tablet', 'device-mobile');
            frame.classList.add(`device-${device}`);
        }

        document.querySelectorAll('.device-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.device === device);
        });

        localStorage.setItem('builder-device', device);
    }

    setDevice(currentDevice);

    function setPreviewDevice(mode) {
        setDevice(mode);
    }

    function scheduleLivePreviewUpdate() {
        clearTimeout(window.livePreviewTimer);
        window.livePreviewTimer = setTimeout(() => {
            updateLivePreviewValue();
        }, 250);
    }

    function updateLivePreviewValue() {
        if (previewLoading) return;

        const previewFrame = document.getElementById('live-preview-iframe');
        if (!previewFrame) return;

        if (activeCodeScope === 'page') {
            previewFrame.srcdoc = fullPageSource || getDefaultFullPageSource();
            return;
        }

        previewLoading = true;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        fetch('<?= Url::to(['preview-layout']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'layout_json': JSON.stringify(window.pageState),
                    '_csrf-frontend': csrfToken
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    previewFrame.srcdoc = getPreviewDoc(data.html);
                }
                previewLoading = false;
            })
            .catch(() => {
                previewLoading = false;
            });
    }

    function applyCodeEditorLanguage() {
        if (!monacoEditor) return;
        const model = monacoEditor.getModel();
        if (!model) return;
        const language = activeCodeScope === 'page' ? 'html' : (currentCodeLang === 'js' ? 'javascript' : currentCodeLang);
        monaco.editor.setModelLanguage(model, language);
    }

    function syncCodeScopeUI() {
        document.querySelectorAll('.code-scope-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.scope === activeCodeScope);
        });

        const canvasWrapper = document.querySelector('.canvas-wrapper');
        const canvasFrame = document.getElementById('main-canvas-frame');
        if (canvasWrapper) {
            canvasWrapper.classList.toggle('page-scroll-mode', activeCodeScope === 'page');
            canvasWrapper.classList.toggle('component-scroll-mode', activeCodeScope !== 'page');
        }
        if (canvasFrame) {
            canvasFrame.classList.toggle('component-scroll-mode', activeCodeScope !== 'page');
        }

        const componentTools = document.getElementById('component-code-tools');
        if (componentTools) {
            componentTools.classList.toggle('is-hidden', activeCodeScope === 'page');
        }

        const hint = document.getElementById('code-mode-hint');
        if (hint) {
            hint.textContent = activeCodeScope === 'page' ?
                'Single file editor untuk full halaman (DOCTYPE + head + body + script) dengan render realtime di canvas.' :
                'Edit custom code untuk komponen yang dipilih (HTML/CSS/JS terpisah).';
        }
    }

    async function setCodeScope(scope) {
        activeCodeScope = scope === 'page' ? 'page' : 'component';

        if (activeCodeScope === 'page' && (fullPageSourceDerivedFromBuilder || !fullPageSource.trim())) {
            fullPageSource = await generateFullSourceFromLayoutState(window.pageState, getDefaultFullPageSource());
        }

        syncCodeScopeUI();
        renderBuilder(window.pageState);

        if (document.getElementById('properties-code-tab')?.style.display !== 'none') {
            initMonacoEditor();
        }
    }

    // Tab Switching Logic
    document.querySelectorAll('.prop-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            document.querySelectorAll('.prop-tab-btn').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.style.borderBottomColor = (b === btn) ? '#3b82f6' : 'transparent';
            });

            document.querySelectorAll('.prop-tab-content').forEach(c => {
                c.style.display = c.id === `properties-${tab}-tab` ? 'flex' : 'none';
            });

            if (tab === 'code') {
                initMonacoEditor();
            }
        });
    });

    document.querySelectorAll('.code-scope-btn').forEach(btn => {
        btn.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            setCodeScope(btn.dataset.scope);
        });
    });

    // Code Language Switching Logic
    document.querySelectorAll('.code-lang-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const lang = btn.dataset.lang;
            switchCodeLang(lang);
        });
    });

    function initMonacoEditor() {
        if (monacoEditor) {
            applyCodeEditorLanguage();
            if (activeCodeScope === 'page') {
                loadPageSourceIntoEditor();
            } else {
                loadCodeFromState();
            }
            return;
        }

        require.config({
            paths: {
                vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs'
            }
        });
        require(['vs/editor/editor.main'], function() {
            monacoEditor = monaco.editor.create(document.getElementById('monaco-editor-container'), {
                value: '',
                language: 'html',
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: {
                    enabled: false
                },
                fontSize: 12,
                lineNumbers: 'on',
                scrollBeyondLastLine: false,
                padding: {
                    top: 10
                }
            });

            monacoEditor.onDidChangeModelContent(() => {
                if (isSyncingCode) return;
                if (activeCodeScope === 'page') {
                    updatePageSourceInState();
                } else {
                    updateCodeInState();
                }
            });

            applyCodeEditorLanguage();
            if (activeCodeScope === 'page') {
                loadPageSourceIntoEditor();
            } else {
                loadCodeFromState();
            }
        });
    }

    function loadPageSourceIntoEditor() {
        if (!monacoEditor) return;
        isSyncingCode = true;
        monacoEditor.setValue(fullPageSource || getDefaultFullPageSource());
        isSyncingCode = false;
    }

    function updatePageSourceInState() {
        if (!monacoEditor) return;
        fullPageSource = monacoEditor.getValue() || getDefaultFullPageSource();
        fullPageSourceDerivedFromBuilder = false;
        clearTimeout(window.pageSourceUpdateTimer);
        window.pageSourceUpdateTimer = setTimeout(() => {
            renderBuilder(window.pageState);
            scheduleLivePreviewUpdate();
        }, 350);
    }

    async function syncFullPageSourceFromBuilderState() {
        if (!fullPageSourceDerivedFromBuilder) return;
        fullPageSource = await generateFullSourceFromLayoutState(window.pageState, getDefaultFullPageSource());
        if (activeCodeScope === 'page' && monacoEditor) {
            loadPageSourceIntoEditor();
        }
    }

    function scheduleFullPageSourceSyncFromBuilder() {
        if (!fullPageSourceDerivedFromBuilder) return;
        clearTimeout(window.fullPageSourceSyncTimer);
        window.fullPageSourceSyncTimer = setTimeout(() => {
            syncFullPageSourceFromBuilderState();
        }, 300);
    }

    function loadCodeFromState() {
        if (!monacoEditor || !selectedBlockId) return;

        const block = window.pageState.find(b => b.id === selectedBlockId);
        if (!block) return;

        isSyncingCode = true;
        const codeKey = `custom${currentCodeLang.charAt(0).toUpperCase()}${currentCodeLang.slice(1)}`;
        let code = block.props[codeKey];

        if (!code && COMPONENT_BASE_CODE[block.type]) {
            let baseCode = COMPONENT_BASE_CODE[block.type][currentCodeLang] || '';
            if (baseCode) {
                const props = block.props || {};
                baseCode = baseCode
                    .replace(/{id}/g, block.id)
                    .replace(/{text}/g, props.text || 'Teks')
                    .replace(/{content}/g, props.content || 'Konten')
                    .replace(/{title}/g, props.title || 'Judul')
                    .replace(/{src}/g, props.src || '')
                    .replace(/{alt}/g, props.alt || 'Image')
                    .replace(/{url}/g, props.url || '')
                    .replace(/{action}/g, props.action || '/submit');
            }
            code = baseCode;
        }

        monacoEditor.setValue(code || '');
        isSyncingCode = false;
    }

    function updateCodeInState() {
        if (!monacoEditor || !selectedBlockId) return;

        const block = window.pageState.find(b => b.id === selectedBlockId);
        if (!block) return;

        const code = monacoEditor.getValue();
        const codeKey = `custom${currentCodeLang.charAt(0).toUpperCase()}${currentCodeLang.slice(1)}`;
        block.props[codeKey] = code;

        clearTimeout(window.codeUpdateTimer);
        window.codeUpdateTimer = setTimeout(() => {
            renderBuilder(window.pageState);
            scheduleLivePreviewUpdate();
            scheduleFullPageSourceSyncFromBuilder();
        }, 500);
    }

    const originalSelectBlock = window.selectBlock;
    window.selectBlock = function(blockId) {
        if (originalSelectBlock) originalSelectBlock(blockId);
        if (document.getElementById('properties-code-tab') &&
            document.getElementById('properties-code-tab').style.display !== 'none' &&
            activeCodeScope !== 'page') {
            loadCodeFromState();
        }
    };

    function switchCodeLang(lang) {
        currentCodeLang = lang;
        document.querySelectorAll('.code-lang-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.lang === lang);
            btn.style.background = btn.classList.contains('active') ? '#3b82f6' : 'transparent';
            btn.style.color = btn.classList.contains('active') ? 'white' : '#94a3b8';
            btn.style.borderColor = btn.classList.contains('active') ? '#3b82f6' : '#475569';
        });
        if (monacoEditor && activeCodeScope !== 'page') {
            applyCodeEditorLanguage();
            loadCodeFromState();
        }
    }

    function resetBaseCode() {
        if (!selectedBlockId || activeCodeScope === 'page') return;
        const block = window.pageState.find(b => b.id === selectedBlockId);
        if (!block) return;

        if (confirm('Reset semua custom code komponen ke base template?')) {
            delete block.props.customHtml;
            delete block.props.customCss;
            delete block.props.customJs;
            loadCodeFromState();
            renderBuilder(window.pageState);
        }
    }

    // Modify renderBlockContent to support custom code with iframe sandbox
    const originalRenderBlockContent = renderBlockContent;
    window.renderBlockContent = function(block) {
        const props = block.props || {};

        if (props.customHtml || props.customCss || props.customJs) {
            const id = `custom-wrap-${block.id}`;
            const srcDoc = `
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { margin: 0; padding: 0; font-family: sans-serif; overflow: hidden; }
                        ${props.customCss || ''}
                    </style>
                </head>
                <body>
                    <div id="root">${props.customHtml || ''}</div>
                    <script>
                        (function() {
                            try {
                                const container = document.getElementById('root');
                                ${props.customJs || ''}
                            } catch (e) { console.error(e); }
                        })();
                        
                        // Auto-resize parent iframe
                        function updateHeight() {
                            window.parent.postMessage({
                                type: 'resize',
                                blockId: '${block.id}',
                                height: document.documentElement.scrollHeight
                            }, '*');
                        }
                        window.onload = updateHeight;
                        new ResizeObserver(updateHeight).observe(document.body);
                        <\/script>
                </body>
                </html>
            `.replace(/"/g, '&quot;');

            return `
                <div class="iframe-container" style="min-height: 50px;">
                    <iframe 
                        id="iframe-${block.id}"
                        srcdoc="${srcDoc}"
                        style="width: 100%; border: none; overflow: hidden; display: block; pointer-events: none;"
                        sandbox="allow-scripts allow-popups allow-top-navigation-by-user-activation"
                    ></iframe>
                </div>
            `;
        }

        return originalRenderBlockContent(block);
    };

    // Message handler for iframe resizing
    window.addEventListener('message', (e) => {
        if (e.data && e.type === 'resize' && e.data.blockId) {
            const iframe = document.getElementById(`iframe-${e.data.blockId}`);
            if (iframe) {
                iframe.style.height = e.data.height + 'px';
            }
        }
    });
</script>

<!-- Builder Toolbar -->
<div class="builder-toolbar" style="<?= $canAccessActions ? '' : 'display:none;' ?>">
    <div class="builder-toolbar-title">
        <span class="material-symbols-outlined">dashboard</span>
        Dynamic Page Builder
    </div>
    <div class="builder-toolbar-actions">
        <?php if ($canAccessActions): ?>
            <button class="btn-preview" onclick="previewPage()">
                <span class="material-symbols-outlined">visibility</span>
                Preview
            </button>
            <button class="btn-save" onclick="savePage()">
                <span class="material-symbols-outlined">save</span>
                Simpan
            </button>
        <?php endif; ?>
    </div>
</div>

<div id="saveDialogOverlay" class="save-dialog-overlay" onclick="handleSaveDialogOverlay(event)">
    <div class="save-dialog">
        <div class="save-dialog-header">
            <h3 class="save-dialog-title">Simpan Halaman</h3>
        </div>
        <div class="save-dialog-body">
            <label class="save-dialog-label" for="saveTitleInput">Judul Halaman</label>
            <input id="saveTitleInput" class="save-dialog-input" type="text" maxlength="255" autocomplete="off" />
        </div>
        <div class="save-dialog-actions">
            <button type="button" class="save-dialog-btn" onclick="closeSaveDialog()">Batal</button>
            <button type="button" class="save-dialog-btn primary" onclick="submitSavePage()">Simpan</button>
        </div>
    </div>
</div>

<!-- Warning Modal for Button URL Validation -->
<div id="warningModalOverlay" class="warning-modal-overlay">
    <div class="warning-modal">
        <div class="warning-modal-header">
            <div class="warning-modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="warning-modal-title">Peringatan URL Kosong</h3>
                <p class="warning-modal-subtitle">Beberapa button belum memiliki URL yang valid</p>
            </div>
        </div>
        <div class="warning-modal-body">
            <div id="warningModalList" class="warning-modal-list"></div>
        </div>
        <div class="warning-modal-footer">
            <button type="button" class="warning-modal-btn warning-modal-btn-cancel" onclick="closeWarningModal()">Perbaiki Dulu</button>
            <button type="button" class="warning-modal-btn warning-modal-btn-proceed" id="warningModalProceedBtn">Tetap Simpan</button>
        </div>
    </div>
</div>

<script>
    // Save Page
    const defaultPageTitle = <?= json_encode((string) ($model->title ?? '')) ?>;
    
    // Check for saveError flag in URL to reopen dialog if there was an error
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('saveError') === '1') {
        // Remove the param so it doesn't persist
        window.history.replaceState({}, '', window.location.pathname + window.location.hash);
        // Open save dialog after a short delay to ensure page is fully loaded
        setTimeout(function() {
            openSaveDialog(true); // true = restore from sessionStorage
        }, 100);
    }

    function openSaveDialog(restoreFromStorage = false) {
        const overlay = document.getElementById('saveDialogOverlay');
        const input = document.getElementById('saveTitleInput');
        if (!overlay || !input) return;

        // Try to restore from sessionStorage if this is a retry after error
        if (restoreFromStorage) {
            const savedTitle = sessionStorage.getItem('pageBuilderPendingTitle');
            input.value = savedTitle || defaultPageTitle || '';
        } else {
            input.value = defaultPageTitle || '';
        }
        
        overlay.classList.add('open');
        setTimeout(() => {
            input.focus();
            input.select();
        }, 20);
    }

    function closeSaveDialog() {
        const overlay = document.getElementById('saveDialogOverlay');
        const input = document.getElementById('saveTitleInput');
        if (overlay) overlay.classList.remove('open');
        // Clear stored title on close
        sessionStorage.removeItem('pageBuilderPendingTitle');
    }

    function handleSaveDialogOverlay(event) {
        if (event.target && event.target.id === 'saveDialogOverlay') {
            closeSaveDialog();
        }
    }

    function validateBeforeSave() {
        // URL validation disabled - always return empty array
        return [];
    }

    function showWarningModal(buttonsWithoutUrl, onProceed) {
        // Disabled
    }

    function closeWarningModal() {
        // Disabled
    }

    function submitSavePage() {
        // Skip URL validation - proceed directly to save
        doSubmitSavePage();
    }
    
    function doSubmitSavePage() {
        const input = document.getElementById('saveTitleInput');
        const title = (input?.value || '').trim();
        if (!title) {
            if (input) input.focus();
            return;
        }
        
        // Save title to sessionStorage so we can restore if there's an error
        sessionStorage.setItem('pageBuilderPendingTitle', title);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $model->isNewRecord ? Url::to(['dynamic-create']) : Url::to(['dynamic-update', 'id' => $model->id]) ?>';

        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf-frontend';
            input.value = csrf.getAttribute('content');
            form.appendChild(input);
        }

        const titleInput = document.createElement('input');
        titleInput.type = 'hidden';
        titleInput.name = 'MasterPage[title]';
        titleInput.value = title;
        form.appendChild(titleInput);

        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'MasterPage[layout_json]';
        contentInput.value = JSON.stringify(window.pageState);
        form.appendChild(contentInput);

        const pageTypeInput = document.createElement('input');
        pageTypeInput.type = 'hidden';
        pageTypeInput.name = 'MasterPage[page_type]';
        pageTypeInput.value = activeCodeScope === 'page' ? PAGE_TYPE_CUSTOM_CODE : PAGE_TYPE_BUILDER;
        form.appendChild(pageTypeInput);

        const useCustomInput = document.createElement('input');
        useCustomInput.type = 'hidden';
        useCustomInput.name = 'MasterPage[use_page_custom_code]';
        useCustomInput.value = activeCodeScope === 'page' ? '1' : '0';
        form.appendChild(useCustomInput);

        const customHtmlInput = document.createElement('textarea');
        customHtmlInput.name = 'MasterPage[custom_html]';
        customHtmlInput.style.display = 'none';
        customHtmlInput.value = activeCodeScope === 'page' ? (fullPageSource || '').trim() : '';
        form.appendChild(customHtmlInput);

        const pageCustomHtmlInput = document.createElement('textarea');
        pageCustomHtmlInput.name = 'MasterPage[page_custom_html]';
        pageCustomHtmlInput.style.display = 'none';
        pageCustomHtmlInput.value = customHtmlInput.value;
        form.appendChild(pageCustomHtmlInput);

        const customCssInput = document.createElement('input');
        customCssInput.type = 'hidden';
        customCssInput.name = 'MasterPage[custom_css]';
        customCssInput.value = '';
        form.appendChild(customCssInput);

        const pageCustomCssInput = document.createElement('input');
        pageCustomCssInput.type = 'hidden';
        pageCustomCssInput.name = 'MasterPage[page_custom_css]';
        pageCustomCssInput.value = '';
        form.appendChild(pageCustomCssInput);

        const customJsInput = document.createElement('input');
        customJsInput.type = 'hidden';
        customJsInput.name = 'MasterPage[custom_js]';
        customJsInput.value = '';
        form.appendChild(customJsInput);

        const pageCustomJsInput = document.createElement('input');
        pageCustomJsInput.type = 'hidden';
        pageCustomJsInput.name = 'MasterPage[page_custom_js]';
        pageCustomJsInput.value = '';
        form.appendChild(pageCustomJsInput);

        // Don't close dialog - let form submission handle it
        // If there's an error, the dialog will stay open
        document.body.appendChild(form);
        form.submit();
    }

    function savePage() {
        openSaveDialog();
    }

    document.addEventListener('keydown', (event) => {
        const overlay = document.getElementById('saveDialogOverlay');
        if (!overlay || !overlay.classList.contains('open')) return;

        if (event.key === 'Escape') {
            closeSaveDialog();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            submitSavePage();
        }
    });
</script>

<script>
    (() => {
        const svg = (content) => `
            <svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                ${content}
            </svg>`;
        const filled = (content) => `
            <svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false" fill="currentColor">
                ${content}
            </svg>`;

        const icons = {
            add: svg('<path d="M12 5v14"/><path d="M5 12h14"/>'),
            arrow_back: svg('<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>'),
            chevron_left: svg('<path d="m15 18-6-6 6-6"/>'),
            content_copy: svg('<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>'),
            dashboard: svg('<rect x="3" y="3" width="7" height="8" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="15" width="7" height="6" rx="1"/>'),
            database: svg('<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>'),
            delete: svg('<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/>'),
            description: svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/>'),
            drag_indicator: svg('<path d="M9 5h.01"/><path d="M15 5h.01"/><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M9 19h.01"/><path d="M15 19h.01"/>'),
            dynamic_form: svg('<path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h10"/><rect x="4" y="3" width="16" height="18" rx="2"/>'),
            expand_more: svg('<path d="m6 9 6 6 6-6"/>'),
            folder_open: svg('<path d="M3 7h6l2 2h10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M3 7V5a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v2"/>'),
            grid_view: svg('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'),
            horizontal_rule: svg('<path d="M4 12h16"/>'),
            image: svg('<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="10" r="2"/><path d="m21 16-5-5L5 19"/>'),
            list_alt: svg('<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>'),
            logout: svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>'),
            notes: svg('<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/>'),
            person: svg('<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 14.5-4 16 0"/>'),
            save: svg('<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>'),
            settings: svg('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2 3.4-.2-.1a1.7 1.7 0 0 0-2 .2 1.7 1.7 0 0 0-.6 1.5H9a1.7 1.7 0 0 0-.6-1.5 1.7 1.7 0 0 0-2-.2l-.2.1-2-3.4.1-.1A1.7 1.7 0 0 0 4.6 15 1.7 1.7 0 0 0 3 14V10a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2-3.4.2.1a1.7 1.7 0 0 0 2-.2A1.7 1.7 0 0 0 9 2h6a1.7 1.7 0 0 0 .6 1.5 1.7 1.7 0 0 0 2 .2l.2-.1 2 3.4-.1.1a1.7 1.7 0 0 0-.3 1.9A1.7 1.7 0 0 0 21 10v4a1.7 1.7 0 0 0-1.6 1z"/>'),
            smart_button: svg('<rect x="3" y="7" width="18" height="10" rx="3"/><path d="M8 12h8"/>'),
            space_bar: svg('<path d="M4 17h16"/><path d="M4 17v-4"/><path d="M20 17v-4"/>'),
            square: svg('<rect x="5" y="5" width="14" height="14" rx="2"/>'),
            table_chart: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M9 4v16"/><path d="M15 4v16"/>'),
            title: svg('<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>'),
            touch_app: svg('<path d="M9 11V5a2 2 0 0 1 4 0v6"/><path d="M13 10v-1a2 2 0 0 1 4 0v3"/><path d="M17 12v-1a2 2 0 0 1 4 0v3c0 5-3 8-8 8h-1a5 5 0 0 1-4-2l-4-5a2 2 0 0 1 3-3l2 2"/>'),
            videocam: svg('<rect x="3" y="6" width="12" height="12" rx="2"/><path d="m15 10 6-4v12l-6-4z"/>'),
            view_column: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="M15 4v16"/>'),
            view_stream: svg('<rect x="4" y="5" width="16" height="5" rx="1"/><rect x="4" y="14" width="16" height="5" rx="1"/>'),
            visibility: svg('<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>'),
            widgets: svg('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M17.5 14v7"/><path d="M14 17.5h7"/>'),
            category: svg('<circle cx="7" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/>')
        };

        const applyMaterialIconFallback = (root = document) => {
            root.querySelectorAll('.material-symbols-outlined').forEach((el) => {
                if (el.dataset.iconFallbackApplied === '1' || el.querySelector('svg')) {
                    return;
                }

                const name = el.textContent.trim();
                if (!name) {
                    return;
                }

                el.dataset.iconName = name;
                el.setAttribute('aria-label', name.replace(/_/g, ' '));
                el.innerHTML = icons[name] || icons.category;
                el.dataset.iconFallbackApplied = '1';
            });
        };

        window.applyMaterialIconFallback = applyMaterialIconFallback;

        const materialFontAvailable = () => {
            const probeText = 'view_stream';
            const probe = document.createElement('span');
            probe.textContent = probeText;
            probe.style.cssText = 'position:absolute;left:-9999px;top:-9999px;font-size:24px;line-height:1;white-space:nowrap;visibility:hidden;';

            const fallbackProbe = probe.cloneNode(true);
            fallbackProbe.style.fontFamily = 'Arial, sans-serif';

            const iconProbe = probe.cloneNode(true);
            iconProbe.style.fontFamily = '"Material Symbols Outlined", Arial, sans-serif';
            iconProbe.style.fontFeatureSettings = '"liga"';

            document.body.appendChild(fallbackProbe);
            document.body.appendChild(iconProbe);

            const fallbackWidth = fallbackProbe.getBoundingClientRect().width;
            const iconWidth = iconProbe.getBoundingClientRect().width;

            fallbackProbe.remove();
            iconProbe.remove();

            return iconWidth > 0 && fallbackWidth > 0 && iconWidth < fallbackWidth * 0.8;
        };

        const runIfNeeded = () => {
            if (!materialFontAvailable()) {
                applyMaterialIconFallback();
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runIfNeeded);
        } else {
            runIfNeeded();
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(runIfNeeded).catch(runIfNeeded);
        }

        window.setTimeout(runIfNeeded, 1200);
    })();
</script>
