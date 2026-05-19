<?php

namespace app\components;

use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\UploadedFile;

class WorkspaceMediaStorage
{
    private const PUBLIC_RELATIVE_DIR = 'uploads/workspace/';
    private const STORAGE_ALIAS = '@storage';

    public function storageBasePath(): string
    {
        $configured = getenv('YII_WORKSPACE_MEDIA_STORAGE_PATH');
        if (!is_string($configured) || trim($configured) === '') {
            $configured = getenv('APP_WORKSPACE_MEDIA_STORAGE_PATH');
        }

        if (is_string($configured) && trim($configured) !== '') {
            $configured = trim($configured);
            if (preg_match('#^(?:[A-Za-z]:[\\\\/]|/|\\\\\\\\)#', $configured)) {
                return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $configured), DIRECTORY_SEPARATOR);
            }

            return Yii::getAlias($configured);
        }

        return Yii::getAlias(self::STORAGE_ALIAS . '/uploads');
    }

    public function publicBasePath(): string
    {
        return Yii::getAlias('@webroot/' . self::PUBLIC_RELATIVE_DIR);
    }

    public function publicUrl(string $relativePath): string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '') {
            return '';
        }

        $this->syncPublicMirror($relativePath);
        return Url::to('/' . self::PUBLIC_RELATIVE_DIR . $relativePath, true);
    }

    public function storagePath(string $relativePath): string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '') {
            return '';
        }

        return $this->storageBasePath() . DIRECTORY_SEPARATOR . $relativePath;
    }

    public function ensurePersistentDirectories(): void
    {
        foreach (['project-assets', 'workspace-logo', 'login-background'] as $directory) {
            $this->ensureDirectory($this->storageBasePath() . DIRECTORY_SEPARATOR . $directory);
        }
    }

    public function publicPath(string $relativePath): string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '') {
            return '';
        }

        return $this->publicBasePath() . $relativePath;
    }

    public function localPath(string $value): string
    {
        $relativePath = $this->normalizeValueToRelativePath($value);
        if ($relativePath === '') {
            return '';
        }

        $storagePath = $this->storagePath($relativePath);
        if ($storagePath !== '' && is_file($storagePath)) {
            return $storagePath;
        }

        $legacyStoragePath = $this->legacyStoragePath($relativePath);
        if ($legacyStoragePath !== '' && is_file($legacyStoragePath)) {
            $this->ensureDirectory(dirname($storagePath));
            if (!is_file($storagePath)) {
                @copy($legacyStoragePath, $storagePath);
            }
            return is_file($storagePath) ? $storagePath : $legacyStoragePath;
        }

        $publicPath = $this->publicPath($relativePath);
        if ($publicPath !== '' && is_file($publicPath)) {
            $this->ensureDirectory(dirname($storagePath));
            if (!is_file($storagePath)) {
                @copy($publicPath, $storagePath);
            }
            return is_file($storagePath) ? $storagePath : $publicPath;
        }

        return $storagePath;
    }

    public function delete(string $value): void
    {
        if (preg_match('#^https?://#i', trim($value))) {
            return;
        }

        $relativePath = $this->normalizeValueToRelativePath($value);
        if ($relativePath === '') {
            return;
        }

        foreach ([$this->storagePath($relativePath), $this->publicPath($relativePath)] as $filePath) {
            if ($filePath !== '' && is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    /**
     * @return array{success:bool,message:string,relative_path?:string,storage_path?:string,public_path?:string,url?:string}
     */
    public function storeUploadedFile(UploadedFile $uploadedFile, string $prefix, string $relativeDir = ''): array
    {
        $this->ensurePersistentDirectories();
        $extension = strtolower($uploadedFile->getExtension());
        $relativeDir = $this->normalizeRelativeDir($relativeDir);
        $fileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $relativePath = $relativeDir . $fileName;

        $storagePath = $this->storagePath($relativePath);
        $publicPath = $this->publicPath($relativePath);

        $this->ensureDirectory(dirname($storagePath));
        $this->ensureDirectory(dirname($publicPath));

        if (!$uploadedFile->saveAs($storagePath)) {
            return ['success' => false, 'message' => 'Gagal menyimpan file ke storage.'];
        }

        if ($publicPath !== $storagePath) {
            @copy($storagePath, $publicPath);
        }

        return [
            'success' => true,
            'message' => 'File berhasil disimpan.',
            'relative_path' => $relativePath,
            'storage_path' => $storagePath,
            'public_path' => $publicPath,
            'url' => $this->publicUrl($relativePath),
        ];
    }

    public function versionedUrl(string $value): string
    {
        $relativePath = $this->normalizeValueToRelativePath($value);
        if ($relativePath === '') {
            return '';
        }

        $this->syncPublicMirror($relativePath);
        $url = Url::to('/' . self::PUBLIC_RELATIVE_DIR . $relativePath, true);
        $file = $this->localPath($relativePath);
        $version = is_file($file) ? (string)filemtime($file) : date('YmdHis');

        return $url . (strpos($url, '?') !== false ? '&' : '?') . 'v=' . rawurlencode($version);
    }

    private function syncPublicMirror(string $relativePath): void
    {
        $storagePath = $this->storagePath($relativePath);
        $publicPath = $this->publicPath($relativePath);

        if ($storagePath === '' || $publicPath === '') {
            return;
        }

        if (!is_file($storagePath)) {
            $legacyStoragePath = $this->legacyStoragePath($relativePath);
            if ($legacyStoragePath !== '' && is_file($legacyStoragePath)) {
                $this->ensureDirectory(dirname($storagePath));
                @copy($legacyStoragePath, $storagePath);
            }
        }

        if (!is_file($storagePath) && is_file($publicPath)) {
            $this->ensureDirectory(dirname($storagePath));
            @copy($publicPath, $storagePath);
        }

        if (!is_file($storagePath)) {
            return;
        }

        if ($publicPath === $storagePath) {
            return;
        }

        $this->ensureDirectory(dirname($publicPath));
        if (!is_file($publicPath)) {
            @copy($storagePath, $publicPath);
        }
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath));
        $relativePath = ltrim($relativePath, '/');
        $relativePath = preg_replace('#[^a-zA-Z0-9_\-./]#', '', $relativePath) ?? $relativePath;
        return $relativePath;
    }

    private function normalizeRelativeDir(string $relativeDir): string
    {
        $relativeDir = $this->normalizeRelativePath($relativeDir);
        if ($relativeDir === '') {
            return '';
        }

        return rtrim($relativeDir, '/') . '/';
    }

    private function normalizeValueToRelativePath(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $value;
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (strpos($path, self::PUBLIC_RELATIVE_DIR) === 0) {
            $path = substr($path, strlen(self::PUBLIC_RELATIVE_DIR));
        }

        return $this->normalizeRelativePath($path);
    }

    private function legacyStoragePath(string $relativePath): string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '') {
            return '';
        }

        return $this->storageBasePath() . DIRECTORY_SEPARATOR . 'workspace' . DIRECTORY_SEPARATOR . $relativePath;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0755, true);
        }
    }
}
