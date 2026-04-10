<?php
/**
 * Setup script to create/reset admin user
 * Usage: php setup_admin.php
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/web.php';
$app = new yii\web\Application($config);

use app\models\User;

echo "=== Admin User Setup ===\n\n";

// Disable profiling to avoid extra output
Yii::$app->params['disableProfiler'] = true;

// Check if admin user exists
$admin = User::findOne(['username' => 'admin']);

if ($admin) {
    echo "Admin user exists. Resetting password...\n";
    $admin->setPassword('admin123');
    $admin->auth_key = Yii::$app->security->generateRandomString(32);
    if ($admin->save()) {
        echo "✓ Admin password reset to: admin123\n";
    } else {
        echo "✗ Failed to reset password\n";
        print_r($admin->getErrors());
    }
} else {
    echo "Creating admin user...\n";
    $user = new User();
    $user->username = 'admin';
    $user->setPassword('admin123');
    $user->auth_key = Yii::$app->security->generateRandomString(32);
    
    if ($user->save()) {
        echo "✓ Admin user created successfully!\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
    } else {
        echo "✗ Failed to create admin user\n";
        print_r($user->getErrors());
    }
}

echo "\n=== Setup Complete ===\n";

