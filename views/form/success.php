<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */

use yii\bootstrap5\Html;

$this->title = 'Response Recorded - ' . $model->name;

// Tailwind CSS
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'primary': '#4f46e5',
                    'primary-dark': '#4338ca',
                    'success': '#10b981',
                    'success-dark': '#059669',
                },
                animation: {
                    'bounce-slow': 'bounce 2s infinite',
                    'fade-in': 'fadeIn 0.6s ease-out',
                    'slide-up': 'slideUp 0.6s ease-out',
                },
                keyframes: {
                    fadeIn: {
                        '0%': { opacity: '0' },
                        '100%': { opacity: '1' },
                    },
                    slideUp: {
                        '0%': { opacity: '0', transform: 'translateY(20px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                }
            }
        }
    }
</script>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center p-4 m-0">
    <div class="max-w-2xl w-full mx-auto">
        <!-- Success Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden animate-slide-up">
            <!-- Success Header with Gradient -->
            <div class="bg-gradient-to-r from-success to-success-dark text-white p-12 text-center">
                <!-- Animated Checkmark Circle -->
                <div class="mx-auto w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-6 animate-bounce-slow">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <h1 class="text-3xl font-bold mb-3">Response Recorded!</h1>
                <p class="text-green-100 text-lg">Your form has been submitted successfully</p>
            </div>

            <!-- Card Body -->
            <div class="p-12">
                <!-- Message -->
                <div class="text-center mb-8">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                        <p class="text-gray-700 text-lg leading-relaxed">
                            Terima kasih telah mengisi form <strong><?= Html::encode($model->name) ?></strong>
                        </p>
                        <p class="text-gray-600 mt-2">
                            Jawaban Anda telah berhasil kami terima dan akan segera diproses.
                        </p>
                    </div>
                </div>

                <!-- Timestamp -->
                <div class="text-center mb-8">
                    <p class="text-sm text-gray-500">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                        Submitted at: <?= date('F d, Y H:i:s') ?>
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Submit Another Response -->
                    <a href="<?= \yii\helpers\Url::to(['form/public-render', 'id' => $model->id]) ?>" 
                       class="flex-1 bg-gradient-to-r from-primary to-primary-dark text-white font-semibold py-4 px-6 rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 text-center no-underline flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Submit Another Response
                    </a>
                    
                    <!-- Close Button -->
                    <button onclick="window.close()" 
                            class="flex-1 bg-gray-100 text-gray-700 font-semibold py-4 px-6 rounded-xl hover:bg-gray-200 transition-all duration-200 text-center flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Close
                    </button>
                </div>

                <!-- Optional: Go Back to Source -->
                <?php if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])): ?>
                    <div class="text-center mt-6">
                        <a href="<?= Html::encode($_SERVER['HTTP_REFERER']) ?>" 
                           class="text-primary hover:text-primary-dark font-medium inline-flex items-center gap-2 transition-colors no-underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to source page
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm animate-fade-in">
            <p>Powered by <span class="font-semibold text-primary">TableForge</span></p>
        </div>
    </div>
</body>
