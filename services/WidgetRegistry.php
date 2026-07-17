<?php

namespace app\services;

use Yii;
use yii\base\Component;

class WidgetRegistry extends Component
{
    private static $instance;
    private $widgets = [];
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

        $this->register('card', [
            'name' => 'Card',
            'icon' => 'square',
            'category' => 'advanced',
            'description' => 'Menampilkan data dalam bentuk kartu',
            'component' => 'card',
            'defaultProps' => [
                'title' => 'Card Title',
                'subtitle' => '',
                'description' => '',
                'icon' => '',
                'iconLibrary' => 'material-symbols',
                'iconSize' => '48',
                'iconColor' => '#6366f1',
                'iconWeight' => '400',
                'bgColor' => '#ffffff',
                'bgType' => 'solid',
                'bgGradient' => '',
                'bgImage' => '',
                'bgPattern' => '',
                'bgBlur' => '0',
                'padding' => '24',
                'borderRadius' => '12',
                'shadow' => 'md',
                'border' => '',
                'borderColor' => '#e2e8f0',
                'textColor' => '#1e293b',
                'fontSize' => '16',
                'fontWeight' => '400',
                'fontFamily' => '',
                'lineHeight' => '1.5',
                'width' => '100',
                'height' => 'auto',
                'alignment' => 'left',
                'datasource' => 'static',
                'tableId' => '',
                'tableName' => '',
                'aggregate' => 'COUNT',
                'column' => '',
                'filterJson' => '[]',
                'customSql' => '',
                'outputFormat' => 'auto',
                'numberDecimal' => '0',
                'numberSeparator' => ',',
                'numberPrefix' => '',
                'numberSuffix' => '',
                'numberLocale' => 'id-ID',
                'refresh' => 'page_load',
                'refreshInterval' => '30',
                'cacheTtl' => '300',
                'cacheKey' => '',
                'showIcon' => true,
                'showTitle' => true,
                'showSubtitle' => true,
                'showDescription' => true,
                'showValue' => true,
                'iconBackground' => '',
                'iconShape' => 'none',
                'iconOpacity' => '100',
                'iconRotation' => '0',
                'iconFill' => false,
                'iconStroke' => '1.5',
                'timeFilterEnabled' => false,
                'timeFilterPeriod' => 'all',
                'timeFilterColumn' => '',
            ],
            'propsMeta' => [
                'general' => [
                    'label' => 'General',
                    'fields' => ['title', 'subtitle', 'description']
                ],
                'layout' => [
                    'label' => 'Layout',
                    'fields' => ['width', 'height', 'padding', 'borderRadius', 'shadow', 'border', 'borderColor', 'alignment']
                ],
                'typography' => [
                    'label' => 'Typography',
                    'fields' => ['fontSize', 'fontWeight', 'fontFamily', 'lineHeight', 'textColor']
                ],
                'icon' => [
                    'label' => 'Icon',
                    'fields' => ['showIcon', 'icon', 'iconLibrary', 'iconSize', 'iconColor', 'iconWeight', 'iconStroke', 'iconFill', 'iconBackground', 'iconShape', 'iconOpacity', 'iconRotation']
                ],
                'background' => [
                    'label' => 'Background',
                    'fields' => ['bgType', 'bgColor', 'bgGradient', 'bgImage', 'bgPattern', 'bgBlur']
                ],
                'datasource' => [
                    'label' => 'Data Source',
                    'fields' => ['datasource', 'tableId', 'tableName', 'aggregate', 'column', 'filterJson', 'outputFormat']
                ],
                'number' => [
                    'label' => 'Number Format',
                    'fields' => ['numberDecimal', 'numberSeparator', 'numberPrefix', 'numberSuffix', 'numberLocale']
                ],
                'refresh' => [
                    'label' => 'Refresh',
                    'fields' => ['refresh', 'refreshInterval', 'cacheTtl']
                ],
                'timefilter' => [
                    'label' => 'Time Filter',
                    'fields' => ['timeFilterEnabled', 'timeFilterColumn', 'timeFilterPeriod']
                ],
            ]
        ]);

        $this->register('chart', [
            'name' => 'Chart',
            'icon' => 'bar_chart',
            'category' => 'advanced',
            'description' => 'Visualisasi data dalam bentuk grafik',
        ]);

        $this->register('kpi', [
            'name' => 'KPI',
            'icon' => 'speed',
            'category' => 'advanced',
            'description' => 'Key Performance Indicator',
        ]);

        $this->register('gauge', [
            'name' => 'Gauge',
            'icon' => 'analytics',
            'category' => 'advanced',
            'description' => 'Pengukur nilai dalam bentuk gauge',
        ]);

        $this->register('progress', [
            'name' => 'Progress',
            'icon' => 'progress_activity',
            'category' => 'advanced',
            'description' => 'Progress bar',
        ]);

        $this->register('timeline', [
            'name' => 'Timeline',
            'icon' => 'timeline',
            'category' => 'advanced',
            'description' => 'Urutan kejadian berdasarkan waktu',
        ]);

        $this->register('calendar', [
            'name' => 'Calendar',
            'icon' => 'calendar_month',
            'category' => 'advanced',
            'description' => 'Kalender dengan event',
        ]);

        $this->register('statistic', [
            'name' => 'Statistic',
            'icon' => 'statistics',
            'category' => 'advanced',
            'description' => 'Angka statistik',
        ]);
    }

    public function register($type, $config)
    {
        $this->widgets[$type] = $config;
    }

    public function get($type)
    {
        $this->loadDefaults();
        return $this->widgets[$type] ?? null;
    }

    public function getAll()
    {
        $this->loadDefaults();
        return $this->widgets;
    }

    public function getByCategory($category)
    {
        $this->loadDefaults();
        return array_filter($this->widgets, function ($w) use ($category) {
            return ($w['category'] ?? '') === $category;
        });
    }

    public function getPropsMeta($type)
    {
        $widget = $this->get($type);
        return $widget['propsMeta'] ?? [];
    }

    public function getDefaultProps($type)
    {
        $widget = $this->get($type);
        return $widget['defaultProps'] ?? [];
    }
}
