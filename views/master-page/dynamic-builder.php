<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use app\models\Form;

/**
 * @var \app\models\MasterPage $model
 */

$this->title = $model->isNewRecord ? 'Buat Halaman Baru' : 'Edit Halaman: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Halaman', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Parse layout_json from model - handle both array and JSON string formats
// CRITICAL: Use ONLY direct DB query to avoid MasterPage __get() infinite recursion bug
$initialState = [];
$rawJson = null;

try {
    // Direct DB query to get raw layout_json value (bypasses ActiveRecord __get/__set issues)
    $db = \Yii::$app->db;
    $tableName = $model::tableName();
    $modelId = (int)$model->id;

    $sql = "SELECT layout_json FROM {$tableName} WHERE id = :id";
    $cmd = $db->createCommand($sql, [':id' => $modelId]);
    $rawJson = $cmd->queryScalar();
    $debugInfo['rawJsonFromDb'] = $rawJson;
    $debugInfo['rawJsonLength'] = is_string($rawJson) ? strlen($rawJson) : (is_null($rawJson) ? -1 : 9999);
} catch (\Exception $e) {
    $rawJson = null;
    $debugInfo['error'] = $e->getMessage();
}

if ($rawJson !== null && $rawJson !== '') {
    if (is_array($rawJson)) {
        $initialState = $rawJson;
    } elseif (is_string($rawJson)) {
        // html_entity_decode first because the value might have been HTML-encoded through json_encode
        $decodedRaw = html_entity_decode($rawJson, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Try direct decode first
        $decoded = json_decode($decodedRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $initialState = $decoded;
        } else {
            // Try double-decode (JSON was double-encoded)
            $doubleDecoded = json_decode($decodedRaw, true);
            if (is_array($doubleDecoded)) {
                $initialState = $doubleDecoded;
            } else {
                // Try stripping slashes then decode
                $stripped = stripslashes($decodedRaw);
                $third = @json_decode($stripped, true);
                if (is_array($third)) {
                    $initialState = $third;
                } else {
                    // Final fallback: try decoding the original raw value
                    $fourth = @json_decode($rawJson, true);
                    if (is_array($fourth)) {
                        $initialState = $fourth;
                    }
                }
            }
        }
    }
}
// Normalize: ensure state is always an array of blocks
if (!is_array($initialState)) {
    $initialState = [];
}
// Guarantee array of blocks (re-index)
$initialState = array_values($initialState);
$availableForms = Form::find()->where(['user_id' => Yii::$app->user->id])->all();
$formsList = [];
foreach ($availableForms as $form) {
    $formsList[$form->id] = ['id' => $form->id, 'name' => $form->name, 'storage' => $form->storage_type];
}

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_END]);
$this->registerCssFile('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css');

$fieldTypes = [
    ['value' => 'input', 'label' => 'Input Text'],
    ['value' => 'email', 'label' => 'Email'],
    ['value' => 'textarea', 'label' => 'Textarea'],
    ['value' => 'select', 'label' => 'Dropdown'],
    ['value' => 'checkbox', 'label' => 'Checkbox'],
    ['value' => 'radio', 'label' => 'Radio'],
    ['value' => 'date', 'label' => 'Date'],
    ['value' => 'number', 'label' => 'Number'],
    ['value' => 'file', 'label' => 'File Upload']
];
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
                    <button class="btn-preview" onclick="startBlankTemplate()">
                        <i class="ti ti-file-plus"></i> Mulai Kosong
                    </button>
                    <button class="btn-save" onclick="confirmTemplate()" id="templateUseBtn" disabled>
                        <i class="ti ti-check"></i> Pakai Template
                    </button>
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
                    <button class="btn-save" onclick="selectPreviewTemplate()">
                        <i class="ti ti-check"></i> Pilih Template Ini
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
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
        overflow-y: auto;
        padding: 32px;
        background: #ffffff;
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
        padding: 24px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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
<div class="page-builder" id="builderInterface" style="<?= ($model->isNewRecord && empty($initialState)) ? 'display:none;' : '' ?>">
    <!-- LEFT PANEL: Component Library -->
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
        <div class="component-item" data-type="form">
            <span class="material-symbols-outlined">dynamic_form</span>
            <span>Form Builder</span>
        </div>
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

    <!-- CANVAS: Main Area -->
    <div class="builder-canvas">
        <div id="canvas" class="canvas-drop-zone">
            <p style="text-align: center; color: #94a3b8; padding: 60px 20px;">
                <span style="font-size:48px;display:block;margin-bottom:16px">🎨</span>
                Drag komponen dari panel kiri ke sini<br>
                atau klik untuk menambah komponen
            </p>
        </div>
    </div>

    <!-- RIGHT PANEL: Properties -->
    <div class="builder-properties">
        <div class="prop-header">
            <span class="material-symbols-outlined">settings</span>
            Properties
        </div>
        <div id="properties-panel">
            <div class="no-selection">
                <span class="material-symbols-outlined">touch_app</span>
                <p style="font-size:14px">Pilih komponen untuk edit</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Ensure template modal handlers exist before inline onclick attributes execute.
    // This avoids runtime errors like: "confirmTemplate is not defined".
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
            url: '#',
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

    window.pageState = <?= json_encode($initialState) ?>;
    let selectedBlockId = null;
    let isAddingBlock = false;

    function generateId() {
        return 'block-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    function renderBuilder(state) {
        const canvas = document.getElementById('canvas');
        canvas.innerHTML = '';

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
        }
        window.sortableInstance = new Sortable(canvas, {
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

    function createBlockElement(block) {
        const div = document.createElement('div');
        div.className = 'builder-block';
        div.dataset.id = block.id;
        div.dataset.type = block.type;
        if (block.id === selectedBlockId) div.classList.add('selected');

        div.innerHTML = `
        <div class="block-actions">
            <button class="block-action-btn move" title="Move"><span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8">drag_indicator</span></button>
            <button class="block-action-btn duplicate" title="Duplicate"><span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8">content_copy</span></button>
            <button class="block-action-btn delete" title="Delete"><span class="material-symbols-outlined" style="font-size:16px">delete</span></button>
        </div>
        <div class="block-content" onclick="selectBlock('${block.id}')">
            ${renderBlockContent(block)}
        </div>
    `;

        div.querySelector('.delete').addEventListener('click', (e) => {
            e.stopPropagation();
            deleteBlock(block.id);
        });

        div.querySelector('.duplicate').addEventListener('click', (e) => {
            e.stopPropagation();
            duplicateBlock(block);
        });

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
                return `<div style="text-align:${props.align || 'center'};padding:12px"><button style="background:${colors[style]};color:white;border:none;border-radius:8px;padding:${sizes[props.size || 'md']};cursor:pointer;font-weight:600;font-size:14px;width:${props.fullWidth ? '100%' : 'auto'}">${props.text || 'Button'}</button></div>`;
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
            case 'section':
                return `<div style="padding:${props.padding || '40'}px;margin:${props.margin || '0'}px;background:${props.background || '#fff'};border-radius:8px;border:1px dashed #cbd5e1;color:#94a3b8;text-align:center;">📦 Section</div>`;
            default:
                return `<div style="padding:16px;background:#fef3c7;color:#92400e;">Unknown: ${block.type}</div>`;
        }
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
                html += `<div class="prop-section">
                <div class="prop-section-title">🔘 Teks & Link</div>
                <div class="prop-group">
                    <label>Teks Tombol</label>
                    <input type="text" class="prop-input" value="${props.text || ''}" onchange="updateProp('${blockId}', 'text', this.value)">
                </div>
                <div class="prop-group">
                    <label>URL / Link</label>
                    <input type="text" class="prop-input" value="${props.url || ''}" onchange="updateProp('${blockId}', 'url', this.value)">
                    <small>Contoh: https://example.com atau /page/path</small>
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

            default:
                html += '<div class="no-selection"><p>Tidak ada properties untuk komponen ini</p></div>';
        }

        panel.innerHTML = html;
    }

    function updateProp(blockId, key, value) {
        const block = window.pageState.find(b => b.id === blockId);
        if (block) {
            block.props[key] = value;
            renderBuilder(window.pageState);
            renderProperties(blockId);
        }
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
                        url: '#',
                        style: 'primary',
                        size: 'lg'
                    }
                },
                {
                    id: 'b2',
                    type: 'button',
                    props: {
                        text: 'Pelajari Lebih Lanjut',
                        url: '#',
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
                        url: '#',
                        style: 'primary'
                    }
                },
                {
                    id: 'b2',
                    type: 'button',
                    props: {
                        text: 'Lihat Demo',
                        url: '#',
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
                        url: '#',
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
                        url: '#',
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
                        content: 'Kami mengumpulkan informasi yang Anda berikan secara sukarela saat menggunakan layanan kami.',
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
                <div class="template-card ${selected ? 'selected' : ''}" onclick="selectTemplate('${template.id}')" ondblclick="openTemplatePreview('${template.id}')">
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
        updateTemplateSelectionState();
        renderTemplates();
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

    function confirmTemplate() {
        let newState = [];
        if (selectedTemplateId && selectedTemplateId !== 'blank') {
            const template = templates.find(t => t.id === selectedTemplateId);
            if (template) newState = JSON.parse(JSON.stringify(template.state));
        }
        window.pageState = newState;
        closeTemplatePreview();
        const modal = document.getElementById('templateModal');
        if (modal) modal.remove();
        document.getElementById('builderInterface').style.display = 'flex';
        setTimeout(() => renderBuilder(window.pageState), 0);
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        const hasExisting = <?= json_encode(!empty($initialState)) ?>;
        const isNewRecord = <?= json_encode($model->isNewRecord) ?>;

        // For existing pages (update mode), always show builder with saved state
        // For new pages, show template selector first
        if (isNewRecord && !hasExisting) {
            renderTemplates();
        } else if (!isNewRecord) {
            // Update mode: remove template modal if present, show builder
            const modal = document.getElementById('templateModal');
            if (modal) modal.remove();
            const builder = document.getElementById('builderInterface');
            if (builder) builder.style.display = 'flex';
            // Render builder with saved state from PHP
            console.log('UPDATE MODE - pageState blocks:', window.pageState ? window.pageState.length : 0);
            renderBuilder(window.pageState);
            // Select first block if any exist
            if (window.pageState && window.pageState.length > 0) {
                selectBlock(window.pageState[0].id);
            }
        } else {
            // New record with no existing data - show template selector
            renderTemplates();
        }

        // Setup drag & drop
        document.querySelectorAll('.component-item').forEach(item => {
            item.draggable = true;
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('type', item.dataset.type);
            });
        });

        const canvas = document.getElementById('canvas');
        canvas?.addEventListener('dragover', (e) => {
            e.preventDefault();
            canvas.style.borderColor = '#6366f1';
        });
        canvas?.addEventListener('dragleave', () => {
            canvas.style.borderColor = 'transparent';
        });
        canvas?.addEventListener('drop', (e) => {
            e.preventDefault();
            const type = e.dataTransfer.getData('type');
            if (type) addBlock(type);
        });
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
        ${data.html}
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

    // Save Page
    function savePage() {
        const title = prompt('Judul Halaman:', <?= json_encode($model->title ?? '') ?>);
        if (!title) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $model->isNewRecord ? Url::to(['dynamic-create']) : Url::to(['dynamic-update', 'id' => $model->id]) ?>';

        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            const input = document.createElement('input');
            input.name = '_csrf-frontend';
            input.value = csrf.getAttribute('content');
            form.appendChild(input);
        }

        const titleInput = document.createElement('input');
        titleInput.name = 'MasterPage[title]';
        titleInput.value = title;
        form.appendChild(titleInput);

        const contentInput = document.createElement('input');
        contentInput.name = 'MasterPage[layout_json]';
        contentInput.value = JSON.stringify(window.pageState);
        form.appendChild(contentInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>

<!-- Builder Toolbar -->
<div class="builder-toolbar">
    <div class="builder-toolbar-title">
        <span class="material-symbols-outlined">dashboard</span>
        Dynamic Page Builder
    </div>
    <div class="builder-toolbar-actions">
        <button class="btn-preview" onclick="previewPage()">
            <span class="material-symbols-outlined">visibility</span>
            Preview
        </button>
        <button class="btn-save" onclick="savePage()">
            <span class="material-symbols-outlined">save</span>
            Simpan
        </button>
    </div>
</div>