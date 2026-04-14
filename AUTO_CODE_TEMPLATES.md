# 🎨 Auto-Generate Code Templates Feature

## 📋 Overview
Saat block ditambahkan ke canvas (via drag-drop atau klik), sistem akan **otomatis generate** template kode HTML, CSS, dan JavaScript khusus untuk block tersebut dan menampilkannya di panel **Custom UI/UX**.

---

## ✨ Cara Kerja

### 1. **Tambah Block → Auto-Generate Code** 🚀
Ketika Anda menambahkan block ke canvas:
1. Block ditambahkan via klik atau drag-drop
2. Sistem otomatis mendeteksi tipe block
3. Template code khusus di-generate sesuai tipe block
4. Code editor di panel Custom UI/UX otomatis terisi

### 2. **Smart Fill Behavior** 🧠
- **Editor kosong**: Langsung isi dengan template
- **Editor ada isi**: Muncul konfirmasi "Append atau Skip?"
  - Klik **OK** → Template di-APPEND ke code yang sudah ada
  - Klik **Cancel** → Skip, tidak menambah code

### 3. **Visual Feedback** 💫
- Notification muncul: "✓ Code template for [Block] generated!"
- Tab Custom UI/UX berkedip dengan gradient purple selama 2 detik
- User tahu bahwa code sudah berhasil di-generate

---

## 📦 Template yang Tersedia

### Input Fields

#### 1. **Text Input**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">Label</label>
    <input type="text" id="{fieldname}" name="{fieldname}" class="form-control" placeholder="..." required>
    <small class="form-text text-muted">Helper text</small>
</div>
```

**CSS Features:**
- Hover effect dengan shadow dan translateY
- Focus ring dengan warna primary
- Smooth transitions
- Responsive padding

**JavaScript Features:**
- Real-time character count
- Border color change saat ada value
- Focus/blur scale animation

---

#### 2. **Textarea**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">Label</label>
    <textarea id="{fieldname}" name="{fieldname}" class="form-control" rows="4" placeholder="..."></textarea>
</div>
```

**CSS Features:**
- Resize vertical support
- Focus ring styling
- Clean border design

**JavaScript Features:**
- Auto-resize berdasarkan content
- Character count tracking

---

#### 3. **Email Input**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">Email</label>
    <input type="email" id="{fieldname}" name="{fieldname}" class="form-control" placeholder="example@email.com" required>
</div>
```

**CSS Features:**
- Invalid state styling (red border)
- Focus ring
- Clean design

**JavaScript Features:**
- **Email validation** dengan regex
- Real-time validation feedback
- Border color change (green jika valid, red jika invalid)
- Custom validity messages

---

#### 4. **Number Input**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">Number</label>
    <input type="number" id="{fieldname}" name="{fieldname}" class="form-control" min="0" max="100" required>
</div>
```

**CSS Features:**
- Clean input design
- Focus states

**JavaScript Features:**
- **Min/Max validation**
- Border color feedback
- Custom validity messages
- Real-time validation

---

### Selection Fields

#### 5. **Checkbox**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label class="form-label">Label</label>
    <div class="checkbox-group">
        <label class="checkbox-item">
            <input type="checkbox" name="{fieldname}[]" value="Option 1">
            <span class="checkbox-label">Option 1</span>
        </label>
        <!-- More options -->
    </div>
</div>
```

**CSS Features:**
- Flexbox layout
- Hover effects
- Custom accent color (#4f46e5)
- Smooth transitions

**JavaScript Features:**
- Track selected values
- Scale animation saat checked
- Console logging untuk debugging

---

#### 6. **Radio**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label class="form-label">Label</label>
    <div class="radio-group">
        <label class="radio-item">
            <input type="radio" name="{fieldname}" value="Option 1" checked>
            <span class="radio-label">Option 1</span>
        </label>
    </div>
</div>
```

**CSS Features:**
- Clean radio design
- Hover states
- Accent color customization

**JavaScript Features:**
- Track selected value
- **Highlight animation** saat berubah
- Console logging

---

#### 7. **Select/Dropdown**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">Label</label>
    <select id="{fieldname}" name="{fieldname}" class="form-control">
        <option value="">-- Select an option --</option>
        <option value="Option 1">Option 1</option>
    </select>
</div>
```

**CSS Features:**
- Custom select styling
- Focus ring
- Option padding

**JavaScript Features:**
- Track selected value
- Scale animation on change
- Console logging

---

### Special Fields

#### 8. **File Upload**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">File Upload</label>
    <div class="file-upload-wrapper">
        <input type="file" id="{fieldname}" name="{fieldname}" class="form-control" required>
        <div class="file-upload-preview"></div>
    </div>
</div>
```

**CSS Features:**
- **Dashed border** (upload area design)
- Hover color change
- Background color on hover
- Preview area styling

**JavaScript Features:**
- **File info preview** (nama, size)
- Beautiful file info card
- Console logging
- Auto preview injection

---

#### 9. **Date Picker**
**HTML Structure:**
```html
<div class="form-group form-field-{fieldname}">
    <label for="{fieldname}">Date</label>
    <input type="date" id="{fieldname}" name="{fieldname}" class="form-control" required>
</div>
```

**CSS Features:**
- Clean date input design
- Focus states

**JavaScript Features:**
- **Auto set min date** ke today
- Date formatting
- Scale animation on change
- Console logging dengan format tanggal

---

### Content Blocks

#### 10. **Heading**
**HTML Structure:**
```html
<div class="form-heading form-field-{fieldname}">
    <h2 class="heading-text">Your Heading</h2>
</div>
```

**CSS Features:**
- Dynamic font size (berdasarkan level h1/h2/h3)
- Text alignment support
- Clean typography

**JavaScript Features:**
- **Fade-in animation** saat load
- translateY animation
- Smooth appearance

---

#### 11. **Image**
**HTML Structure:**
```html
<div class="form-image form-field-{fieldname}">
    <img src="..." alt="..." class="image-wrapper">
    <p class="image-caption">Caption here</p>
</div>
```

**CSS Features:**
- Border radius
- Box shadow
- **Hover zoom effect**
- Caption styling

**JavaScript Features:**
- **Lazy loading effect** (fade-in)
- Click to zoom cursor
- Console logging

---

## 🎯 Cara Menggunakan

### Metode 1: Drag & Drop
```
1. Drag block dari panel kiri
2. Drop ke canvas
3. ✅ Code otomatis di-generate!
4. Klik tab "Custom UI/UX" di panel kanan
5. Lihat HTML, CSS, dan JS sudah terisi
6. Edit sesuai kebutuhan
```

### Metode 2: Klik Block
```
1. Klik block di panel kiri
2. Block ditambahkan ke canvas
3. ✅ Code otomatis di-generate!
4. Buka tab "Custom UI/UX"
5. Template sudah ada di editor
```

### Metode 3: Multi-Block
```
1. Tambah block pertama → Code generated
2. Tambah block kedua → Muncul konfirmasi "Append?"
3. Klik OK untuk append
4. Klik Cancel untuk skip
5. Semua code block bisa dikumulatif!
```

---

## 💡 Contoh Penggunaan

### Contoh 1: Form Contact Sederhana
**Langkah:**
1. Tambah block "Text Input" (label: "Nama")
2. Tambah block "Email Input" (label: "Email")
3. Tambah block "Textarea" (label: "Pesan")

**Hasil:**
- HTML untuk ketiga field sudah ada
- CSS styling sudah include hover/focus effects
- JavaScript validation untuk email sudah ada
- Tinggal customize warna, font, dll

### Contoh 2: Form dengan File Upload
**Langkah:**
1. Tambah block "File Upload"
2. Code otomatis generate dengan:
   - HTML structure yang benar
   - CSS dashed border design
   - JavaScript file preview functionality

**Hasil:**
```javascript
// Sudah include:
- File info preview (nama, size)
- Beautiful card design
- Console logging
- Error handling
```

### Contoh 3: Form dengan Validasi
**Langkah:**
1. Tambah block "Email Input"
2. Tambah block "Number Input"

**Hasil:**
- Email validation dengan regex sudah aktif
- Number min/max validation sudah aktif
- Border color feedback (green/red) sudah jalan
- Custom validity messages sudah ada

---

## 🔧 Block yang Sudah Support Auto-Generate

✅ Text Input  
✅ Textarea  
✅ Email Input  
✅ Number Input  
✅ Checkbox  
✅ Radio  
✅ Select/Dropdown  
✅ File Upload  
✅ Date Picker  
✅ Heading  
✅ Image  

🔄 **Generic Template** (untuk block lain):
- Semua block yang belum punya template khusus
- Akan dapat generic template dengan struktur dasar
- Tetap bisa di-customize penuh

---

## 🎨 Custom Template Structure

Setiap template punya 3 bagian:

### 1. HTML Template
```html
<!-- Comment dengan nama block -->
<div class="form-group form-field-{unique_class}">
    <!-- Semantic HTML structure -->
    <!-- Accessible labels dan IDs -->
    <!-- Placeholder support -->
    <!-- Required attribute support -->
</div>
```

### 2. CSS Template
```css
/* Comment dengan nama block */
.form-field-{unique_class} {
    /* Layout */
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
    
    /* Effects */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.form-field-{unique_class}:hover {
    /* Hover effects */
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.form-field-{unique_class} .form-control:focus {
    /* Focus ring */
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
```

### 3. JavaScript Template
```javascript
// Comment dengan nama block
document.addEventListener('DOMContentLoaded', function() {
    const element = document.getElementById('{fieldname}');
    
    if (element) {
        // Event listeners
        element.addEventListener('input/change', function() {
            // Validation logic
            // Visual feedback
            // Console logging
        });
        
        // Animations
        // Utility functions
    }
});
```

---

## 🚀 Features per Template

### All Templates Include:
✅ **Unique class** untuk setiap block (`form-field-{fieldname}`)  
✅ **Accessible HTML** (labels, IDs, semantics)  
✅ **Responsive design**  
✅ **Hover effects**  
✅ **Focus states**  
✅ **Smooth transitions**  
✅ **Console logging** untuk debugging  
✅ **Validation** (jika applicable)  
✅ **Animations** (jika relevant)  

### Special Features:
- **Email**: Regex validation, real-time feedback
- **Number**: Min/max validation
- **File**: Preview card dengan file info
- **Date**: Auto min-date setting
- **Checkbox/Radio**: Multi-value tracking
- **Image**: Lazy loading, zoom effect
- **Heading**: Fade-in animation

---

## 📊 Smart Behavior

### Saat Editor Kosong:
```
User drag block "Email Input"
↓
Code editors langsung terisi:
- Custom CSS: Email styles
- Custom HTML (Before): Email HTML structure
- Custom JS: Email validation script
↓
Notification: "✓ Code template for Email generated!"
```

### Saat Editor Ada Isi:
```
User sudah punya code di editor
↓
User drag block "Number Input"
↓
Confirmation muncul:
"✨ Auto-generate code for Number Input?

Click OK to APPEND to existing code
Click Cancel to skip"
↓
User klik OK → Code di-APPEND
User klik Cancel → Code tidak ditambah
```

---

## 💾 Persistence

### During Editing:
- Generated code otomatis masuk ke editor
- Tersimpan di localStorage saat klik "Save Custom Design"
- Bisa di-edit manual setelah generate

### After Save:
- Code tersimpan di database (schema_js column)
- Permanent dan tidak hilang
- Akan load saat form dibuka lagi

---

## 🐛 Troubleshooting

### Code tidak muncul?
✅ Pastikan klik tab "Custom UI/UX" saat tambah block  
✅ Cek notification apakah muncul  
✅ Coba drag block baru  

### Code menimpa yang lama?
✅ Sistem akan konfirmasi dulu sebelum append  
✅ Klik Cancel jika tidak ingin append  
✅ Code lama tetap aman  

### Generic template muncul?
✅ Block tersebut belum punya template khusus  
✅ Tetap bisa di-customize penuh  
✅ Template generic include struktur dasar  

### Editor error?
✅ Cek browser console untuk errors  
✅ Pastikan JavaScript enabled  
✅ Refresh page dan coba lagi  

---

## 🎓 Tips & Best Practices

### 1. **Urutan Penambahan Block**
```
Recommended:
1. Tambah semua block dulu
2. Buka Custom UI/UX tab
3. Edit code satu per satu
4. Save design

Atau:
1. Tambah block satu per satu
2. Edit code setiap block
3. Save design di akhir
```

### 2. **Kustomisasi Code**
```
Generated code adalah STARTING POINT
- Edit warna, font, spacing
- Tambah custom logic
- Hapus yang tidak perlu
- Combine dengan code block lain
```

### 3. **Performance**
```
- Generated code sudah optimized
- Minimal overhead
- Clean structure
- Easy to customize
```

### 4. **Testing**
```
- Selalu klik "Apply & Preview Design"
- Lihat hasil di preview modal
- Test interactions (validation, animations)
- Save jika sudah puas
```

---

## 🎉 Summary

Dengan fitur Auto-Generate Code Templates:

✅ **Tidak perlu mulai dari nol** - Template sudah ada  
✅ **Tidak perlu bingung struktur HTML** - Sudah di-generate  
✅ **Tidak perlu takut salah CSS** - Sudah include best practices  
✅ **Tidak perlu khawatir JS error** - Sudah include validation  
✅ **Smart append behavior** - Tidak overwrite code lama  
✅ **Visual feedback** - Notification dan animations  
✅ **Professional code** - Clean, accessible, responsive  

**User bisa langsung fokus ke CUSTOMIZE, bukan mulai dari nol!** 🚀

---

## 📞 Support

Jika ada masalah:
1. Cek browser console
2. Pastikan semua block support feature ini
3. Clear cache dan localStorage
4. Refresh page

**Happy Coding!** 💻✨
