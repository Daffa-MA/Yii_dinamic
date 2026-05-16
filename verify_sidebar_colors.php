<?php
/**
 * Verification script to check sidebar color settings
 * Run this from command line: php verify_sidebar_colors.php
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/web.php';
new yii\web\Application($config);

echo "=== Sidebar Color Verification ===\n\n";

// Load workspace settings
$settings = new \app\models\WorkspaceSettings();
$settings->loadFromDatabase();

echo "Workspace Settings from Database:\n";
echo "- sidebar_text_color: " . ($settings->sidebar_text_color ?? 'NOT SET') . "\n";
echo "- sidebar_text_muted: " . ($settings->sidebar_text_muted ?? 'NOT SET') . "\n\n";

// Get CSS vars
$cssVars = $settings->getCssVars();
echo "CSS Variables:\n";
echo "- sidebar-text-color: " . ($cssVars['sidebar-text-color'] ?? 'NOT SET') . "\n";
echo "- sidebar-text-muted: " . ($cssVars['sidebar-text-muted'] ?? 'NOT SET') . "\n\n";

// Check session
if (isset($_SESSION)) {
    echo "Session data available\n";
} else {
    echo "No session data (this is normal for CLI)\n";
}

echo "\n=== Verification Complete ===\n";
echo "\nNext steps:\n";
echo "1. Hard refresh your browser (Ctrl + Shift + R or Cmd + Shift + R)\n";
echo "2. Check the HTML source for debug comments\n";
echo "3. Look for '<!-- CACHE BUSTER: v2.0' in the HTML\n";
echo "4. Verify inline styles have 'color: #yourcolor !important'\n";
