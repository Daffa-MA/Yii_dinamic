<?php

namespace app\services;

use Yii;
use yii\base\Component;

class FormatterRegistry extends Component
{
    private static $instance;
    private $formats = [];
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

        $this->register('auto', [
            'name' => 'auto',
            'label' => 'Auto',
            'icon' => 'auto_fix',
            'description' => 'Format otomatis berdasarkan tipe data',
        ]);

        $this->register('number', [
            'name' => 'number',
            'label' => 'Number',
            'icon' => 'numbers',
            'description' => 'Format angka',
            'hasConfig' => true,
        ]);

        $this->register('currency', [
            'name' => 'currency',
            'label' => 'Currency',
            'icon' => 'attach_money',
            'description' => 'Format mata uang',
            'hasConfig' => true,
        ]);

        $this->register('percentage', [
            'name' => 'percentage',
            'label' => 'Percentage',
            'icon' => 'percent',
            'description' => 'Format persentase',
            'hasConfig' => true,
        ]);

        $this->register('duration', [
            'name' => 'duration',
            'label' => 'Duration',
            'icon' => 'timer',
            'description' => 'Format durasi waktu',
        ]);

        $this->register('date', [
            'name' => 'date',
            'label' => 'Date',
            'icon' => 'calendar_today',
            'description' => 'Format tanggal',
            'hasConfig' => true,
        ]);

        $this->register('datetime', [
            'name' => 'datetime',
            'label' => 'Datetime',
            'icon' => 'calendar_clock',
            'description' => 'Format tanggal dan waktu',
            'hasConfig' => true,
        ]);

        $this->register('time', [
            'name' => 'time',
            'label' => 'Time',
            'icon' => 'schedule',
            'description' => 'Format waktu',
            'hasConfig' => true,
        ]);

        $this->register('custom', [
            'name' => 'custom',
            'label' => 'Custom Format',
            'icon' => 'more_horiz',
            'description' => 'Format kustom menggunakan pattern',
            'hasConfig' => true,
        ]);
    }

    public function register($name, $config)
    {
        $this->formats[$name] = $config;
    }

    public function get($name)
    {
        $this->loadDefaults();
        return $this->formats[$name] ?? null;
    }

    public function getAll()
    {
        $this->loadDefaults();
        return $this->formats;
    }

    public function getOptions()
    {
        $this->loadDefaults();
        $options = [];
        foreach ($this->formats as $name => $config) {
            $options[] = [
                'value' => $name,
                'label' => $config['label'],
                'icon' => $config['icon'] ?? '',
            ];
        }
        return $options;
    }
}
