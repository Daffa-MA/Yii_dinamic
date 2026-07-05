<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class MasterPageChart extends ActiveRecord
{
    public static function tableName()
    {
        return 'master_page_chart';
    }

    public static function getDb()
    {
        $metaDb = Yii::$app->get('metadataDb', false);
        return $metaDb ?: Yii::$app->db;
    }

    public function rules()
    {
        return [
            [['title', 'chart_type', 'table_id'], 'required'],
            [['page_id', 'table_id', 'position', 'height', 'limit'], 'integer'],
            [['show_legend', 'show_label', 'show_toolbar', 'show_grid', 'show_total', 'is_active'], 'boolean'],
            [['title', 'subtitle', 'chart_type', 'table_name', 'source_type', 'source_query',
              'theme', 'palette', 'animation', 'label_field', 'value_field', 'aggregation',
              'group_by_field', 'sort_field', 'sort_direction'], 'string'],
            [['filter_config', 'series_config', 'extra_config'], 'safe'],
            ['chart_type', 'in', 'range' => [
                'bar', 'bar_horizontal', 'line', 'area', 'pie', 'donut',
                'radar', 'polar_area', 'bubble', 'scatter', 'stacked_bar',
                'stacked_area', 'mixed', 'multi_series'
            ]],
            ['aggregation', 'in', 'range' => ['count', 'sum', 'avg', 'min', 'max', 'count_distinct']],
            ['source_type', 'in', 'range' => ['table', 'view', 'query', 'procedure']],
            ['theme', 'in', 'range' => ['light', 'dark', 'auto']],
            ['palette', 'in', 'range' => ['modern', 'random', 'material', 'pastel', 'dark', 'gradient']],
            ['animation', 'in', 'range' => ['fade', 'zoom', 'slide', 'bounce', 'none']],
            ['sort_direction', 'in', 'range' => ['asc', 'desc']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Halaman',
            'title' => 'Judul Chart',
            'subtitle' => 'Subjudul',
            'chart_type' => 'Tipe Chart',
            'table_id' => 'Tabel',
            'table_name' => 'Nama Tabel',
            'source_type' => 'Tipe Sumber',
            'source_query' => 'Query Kustom',
            'position' => 'Posisi',
            'height' => 'Tinggi (px)',
            'theme' => 'Tema',
            'palette' => 'Palet Warna',
            'animation' => 'Animasi',
            'label_field' => 'Field Label',
            'value_field' => 'Field Value',
            'aggregation' => 'Agregasi',
            'group_by_field' => 'Group By',
            'sort_field' => 'Sortir',
            'sort_direction' => 'Arah Sortir',
            'limit' => 'Batas Data',
            'show_legend' => 'Tampilkan Legend',
            'show_label' => 'Tampilkan Label',
            'show_toolbar' => 'Tampilkan Toolbar',
            'show_grid' => 'Tampilkan Grid',
            'show_total' => 'Tampilkan Total',
            'is_active' => 'Aktif',
        ];
    }

    public function getPage()
    {
        return $this->hasOne(MasterPage::class, ['id' => 'page_id']);
    }

    public function getTable()
    {
        return $this->hasOne(DbTable::class, ['id' => 'table_id']);
    }

    public function getRenderConfig(): array
    {
        $seriesConfig = [];
        if ($this->series_config) {
            $decoded = json_decode($this->series_config, true);
            if (is_array($decoded)) {
                $seriesConfig = $decoded;
            }
        }
        $filterConfig = [];
        if ($this->filter_config) {
            $decoded = json_decode($this->filter_config, true);
            if (is_array($decoded)) {
                $filterConfig = $decoded;
            }
        }
        $extraConfig = [];
        if ($this->extra_config) {
            $decoded = json_decode($this->extra_config, true);
            if (is_array($decoded)) {
                $extraConfig = $decoded;
            }
        }
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'chart_type' => $this->chart_type,
            'table_id' => $this->table_id,
            'table_name' => $this->table_name,
            'source_type' => $this->source_type,
            'source_query' => $this->source_query,
            'height' => (int)$this->height,
            'theme' => $this->theme,
            'palette' => $this->palette,
            'animation' => $this->animation,
            'label_field' => $this->label_field,
            'value_field' => $this->value_field,
            'aggregation' => $this->aggregation,
            'group_by_field' => $this->group_by_field,
            'sort_field' => $this->sort_field,
            'sort_direction' => $this->sort_direction,
            'limit' => (int)$this->limit,
            'show_legend' => (bool)$this->show_legend,
            'show_label' => (bool)$this->show_label,
            'show_toolbar' => (bool)$this->show_toolbar,
            'show_grid' => (bool)$this->show_grid,
            'show_total' => (bool)$this->show_total,
            'series_config' => $seriesConfig,
            'filter_config' => $filterConfig,
            'extra_config' => $extraConfig,
        ];
    }
}
