# Dynamic Page Builder - Update Mode Fix

## Problem Statement
Ketika user membuka halaman UPDATE (`/master-page/dynamic-update?id=6`), canvas builder tampil kosong meskipun sebelumnya sudah ada layout, template, dan component yang tersimpan.

## Root Causes Identified

1. **Toolbar terpisah dari builder interface** - Toolbar ada di luar container, menyebabkan CSS layout rusak
2. **Initialization logic incomplete** - Mode UPDATE tidak menjalankan `renderBuilder()` dengan benar
3. **Container visibility logic tidak konsisten** - Builder interface bisa hidden saat mode UPDATE
4. **No state restoration** - Tidak ada logika untuk restore component saat UPDATE mode

## Solution Implemented

### 1. Restructured HTML Layout
**Before:**
```html
<!-- Toolbar di luar -->
<div class="builder-toolbar">...</div>
<div class="page-builder" id="builderInterface" style="display:none;">...</div>
```

**After:**
```html
<!-- Wrapper container -->
<div class="builder-container" id="builderContainer">
    <!-- Toolbar di dalam -->
    <div class="builder-toolbar">...</div>
    <!-- Builder interface -->
    <div class="page-builder" id="builderInterface">...</div>
</div>
```

### 2. Updated CSS Layout
```css
/* Wrapper for entire builder */
.builder-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #ffffff;
}

.builder-toolbar {
    height: 56px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    flex-shrink: 0;
}

.page-builder {
    height: calc(100vh - 56px);
    display: flex;
    background: #0f172a;
    overflow: hidden;
    flex: 1;
}
```

### 3. Enhanced Initialization Logic
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const hasExisting = <?= json_encode(!empty($model->layout_json)) ?>;
    const builderContainer = document.getElementById('builderContainer');
    const templateModal = document.getElementById('templateModal');
    
    if (!hasExisting) {
        // Mode CREATE - Show template selector
        if (templateModal) templateModal.style.display = 'flex';
        if (builderContainer) builderContainer.style.display = 'none';
        renderTemplates();
    } else {
        // Mode UPDATE - Show builder with existing state
        if (templateModal) templateModal.remove();
        if (builderContainer) builderContainer.style.display = 'flex';
        
        // Ensure canvas is rendered with current page state
        renderBuilder(window.pageState);
        
        // If there are blocks, select the first one
        if (window.pageState && window.pageState.length > 0) {
            selectBlock(window.pageState[0].id);
        }
    }
    
    // ... rest of initialization
});
```

### 4. Updated confirmTemplate Function
```javascript
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
    const container = document.getElementById('builderContainer');
    if (container) container.style.display = 'flex';
    setTimeout(() => renderBuilder(window.pageState), 0);
}
```

## How It Works

### CREATE Flow
1. User visits `/master-page/dynamic-create`
2. `isNewRecord` = true
3. Initialization detects `layout_json` is empty
4. **Result:** Template modal shown, builder hidden

### UPDATE Flow
1. User visits `/master-page/dynamic-update?id=6`
2. `isNewRecord` = false
3. `layout_json` loaded from database
4. `initialState` decoded into `window.pageState`
5. Initialization detects existing `layout_json`
6. **Result:** Builder shown with all components rendered

## State Management

### Initial State Setup
```php
$initialState = !empty($model->layout_json) 
    ? json_decode($model->layout_json, true) 
    : [];
```

This is embedded in the view:
```javascript
window.pageState = <?= json_encode($initialState) ?>;
```

### Update on Canvas Change
When user adds/removes/modifies components, `window.pageState` is updated in memory.

### Save to Database
When user clicks "Simpan":
```javascript
const contentInput = document.createElement('input');
contentInput.name = 'MasterPage[layout_json]';
contentInput.value = JSON.stringify(window.pageState);  // Entire state saved
form.appendChild(contentInput);
```

## Testing Checklist

- [ ] CREATE: Template selector appears on new page
- [ ] CREATE: Can select template and see components render
- [ ] CREATE: Can add more components after template
- [ ] CREATE: Save creates new record with layout_json
- [ ] UPDATE: Open existing page with saved layout
- [ ] UPDATE: Canvas shows all previously saved components
- [ ] UPDATE: Can add more components to existing layout
- [ ] UPDATE: Can delete components from layout
- [ ] UPDATE: Save updates the same record
- [ ] UPDATE: Properties panel shows correct component properties
- [ ] UPDATE: Drag & drop still works
- [ ] UPDATE: Component duplication works
- [ ] Preview: Preview button generates correct HTML

## Expected Behavior After Fix

### Before Fix
```
UPDATE Page Opens → Canvas Empty → No Components Visible
```

### After Fix
```
UPDATE Page Opens → Canvas Populated → All Components Visible
                  → Properties Editable
                  → Can Continue Editing
```

## Browser Console Tips

If components still don't show:

```javascript
// Check current state
console.log('Page State:', window.pageState);

// Check container visibility
console.log('Container visible:', document.getElementById('builderContainer').style.display);

// Manually trigger render
renderBuilder(window.pageState);

// Check if state is empty
console.log('State empty?', window.pageState.length === 0);
```

## Files Modified

- `views/master-page/dynamic-builder.php`
  - Moved toolbar inside builder-container
  - Added CSS for .builder-container wrapper
  - Enhanced initialization logic
  - Updated confirmTemplate function

## Related Files

- `controllers/MasterPageController.php` - Handles CREATE and UPDATE actions
- `models/MasterPage.php` - Model definition with layout_json attribute
- `views/master-page/update.php` - Update page view (uses _form.php)
- `views/master-page/create.php` - Create page view (uses _form.php)
- `views/master-page/_form.php` - Common form rendering

## Troubleshooting

### Canvas still empty on UPDATE?
1. Check browser console for JavaScript errors
2. Verify `layout_json` is not empty in database
3. Check if `initialState` is being passed correctly to JavaScript
4. Ensure `renderBuilder()` is being called

### Toolbar not showing?
1. Check if `builderContainer` div is visible
2. Verify CSS `.builder-toolbar` is loaded
3. Check for JavaScript errors preventing initialization

### Components not interacting?
1. Verify drag & drop listeners are attached
2. Check if `Sortable.js` library is loaded
3. Ensure `addBlock()`, `selectBlock()`, etc. functions are defined

## Next Steps (If Issues Persist)

1. Add console logging to initialization:
```javascript
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Ready');
    console.log('hasExisting:', hasExisting);
    console.log('pageState:', window.pageState);
    // ... rest of code
});
```

2. Test with simpler layouts first (just 1 component)

3. Check that `layout_json` in database is valid JSON

4. Verify all JavaScript functions exist before calling
