<?php

namespace app\services;

use Yii;
use yii\base\Component;

class DatasourceRegistry extends Component
{
    private static $instance;
    private $sources = [];
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

        $this->register('database', [
            'name' => 'Database',
            'icon' => 'storage',
            'description' => 'Mengambil data dari tabel database',
            'requiresTable' => true,
            'requiresAggregate' => true,
            'requiresColumn' => true,
            'requiresFilter' => true,
        ]);

        $this->register('api', [
            'name' => 'API',
            'icon' => 'api',
            'description' => 'Mengambil data dari endpoint API eksternal',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);

        $this->register('static', [
            'name' => 'Static Text',
            'icon' => 'text_fields',
            'description' => 'Menggunakan teks statis yang diketik manual',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);

        $this->register('formula', [
            'name' => 'Formula',
            'icon' => 'functions',
            'description' => 'Menghitung nilai berdasarkan formula matematika',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);

        $this->register('variable', [
            'name' => 'Variable',
            'icon' => 'variables',
            'description' => 'Menggunakan variable yang sudah didefinisikan',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);

        $this->register('session', [
            'name' => 'Session',
            'icon' => 'session',
            'description' => 'Data dari session user saat ini',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);

        $this->register('user', [
            'name' => 'User Login',
            'icon' => 'person',
            'description' => 'Data dari user yang sedang login',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);

        $this->register('request', [
            'name' => 'Request Parameter',
            'icon' => 'link',
            'description' => 'Mengambil data dari parameter request (GET/POST)',
            'requiresTable' => false,
            'requiresAggregate' => false,
            'requiresColumn' => false,
            'requiresFilter' => false,
        ]);
    }

    public function register($type, $config)
    {
        $this->sources[$type] = $config;
    }

    public function get($type)
    {
        $this->loadDefaults();
        return $this->sources[$type] ?? null;
    }

    public function getAll()
    {
        $this->loadDefaults();
        return $this->sources;
    }

    public function getOptions()
    {
        $this->loadDefaults();
        $options = [];
        foreach ($this->sources as $type => $config) {
            $options[] = [
                'value' => $type,
                'label' => $config['name'],
                'icon' => $config['icon'] ?? '',
            ];
        }
        return $options;
    }
}
