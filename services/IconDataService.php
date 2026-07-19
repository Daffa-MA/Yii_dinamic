<?php

namespace app\services;

use Yii;
use yii\base\Component;

class IconDataService extends Component
{
    private array $_iconCache = [];
    private string $_dataPath;

    public function init()
    {
        parent::init();
        $this->_dataPath = Yii::getAlias('@app/data/icons');
        if (!is_dir($this->_dataPath)) {
            $this->_dataPath = dirname(__DIR__) . '/data/icons';
        }
    }

    public function getIcons($library)
    {
        if (isset($this->_iconCache[$library])) {
            return $this->_iconCache[$library];
        }

        $icons = $this->loadIcons($library);
        $this->_iconCache[$library] = $icons;
        return $icons;
    }

    public function searchIcons($query, $library, $limit = 9999)
    {
        $query = strtolower(trim($query));
        $iconDb = $this->getIcons($library);
        if (empty($iconDb)) return [];

        $results = [];
        foreach ($iconDb as $icon) {
            if ($query) {
                $name = strtolower($icon['name'] ?? '');
                $searchTerms = strtolower($icon['searchTerms'] ?? $name);
                if (strpos($name, $query) === false && strpos($searchTerms, $query) === false) {
                    continue;
                }
            }
            $results[] = $icon;
            if (count($results) >= $limit) break;
        }

        return $results;
    }

    private function loadIcons($library): array
    {
        $fileMap = [
            'heroicons' => 'heroicons.json',
            'lucide' => 'lucide.json',
        ];

        if (!isset($fileMap[$library])) {
            return [];
        }

        $filePath = $this->_dataPath . '/' . $fileMap[$library];
        if (!file_exists($filePath)) {
            return [];
        }

        $json = file_get_contents($filePath);
        if ($json === false) {
            return [];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}
