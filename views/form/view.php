<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var app\models\FormSubmission[] $submissions */

use yii\bootstrap5\Html;

$this->title = 'Form: ' . $model->name;

// Styles for dashboard layout
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap');
?>

<!-- Tailwind CSS v3 -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'on-surface': '#0b1c30',
                    'on-surface-variant': '#464555',
                    'surface': '#e5e9f0',
                    'surface-container-lowest': '#ffffff',
                    'surface-container-low': '#eff4ff',
                    'surface-container': '#e5eeff',
                    'surface-container-high': '#dce9ff',
                    'primary-container': '#4f46e5',
                    'primary': '#3525cd',
                    'secondary': '#006c49',
                    'tertiary': '#7e3000',
                    'surface-tint': '#4d44e3',
                    'outline-variant': '#c7c4d8',
                    'outline': '#777587',
                    'error': '#ba1a1a',
                },
                fontFamily: {
                    'headline': ['Manrope'],
                    'body': ['Inter'],
                }
            }
        }
    }
</script>

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>

<body class="bg-surface font-body text-on-surface">

    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-[#e5e9f0]/70 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]">
        <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-full gap-3 min-w-[320px]">
            <span class="material-symbols-outlined text-outline text-[20px]">search</span>
            <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline/60" placeholder="Search forms, analytics, or users..." type="text" />
        </div>
        <div class="flex items-center gap-4">
            <button class="notification-button material-symbols-outlined text-on-surface-variant hover:bg-slate-100 p-2 rounded-full transition-colors">notifications</button>
            <div class="h-8 w-px bg-outline-variant/30"></div>
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create New Form', ['form/create'], [
                'class' => 'bg-primary-container text-white px-6 py-2.5 rounded-full font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
            ]) ?>
            <div class="flex items-center gap-3 pl-4">
                <img alt="User profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDVAIMYF86keTHH4bWWsnc6u7upwBRSEk3FvJFjB4wyGvQxjTaWJjqAU9nx7f2YPKnoZKb-kP7pNyFHtjhAggRn0nsW21oGtSNUI1fZH-ddxHG35QnLC24RHVvAReNpdG-gdZoInGl6MMsS4OZcWCyLWu52BmaCzV6K8eTFMqMQMQ_HGc2dgYa6qxFdVSNOiJlHTjCPfpP1KqoRirRptx2ymuk_BKJrEm5kDTAWz-vU_e_4_Qeu3S25BXTLNpFN1WxftL-z6kTgLw" />
            </div>
        </div>
    </nav>

    <?= $this->render('../layouts/_sidebar', ['activeMenu' => 'forms']) ?>

    <!-- Main Content -->
    <main class="pl-64 pt-6 min-h-screen">
        <div class="max-w-[1400px] mx-auto px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="<?= \yii\helpers\Url::to(['form/index']) ?>" class="text-on-surface-variant hover:text-on-surface transition-colors">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </a>
                        <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight"><?= Html::encode($model->name) ?></h1>
                    </div>
                    <p class="text-on-surface-variant font-medium">View form details and submissions.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="openPublishModal()" class="bg-secondary text-white px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">public</span> Publish
                    </button>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">content_copy</span> Duplicate', ['form/duplicate', 'id' => $model->id], [
                        'class' => 'bg-surface-container text-on-surface px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all active:scale-95 text-sm no-underline',
                        'data' => [
                            'confirm' => 'Duplicate this form with all fields?',
                            'method' => 'post',
                        ]
                    ]) ?>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">edit</span> Edit', ['form/update', 'id' => $model->id], [
                        'class' => 'bg-surface-container text-on-surface px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all active:scale-95 text-sm no-underline'
                    ]) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Form Details -->
                <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                    <div class="p-8 border-b border-surface-container-low">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">info</span>
                            <h2 class="text-xl font-bold font-headline">Form Details</h2>
                        </div>
                    </div>
                    <div class="p-8">
                        <table class="w-full">
                            <tr class="border-b border-surface-container-low">
                                <td class="py-4 text-sm font-medium text-on-surface-variant w-40">Form Name</td>
                                <td class="py-4 text-sm font-semibold"><?= Html::encode($model->name) ?></td>
                            </tr>
                            <tr class="border-b border-surface-container-low">
                                <td class="py-4 text-sm font-medium text-on-surface-variant">Created At</td>
                                <td class="py-4 text-sm font-semibold"><?= date('M d, Y H:i:s', strtotime($model->created_at)) ?></td>
                            </tr>
                            <tr class="border-b border-surface-container-low">
                                <td class="py-4 text-sm font-medium text-on-surface-variant">Updated At</td>
                                <td class="py-4 text-sm font-semibold"><?= date('M d, Y H:i:s', strtotime($model->updated_at)) ?></td>
                            </tr>
                            <tr class="border-b border-surface-container-low">
                                <td class="py-4 text-sm font-medium text-on-surface-variant">Total Fields</td>
                                <td class="py-4"><span class="bg-primary-container/10 text-primary-container px-3 py-1 rounded-full text-xs font-bold"><?= count($model->getSchema()) ?></span></td>
                            </tr>
                            <tr>
                                <td class="py-4 text-sm font-medium text-on-surface-variant">Total Submissions</td>
                                <td class="py-4"><span class="bg-secondary/10 text-secondary px-3 py-1 rounded-full text-xs font-bold"><?= count($submissions) ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Form Schema -->
                <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                    <div class="p-8 border-b border-surface-container-low">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">list</span>
                            <h2 class="text-xl font-bold font-headline">Form Schema</h2>
                        </div>
                    </div>
                    <div class="p-8">
                        <?php if (empty($model->getSchema())): ?>
                            <p class="text-on-surface-variant text-center py-8">No fields defined.</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php $counter = 1; foreach ($model->getSchema() as $field): ?>
                                    <?php
                                    $fieldLabel = $field['label'] ?? ($field['type'] ?? 'Field ' . $counter);
                                    $fieldName = $field['name'] ?? '-';
                                    $fieldType = $field['type'] ?? 'unknown';
                                    ?>
                                    <div class="flex items-center justify-between p-4 bg-surface-container rounded-xl">
                                        <div class="flex items-center gap-3">
                                            <span class="w-8 h-8 bg-primary-container/10 rounded-lg flex items-center justify-center text-primary-container text-sm font-bold"><?= $counter ?></span>
                                            <div>
                                                <p class="text-sm font-semibold"><?= Html::encode($fieldLabel) ?></p>
                                                <p class="text-xs text-on-surface-variant"><code><?= Html::encode($fieldName) ?></code></p>
                                            </div>
                                        </div>
                                        <span class="bg-surface-container-high text-on-surface-variant px-3 py-1 rounded-full text-xs font-medium"><?= Html::encode($fieldType) ?></span>
                                    </div>
                                    <?php $counter++; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Submissions -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-surface-tint" style="font-variation-settings: 'FILL' 1;">inbox</span>
                        <h2 class="text-xl font-bold font-headline">Recent Submissions</h2>
                    </div>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">list</span> View All Submissions', ['form/submissions', 'id' => $model->id], [
                        'class' => 'bg-primary-container/10 text-primary-container px-4 py-2 rounded-xl text-sm font-bold hover:bg-primary-container/20 transition-colors no-underline flex items-center gap-2'
                    ]) ?>
                </div>
                <div class="p-8">
                    <?php if (empty($submissions)): ?>
                        <p class="text-on-surface-variant text-center py-8">No submissions yet.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-surface-container-low">
                                        <th class="py-4 text-[10px] font-bold uppercase tracking-widest text-outline">ID</th>
                                        <th class="py-4 text-[10px] font-bold uppercase tracking-widest text-outline">Submitted At</th>
                                        <th class="py-4 text-[10px] font-bold uppercase tracking-widest text-outline text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-container-low">
                                    <?php foreach (array_slice($submissions, 0, 5) as $submission): ?>
                                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                                            <td class="py-4 text-sm font-medium"><?= $submission->id ?></td>
                                            <td class="py-4 text-sm text-on-surface-variant"><?= date('M d, Y H:i:s', strtotime($submission->created_at)) ?></td>
                                            <td class="py-4 text-right">
                                                <?= Html::a('<span class="material-symbols-outlined text-[18px]">visibility</span> View', ['form/submissions', 'id' => $model->id], [
                                                    'class' => 'text-primary-container font-bold text-sm hover:underline no-underline flex items-center gap-1 justify-end'
                                                ]) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Publish Modal -->
    <?php
    $csrfToken = Yii::$app->request->csrfToken;
    $csrfParam = Yii::$app->request->csrfParam;
    $baseUrl = getenv('APP_URL');
    if (!$baseUrl) {
        $railwayDomain = getenv('RAILWAY_PUBLIC_DOMAIN') ?: getenv('RAILWAY_STATIC_URL');
        if ($railwayDomain) {
            $baseUrl = preg_match('/^https?:\/\//i', $railwayDomain) ? $railwayDomain : 'https://' . $railwayDomain;
        }
    }
    if (!$baseUrl) {
        $baseUrl = Yii::$app->request->hostInfo;
    }
    $baseUrl = rtrim($baseUrl, '/');
    $defaultPublicUrl = $baseUrl . '/form/public-render/' . $model->id;
    ?>
    <div id="publish-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;max-width:600px;width:90%;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <!-- Modal Header -->
            <div style="background:linear-gradient(135deg,#006c49,#00a773);color:#fff;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                <h3 style="margin:0;font-size:18px;display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined">public</span>
                    <span id="modal-title">Publish Form</span>
                </h3>
                <button onclick="closePublishModal()" style="background:transparent;border:none;color:#fff;cursor:pointer;font-size:24px;padding:4px;">&times;</button>
            </div>

            <!-- Modal Body -->
            <div style="padding:24px;">
                <!-- Before Publish -->
                <div id="before-publish">
                    <form id="publish-form-modal" method="post" action="<?= \yii\helpers\Url::to(['form/publish', 'id' => $model->id]) ?>">
                        <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-weight:600;margin-bottom:8px;color:#0b1c30;">Published Name</label>
                            <input type="text" name="name" value="<?= Html::encode($model->name) ?>" maxlength="255" required
                                style="width:100%;padding:12px 16px;border:1px solid #c7c4d8;border-radius:12px;font-size:14px;transition:border 0.2s;"
                                placeholder="Enter published form name..."
                                onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#c7c4d8'">
                        </div>
                        <div style="background:#e5eeff;border-left:4px solid #4f46e5;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
                            <p style="margin:0;font-size:13px;color:#464555;"><strong>Note:</strong> This will publish your form and make it accessible via a public URL that you can share with others.</p>
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:12px;">
                            <button type="button" onclick="closePublishModal()"
                                style="padding:12px 24px;background:#f0f4f9;border:none;border-radius:12px;font-size:14px;font-weight:600;color:#464555;cursor:pointer;">Cancel</button>
                            <button type="button" id="btn-submit-publish" onclick="handlePublish()"
                                style="padding:12px 24px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                                <span class="material-symbols-outlined" style="font-size:18px;">public</span>
                                Publish Now
                            </button>
                        </div>
                    </form>
                </div>

                <!-- After Publish (Success State) -->
                <div id="after-publish" style="display:none;">
                    <div style="text-align:center;margin-bottom:24px;">
                        <div style="width:64px;height:64px;background:#d1f2eb;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                            <span class="material-symbols-outlined" style="font-size:36px;color:#198754;">check_circle</span>
                        </div>
                        <h4 style="margin:0 0 8px;font-size:20px;color:#0b1c30;">Form Published Successfully!</h4>
                        <p style="margin:0;font-size:14px;color:#464555;">Your form is now live and accessible via the link below.</p>
                    </div>

                    <!-- Public Link Box -->
                    <div style="background:#f8fafd;border:2px solid #e8eef7;border-radius:12px;padding:16px;margin-bottom:20px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#464555;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Public Form Link</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="text" id="public-link-input" readonly
                                value="<?= Html::encode($defaultPublicUrl) ?>"
                                style="flex:1;padding:10px 14px;border:1px solid #c7c4d8;border-radius:8px;font-size:13px;background:#fff;color:#0b1c30;font-family:monospace;">
                            <button onclick="copyPublicLink(event)"
                                style="padding:10px 16px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                                <span class="material-symbols-outlined" style="font-size:16px;">content_copy</span>
                                Copy
                            </button>
                        </div>
                    </div>

                    <!-- QR Code Section -->
                    <div style="background:#f8fafd;border:2px solid #e8eef7;border-radius:12px;padding:16px;margin-bottom:20px;text-align:center;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#464555;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">QR Code</label>
                        <div id="qrcode" style="display:inline-block;padding:16px;background:#fff;border-radius:12px;border:1px solid #e8eef7;"></div>
                        <p style="margin:12px 0 0;font-size:12px;color:#464555;">Scan this QR code to access the form</p>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display:flex;gap:12px;">
                        <a href="<?= Html::encode($defaultPublicUrl) ?>" id="open-form-link" target="_blank"
                            style="flex:1;padding:12px 24px;background:linear-gradient(135deg,#006c49,#00a773);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;text-align:center;">
                            <span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span>
                            Open Form
                        </a>
                        <button onclick="closePublishModal()"
                            style="flex:1;padding:12px 24px;background:#f0f4f9;border:none;border-radius:12px;font-size:14px;font-weight:600;color:#464555;cursor:pointer;">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QRCode.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <script>
        function openPublishModal() {
            console.log('=== OPEN PUBLISH MODAL ===');
            document.getElementById('publish-modal').style.display = 'flex';
            document.getElementById('before-publish').style.display = 'block';
            document.getElementById('after-publish').style.display = 'none';
            document.getElementById('modal-title').textContent = 'Publish Form';
        }

        function closePublishModal() {
            document.getElementById('publish-modal').style.display = 'none';
        }

        function copyPublicLink(event) {
            const input = document.getElementById('public-link-input');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">check</span> Copied!';
                btn.style.background = 'linear-gradient(135deg,#198754,#20c997)';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = 'linear-gradient(135deg,#4f46e5,#6366f1)';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy link. Please copy manually.');
            });
        }

        function handlePublish() {
            console.log('=== HANDLE PUBLISH CALLED ===');

            const form = document.getElementById('publish-form-modal');
            const btn = document.getElementById('btn-submit-publish');
            const nameInput = form.querySelector('input[name="name"]');
            const name = nameInput.value.trim();

            if (!name) {
                alert('Please enter a name for the published form.');
                nameInput.focus();
                return;
            }

            const originalBtnHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">sync</span> Publishing...';

            const actionUrl = form.getAttribute('action');
            const csrfParam = '<?= $csrfParam ?>';
            const csrfToken = '<?= $csrfToken ?>';

            console.log('Action URL:', actionUrl);
            console.log('Form name:', name);

            // Get current form schema from PHP
            const currentSchema = <?= json_encode($model->schema_js) ?>;
            const schemaData = JSON.parse(currentSchema || '{}');
            const pages = schemaData.pages || [];
            const blocks = schemaData.blocks || [];

            console.log('Schema from database:', schemaData);
            console.log('CustomDesign from DB:', schemaData.customDesign);

            // Try to get customDesign from localStorage first (most recent)
            // Initialize with empty object
            let customDesign = {};
            
            // Check if database has customDesign (handle both array and object)
            if (schemaData.customDesign) {
                if (Array.isArray(schemaData.customDesign)) {
                    // Database has empty array, ignore it
                    console.log('Database has empty array for customDesign, ignoring');
                } else if (schemaData.customDesign.css || schemaData.customDesign.htmlBefore || 
                           schemaData.customDesign.htmlAfter || schemaData.customDesign.js) {
                    // Database has actual custom design content
                    customDesign = schemaData.customDesign;
                    console.log('Using customDesign from database');
                }
            }
            
            const localStorageKey = 'formCustomDesign_<?= $model->id ?>';
            const newFormKey = 'formCustomDesign_new'; // Fallback for newly created forms
            let savedDesign = localStorage.getItem(localStorageKey);

            console.log('localStorage key:', localStorageKey);
            console.log('Value from localStorage:', savedDesign);

            // If not found, check for new form key
            if (!savedDesign) {
                console.log('⚠ Not found in localStorage, checking for new form key...');
                savedDesign = localStorage.getItem(newFormKey);
                console.log('New form key value:', savedDesign);
                
                if (savedDesign) {
                    console.log('✅ Found design from new form creation! Will migrate to form ID key.');
                    // Migrate to form ID key
                    localStorage.setItem(localStorageKey, savedDesign);
                    localStorage.removeItem(newFormKey);
                    console.log('✅ Migrated localStorage key from new to form ID');
                }
            }

            if (savedDesign) {
                try {
                    const parsedDesign = JSON.parse(savedDesign);
                    console.log('Parsed localStorage design:', parsedDesign);
                    // Use localStorage design if it has any content
                    if (parsedDesign.css || parsedDesign.htmlBefore || parsedDesign.htmlAfter || parsedDesign.js) {
                        customDesign = parsedDesign;
                        console.log('✅ Using customDesign from localStorage (overriding database)');
                    } else {
                        console.log('⚠ localStorage has design but no content');
                    }
                } catch (e) {
                    console.error('Failed to parse localStorage design:', e);
                }
            } else {
                console.log('⚠ No customDesign in localStorage');
            }

            console.log('Final customDesign to be published:', JSON.parse(JSON.stringify(customDesign)));

            // Prepare form_pages data with custom design
            const formPagesData = {
                pages: pages.length > 0 ? pages : [{
                    id: 'page_1',
                    name: 'Page 1',
                    blocks: blocks
                }],
                customDesign: customDesign
            };

            console.log('=== PUBLISH DATA ===');
            console.log('Custom Design being sent:', JSON.parse(JSON.stringify(customDesign)));
            console.log('Pages data:', JSON.parse(JSON.stringify(formPagesData)));

            const formData = new FormData();
            formData.append('name', name);
            formData.append(csrfParam, csrfToken);
            formData.append('form_pages', JSON.stringify(formPagesData));

            fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Content-Type:', response.headers.get('content-type'));
                    return response.text();
                })
                .then(text => {
                    console.log('=== RAW RESPONSE ===');
                    console.log(text);

                    try {
                        const data = JSON.parse(text);
                        console.log('=== PARSED RESPONSE ===');
                        console.log(data);

                        if (data.success) {
                            console.log('=== SHOWING SUCCESS STATE ===');
                            // Show success state
                            document.getElementById('before-publish').style.display = 'none';
                            document.getElementById('after-publish').style.display = 'block';
                            document.getElementById('modal-title').textContent = 'Form Published';

                            // Update public link with ngrok URL if provided
                            if (data.publicUrl) {
                                console.log('Setting public URL:', data.publicUrl);
                                document.getElementById('public-link-input').value = data.publicUrl;
                                document.getElementById('open-form-link').href = data.publicUrl;
                            }

                            // Generate QR Code
                            const publicLink = document.getElementById('public-link-input').value;
                            const qrcodeContainer = document.getElementById('qrcode');
                            qrcodeContainer.innerHTML = '';

                            if (typeof QRCode !== 'undefined') {
                                console.log('Generating QR Code for:', publicLink);
                                new QRCode(qrcodeContainer, {
                                    text: publicLink,
                                    width: 180,
                                    height: 180,
                                    colorDark: '#0b1c30',
                                    colorLight: '#ffffff',
                                    correctLevel: QRCode.CorrectLevel.H
                                });
                            } else {
                                console.error('QRCode library not loaded!');
                            }
                        } else {
                            alert('Error: ' + (data.message || 'Failed to publish form'));
                            btn.disabled = false;
                            btn.innerHTML = originalBtnHTML;
                        }
                    } catch (parseError) {
                        console.error('=== JSON PARSE ERROR ===');
                        console.error(parseError);
                        console.error('Response text was:', text);
                        alert('Server error. Check console for details.\n\nResponse: ' + text.substring(0, 150));
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHTML;
                    }
                })
                .catch(fetchError => {
                    console.error('=== FETCH ERROR ===');
                    console.error(fetchError);
                    alert('Network error: ' + fetchError.message);
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHTML;
                });
        }

        // Close modal when clicking outside
        document.getElementById('publish-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePublishModal();
            }
        });
    </script>

    <!-- Notification System -->
    <script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>
</body>