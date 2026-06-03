<?php

namespace app\components;

use app\models\Project;
use Yii;
use yii\helpers\Url;

class DomainContext
{
    public function currentHost(): string
    {
        return $this->normalizeHost((string)Yii::$app->request->getHostName());
    }

    public function rootDomain(): string
    {
        $currentHost = $this->currentHost();
        if ($this->isLocalHost($currentHost)) {
            $port = (string)Yii::$app->request->getPort();
            return $port !== '' && $port !== '80' && $port !== '443'
                ? $currentHost . ':' . $port
                : $currentHost;
        }

        $configured = getenv('APP_ROOT_DOMAIN');
        if ($configured === false || trim($configured) === '') {
            $configured = (string)(
                Yii::$app->params['commanderDomain']
                ?? Yii::$app->params['rootDomain']
                ?? Yii::$app->params['appDomainSuffix']
                ?? 'appforge.web.id'
            );
        }

        return $this->normalizeHost((string)$configured);
    }

    public function commanderUrl(string $path = ''): string
    {
        $domain = $this->rootDomain();
        $path = '/' . ltrim($path, '/');

        if ($domain === '') {
            return $path;
        }

        $scheme = $this->isLocalHost($domain) && !Yii::$app->request->isSecureConnection ? 'http' : 'https';
        return $scheme . '://' . $domain . $path;
    }

    public function projectListUrl(): string
    {
        return $this->isWorkspaceDomain()
            ? $this->commanderUrl('/project-list')
            : Url::to(['project/index']);
    }

    public function projectDomainSuffix(): string
    {
        return $this->normalizeHost(Project::getProjectDomainSuffix());
    }

    public function isRootDomain(?string $host = null): bool
    {
        $host = $this->normalizeHost($host ?? $this->currentHost());
        $rootDomain = $this->rootDomain();

        return $rootDomain !== '' && $host === $rootDomain;
    }

    public function isWorkspaceDomain(?string $host = null): bool
    {
        $host = $this->normalizeHost($host ?? $this->currentHost());
        $rootDomain = $this->rootDomain();
        $suffix = $this->projectDomainSuffix();

        if ($host === '' || $this->isRootDomain($host)) {
            return false;
        }

        if ($rootDomain !== '' && substr($host, -strlen('.' . $rootDomain)) === '.' . $rootDomain) {
            return true;
        }

        return $suffix !== '' && substr($host, -strlen('.' . $suffix)) === '.' . $suffix;
    }

    public function extractWorkspacePrefix(?string $host = null): string
    {
        $host = $this->normalizeHost($host ?? $this->currentHost());
        if ($host === '' || $this->isRootDomain($host)) {
            return '';
        }

        $suffixes = array_filter(array_unique([
            $this->rootDomain(),
            $this->projectDomainSuffix(),
        ]));

        foreach ($suffixes as $suffix) {
            $suffix = $this->normalizeHost($suffix);
            $needle = '.' . $suffix;
            if ($suffix !== '' && substr($host, -strlen($needle)) === $needle) {
                $prefix = substr($host, 0, -strlen($needle));
                return Project::normalizeDomainPrefix($prefix);
            }
        }

        return '';
    }

    public function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $host = trim($host, " \t\n\r\0\x0B.");

        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host;
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($this->normalizeHost($host), ['localhost', '127.0.0.1'], true);
    }
}
