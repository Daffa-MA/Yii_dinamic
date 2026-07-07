<?php

namespace app\services;

use Yii;
use yii\base\Component;

class IconRegistry extends Component
{
    private static $instance;
    private $libraries = [];
    private $loaded = false;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function loadDefaults()
    {
        if ($this->loaded) return;
        $this->loaded = true;

        $this->registerLibrary('material-symbols', [
            'name' => 'Material Symbols',
            'version' => 'latest',
            'cdn' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200',
            'cssClass' => 'material-symbols-outlined',
            'searchUrl' => '',
            'type' => 'font',
            'categories' => $this->getMaterialSymbolsCategories(),
        ]);

        $this->registerLibrary('tabler', [
            'name' => 'Tabler Icons',
            'version' => '3.24.0',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.24.0/dist/tabler-icons.min.css',
            'cssClass' => 'ti',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=tabler',
            'type' => 'font',
            'categories' => ['interface', 'objects', 'actions', 'media', 'maps', 'arrows'],
        ]);

        $this->registerLibrary('heroicons', [
            'name' => 'Heroicons',
            'version' => '2.2.0',
            'cdn' => 'https://cdn.jsdelivr.net/npm/heroicons@2.2.0/css/heroicons.css',
            'cssClass' => 'hero-icon',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=heroicons',
            'type' => 'svg',
            'categories' => ['outline', 'solid', 'mini'],
        ]);

        $this->registerLibrary('lucide', [
            'name' => 'Lucide',
            'version' => 'latest',
            'cdn' => 'https://cdn.jsdelivr.net/npm/lucide@latest/dist/cjs/lucide.css',
            'cssClass' => 'lucide',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=lucide',
            'type' => 'svg',
            'categories' => ['arrows', 'communication', 'development', 'editors', 'files', 'layout'],
        ]);

        $this->registerLibrary('phosphor', [
            'name' => 'Phosphor',
            'version' => '2.1.1',
            'cdn' => 'https://cdn.jsdelivr.net/npm/phosphor-icons@2.1.1/src/css/phosphor.css',
            'cssClass' => 'ph',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=ph',
            'type' => 'font',
            'categories' => ['bold', 'duotone', 'fill', 'light', 'regular', 'thin'],
        ]);

        $this->registerLibrary('remix', [
            'name' => 'Remix Icon',
            'version' => '4.5.0',
            'cdn' => 'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css',
            'cssClass' => 'ri',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=ri',
            'type' => 'font',
            'categories' => ['buildings', 'business', 'communication', 'design', 'development', 'device', 'document', 'editor', 'finance', 'health', 'logos', 'map', 'media', 'music', 'others', 'phone', 'plane', 'system', 'tabs', 'time', 'toggle', 'user', 'weather'],
        ]);

        $this->registerLibrary('font-awesome', [
            'name' => 'Font Awesome',
            'version' => '6.7.0',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/css/all.min.css',
            'cssClass' => 'fa-solid',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=fa',
            'type' => 'font',
            'categories' => ['solid', 'regular', 'brands'],
        ]);

        $this->registerLibrary('bootstrap-icons', [
            'name' => 'Bootstrap Icons',
            'version' => '1.11.3',
            'cdn' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            'cssClass' => 'bi',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=bi',
            'type' => 'font',
            'categories' => ['alphabet', 'arrows', 'chat', 'check', 'clouds', 'communication', 'controls', 'devices', 'document', 'emoji', 'filetypes', 'files', 'fonts', 'forms', 'geo', 'graphics', 'health', 'images', 'interfaces', 'layouts', 'maps', 'media', 'medical', 'music', 'nature', 'navigation', 'people', 'person', 'phone', 'plants', 'punctuation', 'search', 'shapes', 'shopping', 'signs', 'social', 'sorting', 'sports', 'status', 'symbols', 'technology', 'text', 'time', 'toggle', 'tools', 'transportation', 'travel', 'tv', 'ui', 'video', 'view', 'weather'],
        ]);

        $this->registerLibrary('feather', [
            'name' => 'Feather',
            'version' => '4.29.2',
            'cdn' => 'https://cdn.jsdelivr.net/npm/feather-icons@4.29.2/dist/feather.css',
            'cssClass' => 'feather',
            'searchUrl' => 'https://api.iconify.design/search?query={query}&prefix=feather',
            'type' => 'svg',
            'categories' => ['design', 'development', 'media', 'status', 'symbols', 'weather'],
        ]);
    }

    private function getMaterialSymbolsCategories()
    {
        return [
            'action', 'alert', 'av', 'communication', 'content', 'device',
            'editor', 'file', 'hardware', 'home', 'image', 'maps',
            'navigation', 'notification', 'places', 'social', 'toggle',
        ];
    }

    public function registerLibrary($name, $config)
    {
        $this->libraries[$name] = $config;
    }

    public function getLibrary($name)
    {
        $this->loadDefaults();
        return $this->libraries[$name] ?? null;
    }

    public function getAllLibraries()
    {
        $this->loadDefaults();
        return $this->libraries;
    }

    public function getLibraryOptions()
    {
        $this->loadDefaults();
        $options = [];
        foreach ($this->libraries as $name => $config) {
            $options[] = [
                'value' => $name,
                'label' => $config['name'],
                'cdn' => $config['cdn'] ?? '',
                'cssClass' => $config['cssClass'] ?? '',
                'categories' => $config['categories'] ?? [],
            ];
        }
        return $options;
    }
}
