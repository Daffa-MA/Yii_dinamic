<?php

namespace app\services;

use Yii;
use yii\base\Component;

class AggregateRegistry extends Component
{
    private static $instance;
    private $aggregates = [];
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

        $this->register('COUNT', [
            'name' => 'COUNT',
            'label' => 'Count (Jumlah)',
            'icon' => '123',
            'description' => 'Menghitung jumlah baris',
            'requiresColumn' => false,
            'numericOnly' => false,
            'sqlFunction' => 'COUNT',
        ]);

        $this->register('SUM', [
            'name' => 'SUM',
            'label' => 'Sum (Total)',
            'icon' => 'summarize',
            'description' => 'Menjumlahkan nilai kolom',
            'requiresColumn' => true,
            'numericOnly' => true,
            'sqlFunction' => 'SUM',
        ]);

        $this->register('AVG', [
            'name' => 'AVG',
            'label' => 'Average (Rata-rata)',
            'icon' => 'average',
            'description' => 'Rata-rata nilai kolom',
            'requiresColumn' => true,
            'numericOnly' => true,
            'sqlFunction' => 'AVG',
        ]);

        $this->register('MIN', [
            'name' => 'MIN',
            'label' => 'Minimum',
            'icon' => 'lowest',
            'description' => 'Nilai terendah',
            'requiresColumn' => true,
            'numericOnly' => false,
            'sqlFunction' => 'MIN',
        ]);

        $this->register('MAX', [
            'name' => 'MAX',
            'label' => 'Maximum',
            'icon' => 'highest',
            'description' => 'Nilai tertinggi',
            'requiresColumn' => true,
            'numericOnly' => false,
            'sqlFunction' => 'MAX',
        ]);

        $this->register('DISTINCT_COUNT', [
            'name' => 'DISTINCT_COUNT',
            'label' => 'Distinct Count',
            'icon' => 'filter_alt',
            'description' => 'Jumlah nilai unik',
            'requiresColumn' => true,
            'numericOnly' => false,
            'sqlFunction' => 'COUNT(DISTINCT',
        ]);

        $this->register('CUSTOM', [
            'name' => 'CUSTOM',
            'label' => 'Custom SQL',
            'icon' => 'code',
            'description' => 'Ekspresi SQL kustom',
            'requiresColumn' => false,
            'numericOnly' => false,
            'sqlFunction' => null,
        ]);
    }

    public function register($type, $config)
    {
        $this->aggregates[$type] = $config;
    }

    public function get($type)
    {
        $this->loadDefaults();
        return $this->aggregates[$type] ?? null;
    }

    public function getAll()
    {
        $this->loadDefaults();
        return $this->aggregates;
    }

    public function getOptions()
    {
        $this->loadDefaults();
        $options = [];
        foreach ($this->aggregates as $type => $config) {
            $options[] = [
                'value' => $type,
                'label' => $config['label'],
                'requiresColumn' => $config['requiresColumn'] ?? false,
                'numericOnly' => $config['numericOnly'] ?? false,
            ];
        }
        return $options;
    }
}
