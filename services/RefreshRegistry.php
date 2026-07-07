<?php

namespace app\services;

use Yii;
use yii\base\Component;

class RefreshRegistry extends Component
{
    private static $instance;
    private $strategies = [];
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

        $this->register('page_load', [
            'name' => 'page_load',
            'label' => 'Page Load',
            'icon' => 'refresh',
            'description' => 'Refresh saat halaman dimuat',
            'hasInterval' => false,
        ]);

        $this->register('realtime', [
            'name' => 'realtime',
            'label' => 'Realtime',
            'icon' => 'bolt',
            'description' => 'Update realtime menggunakan WebSocket/SSE',
            'hasInterval' => false,
        ]);

        $this->register('interval', [
            'name' => 'interval',
            'label' => 'Interval',
            'icon' => 'timer',
            'description' => 'Refresh otomatis dengan interval tertentu',
            'hasInterval' => true,
        ]);

        $this->register('manual', [
            'name' => 'manual',
            'label' => 'Manual',
            'icon' => 'hand_gesture',
            'description' => 'Refresh hanya saat tombol diklik',
            'hasInterval' => false,
        ]);

        $this->register('visibility', [
            'name' => 'visibility',
            'label' => 'Visibility Change',
            'icon' => 'visibility',
            'description' => 'Refresh saat tab menjadi aktif kembali',
            'hasInterval' => false,
        ]);
    }

    public function register($name, $config)
    {
        $this->strategies[$name] = $config;
    }

    public function get($name)
    {
        $this->loadDefaults();
        return $this->strategies[$name] ?? null;
    }

    public function getAll()
    {
        $this->loadDefaults();
        return $this->strategies;
    }

    public function getOptions()
    {
        $this->loadDefaults();
        $options = [];
        foreach ($this->strategies as $name => $config) {
            $options[] = [
                'value' => $name,
                'label' => $config['label'],
                'hasInterval' => $config['hasInterval'] ?? false,
            ];
        }
        return $options;
    }
}
