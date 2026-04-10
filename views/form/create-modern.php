<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Create Form - Visual Builder';

// Register FontAwesome for icons
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

// Register SortableJS from CDN
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="form-builder-container">
    <!-- LEFT SIDEBAR: Field Types -->
    <div class="form-builder-sidebar animate-slide-in-left">
        <div class="sidebar-header">
            <i class="fas fa-cube"></i> Field Types
        </div>
        
        <div class="sidebar-search">
            <input type="text" class="form-control" id="fieldSearch" placeholder="Search fields...">
        </div>

        <div class="field-types">
            <!-- Text Fields -->
            <div class="field-type" draggable="true" data-field-type="text" title="Single line text field">
                <i class="fas fa-font"></i> Text
            </div>
            
            <div class="field-type" draggable="true" data-field-type="number" title="Numeric input">
                <i class="fas fa-hashtag"></i> Number
            </div>
            
            <div class="field-type" draggable="true" data-field-type="email" title="Email address">
                <i class="fas fa-envelope"></i> Email
            </div>

            <div class="field-type" draggable="true" data-field-type="textarea" title="Multi-line text">
                <i class="fas fa-align-left"></i> Textarea
            </div>

            <!-- Selection Fields -->
            <div class="field-type" draggable="true" data-field-type="select" title="Dropdown menu">
                <i class="fas fa-list"></i> Select
            </div>

            <div class="field-type" draggable="true" data-field-type="checkbox" title="Multiple checkboxes">
                <i class="fas fa-check-square"></i> Checkbox
            </div>

            <div class="field-type" draggable="true" data-field-type="radio" title="Radio buttons">
                <i class="fas fa-circle"></i> Radio
            </div>

            <!-- Date & File -->
            <div class="field-type" draggable="true" data-field-type="date" title="Date picker">
                <i class="fas fa-calendar"></i> Date
            </div>

            <div class="field-type" draggable="true" data-field-type="file" title="File upload">
                <i class="fas fa-paperclip"></i> File
            </div>
        </div>
    </div>

    <!-- CENTER CANVAS: Form Preview -->
    <div class="form-builder-canvas">
        <div class="form-preview" data-form-type="builder">
            <div class="empty-canvas">
                <div class="empty-canvas-icon">✨</div>
                <div class="empty-canvas-text">Start Building Your Form</div>
                <div class="empty-canvas-hint">Drag field types from the left to create your form</div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form schema input -->
<?php $form = ActiveForm::begin(['id' => 'form-builder-form', 'method' => 'post']); ?>

    <!-- Form name input (visible) -->
    <div class="d-none">
        <?= $form->field($model, 'name')->textInput(['placeholder' => 'Form Name'])->label(false); ?>
        <?= $form->field($model, 'storage_type')->hiddenInput(['value' => 'json'])->label(false); ?>
        <input type="hidden" id="form-schema" name="FormSchema" value="[]">
    </div>

    <!-- Submit button at top -->
    <div style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 12px;">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= \yii\helpers\Url::to(['form/index']) ?>'">
            <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Form
        </button>
    </div>

<?php ActiveForm::end(); ?>

<style>
/* Override body height for full viewport form builder */
.body-content {
    padding: 0 !important;
}

main {
    padding: 0 !important;
}

body {
    background: linear-gradient(135deg, #f0f4ff 0%, #fff5e6 100%) !important;
}

/* Top Header Bar */
.form-builder-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    z-index: 1000;
}

.form-builder-header h1 {
    color: white;
    font-size: 18px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Smooth animations on load */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-builder-container {
    animation: slideInUp 0.5s ease-out;
}
</style>

<script>
// Initialize form builder UI
document.addEventListener('DOMContentLoaded', function() {
    // Setup form name input
    const formNameInput = document.querySelector('input[name="Form[name]"]');
    if (formNameInput) {
        formNameInput.addEventListener('change', () => {
            updateFormSchema();
        });
    }

    // Setup field search
    const fieldSearch = document.getElementById('fieldSearch');
    if (fieldSearch) {
        fieldSearch.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.field-type').forEach(field => {
                const text = field.textContent.toLowerCase();
                field.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Auto-save form schema on changes
    const formPreview = document.querySelector('.form-preview');
    if (formPreview) {
        new MutationObserver(() => {
            updateFormSchema();
        }).observe(formPreview, {
            childList: true,
            subtree: true,
            attributes: true
        });
    }
});

function updateFormSchema() {
    const fields = document.querySelectorAll('.form-field');
    const schema = Array.from(fields).map(field => {
        const labelInput = field.querySelector('input[placeholder*="Field Label"]');
        const typeEl = field.querySelector('input[placeholder*="text"], textarea, select, input[type="number"], input[type="email"], input[type="date"]');
        
        return {
            id: field.dataset.fieldId,
            type: getFieldType(field),
            label: labelInput ? labelInput.value : 'Field',
            required: field.querySelector('input[type="checkbox"]')?.checked || false,
        };
    });

    const schemaInput = document.getElementById('form-schema');
    schemaInput.value = JSON.stringify(schema);
}

function getFieldType(field) {
    const types = ['text', 'number', 'email', 'date', 'file'];
    for (let type of types) {
        if (field.querySelector(`input[type="${type}"]`)) {
            return type;
        }
    }
    if (field.querySelector('textarea')) return 'textarea';
    if (field.querySelector('select')) return 'select';
    if (field.querySelector('input[type="checkbox"]')) return 'checkbox';
    if (field.querySelector('input[type="radio"]')) return 'radio';
    return 'text';
}
</script>
