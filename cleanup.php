<?php
/**
 * Performance Cleanup Script for Yii Application
 * Jalankan dari browser: http://localhost/cleanup.php
 * Atau dari CLI: php cleanup.php
 */

$runtimeDir = __DIR__ . '/runtime';

$folders = [
    'cache',
    'debug',
    'session',
    'logs',
];

$cleanedCount = 0;
$totalSize = 0;

echo "🧹 Membersihkan file sampah Yii...\n";
echo "================================\n\n";

foreach ($folders as $folder) {
    $path = $runtimeDir . '/' . $folder;
    
    if (!is_dir($path)) {
        continue;
    }
    
    $files = array_diff(scandir($path), array('.', '..', '.gitignore'));
    $folderCount = 0;
    $folderSize = 0;
    
    foreach ($files as $file) {
        $filePath = $path . '/' . $file;
        $fileSize = 0;
        
        if (is_dir($filePath)) {
            $fileSize = dirSize($filePath);
            @rmdir($filePath);
        } else {
            $fileSize = filesize($filePath);
            @unlink($filePath);
        }
        
        $folderSize += $fileSize;
        $folderCount++;
    }
    
    if ($folderCount > 0) {
        $sizeInKB = round($folderSize / 1024, 2);
        echo "✓ $folder: Dihapus $folderCount files ({$sizeInKB} KB)\n";
        $cleanedCount += $folderCount;
        $totalSize += $folderSize;
    }
}

// Hapus old database snapshots
$dbFile = $runtimeDir . '/app.db';
if (file_exists($dbFile)) {
    $dbSize = filesize($dbFile);
    // Jangan dihapus, tapi catat ukurannya
    $sizeMB = round($dbSize / (1024 * 1024), 2);
    echo "\n📊 Database size: $sizeMB MB (tidak dihapus)\n";
}

echo "\n================================\n";
echo "✅ Pembersihan selesai!\n";
echo "Total files dihapus: $cleanedCount\n";
echo "Total size freed: " . round($totalSize / (1024 * 1024), 2) . " MB\n";

function dirSize($dir) {
    $size = 0;
    foreach (scandir($dir) as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $size += dirSize($filePath);
            } else {
                $size += filesize($filePath);
            }
        }
    }
    return $size;
}
