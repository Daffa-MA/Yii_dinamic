<?php

namespace app\services;

use app\models\DbTable;
use app\models\MasterPageChart;
use Yii;
use yii\db\Query;
use yii\helpers\Json;

class MasterChartService
{
    private static $colorPalettes = [
        'modern' => ['#4f46e5', '#06b6d4', '#22c55e', '#eab308', '#ef4444', '#a855f7', '#ec4899', '#14b8a6', '#f97316', '#6366f1'],
        'material' => ['#2196f3', '#4caf50', '#ffeb3b', '#ff5722', '#9c27b0', '#00bcd4', '#ff9800', '#8bc34a', '#e91e63', '#3f51b5'],
        'pastel' => ['#a8d5e2', '#b5e6c3', '#f9e7a0', '#f7c5a0', '#d5a8e2', '#a0d4f7', '#f7a0c5', '#c5e6a0', '#f7d5a0', '#a8e2d5'],
        'dark' => ['#1e293b', '#334155', '#475569', '#64748b', '#94a3b8', '#0f172a', '#1e3a5f', '#2d4a3e', '#4a2d3e', '#3e2d4a'],
        'gradient' => ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'],
    ];

    private static $chartTypeMap = [
        'bar' => 'bar',
        'bar_horizontal' => 'bar',
        'line' => 'line',
        'area' => 'area',
        'pie' => 'pie',
        'donut' => 'donut',
        'radar' => 'radar',
        'polar_area' => 'polarArea',
        'bubble' => 'bubble',
        'scatter' => 'scatter',
        'stacked_bar' => 'bar',
        'stacked_area' => 'area',
        'mixed' => 'line',
        'multi_series' => 'bar',
    ];

    public function getChartConfig(int $chartId): ?array
    {
        $chart = MasterPageChart::findOne($chartId);
        if (!$chart || !$chart->is_active) return null;
        return $chart->getRenderConfig();
    }

    public function getChartsForPage(int $pageId): array
    {
        $charts = MasterPageChart::find()
            ->where(['page_id' => $pageId, 'is_active' => 1])
            ->orderBy(['position' => SORT_ASC])
            ->all();
        $result = [];
        foreach ($charts as $chart) {
            $result[] = $chart->getRenderConfig();
        }
        return $result;
    }

    public function buildChartData(int $chartId, array $extraFilters = []): array
    {
        $config = $this->getChartConfig($chartId);
        if (!$config) {
            return ['success' => false, 'message' => 'Chart tidak ditemukan.'];
        }

        try {
            $rows = $this->fetchData($config, $extraFilters);
            $chartData = $this->formatChartData($rows, $config);
            $palette = $this->getPalette($config['palette'], count($chartData['labels']));

            return [
                'success' => true,
                'config' => $config,
                'chart' => $chartData,
                'palette' => $palette,
            ];
        } catch (\Throwable $e) {
            Yii::warning('Chart data error: ' . $e->getMessage(), 'chart');
            return [
                'success' => false,
                'message' => 'Gagal memuat data chart: ' . $e->getMessage(),
            ];
        }
    }

    private function fetchData(array $config, array $extraFilters): array
    {
        $tableName = $config['table_name'] ?? '';
        $sourceType = $config['source_type'] ?? 'table';

        if ($sourceType === 'query' && !empty($config['source_query'])) {
            return $this->executeCustomQuery($config['source_query']);
        }

        if (empty($tableName)) {
            if (!empty($config['table_id'])) {
                $dbTable = DbTable::findOne($config['table_id']);
                if ($dbTable) $tableName = $dbTable->name;
            }
        }
        if (empty($tableName)) return [];

        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        if (!$schema) return [];

        $query = (new Query())->from($tableName);

        $this->applyAggregation($query, $config, $schema);
        $this->applyExtraFilters($query, $extraFilters, $schema);
        $this->applySortAndLimit($query, $config, $schema);

        return $query->all(Yii::$app->db);
    }

    private function executeCustomQuery(string $sql): array
    {
        try {
            return Yii::$app->db->createCommand($sql)->queryAll();
        } catch (\Throwable $e) {
            Yii::warning('Custom query error: ' . $e->getMessage(), 'chart');
            return [];
        }
    }

    private function applyAggregation(Query $query, array $config, $schema): void
    {
        $aggregation = $config['aggregation'] ?? 'count';
        $valueField = $config['value_field'] ?? '';
        $labelField = $config['label_field'] ?? '';
        $groupByField = $config['group_by_field'] ?? '';

        $multiSeries = !empty($config['series_config']);

        if ($multiSeries) {
            $series = $config['series_config'];
            $selects = [];
            foreach ($series as $i => $s) {
                $field = $s['field'] ?? $valueField;
                $aggr = $s['aggregation'] ?? $aggregation;
                $alias = 'series_' . $i;
                $selects[] = $this->buildAggregationExpression($aggr, $field, $alias, $schema);
            }
            if ($groupByField && isset($schema->columns[$groupByField])) {
                $query->select(array_merge([$groupByField], $selects));
                $query->groupBy($groupByField);
            } else {
                $query->select($selects);
            }
            } elseif ($groupByField && isset($schema->columns[$groupByField])) {
                if ($labelField && isset($schema->columns[$labelField]) && $labelField !== $groupByField) {
                    $query->select([
                        'label' => $labelField,
                        'group' => $groupByField,
                        'value' => $this->buildAggregationExpression($aggregation, $valueField, 'value', $schema, false),
                    ]);
                    $query->groupBy([$groupByField, $labelField]);
                } else {
                    $query->select([
                        'label' => $groupByField,
                        'value' => $this->buildAggregationExpression($aggregation, $valueField, 'value', $schema, false),
                    ]);
                    $query->groupBy($groupByField);
                }
        } elseif ($valueField && isset($schema->columns[$valueField])) {
            $query->select([
                'value' => $this->buildAggregationExpression($aggregation, $valueField, 'value', $schema, false),
            ]);
        } else {
            $query->select([
                'value' => $this->buildAggregationExpression('count', '*', 'value', $schema, false),
            ]);
        }
    }

    private function buildAggregationExpression(string $aggregation, string $field, string $alias, $schema, bool $includeAlias = true): string
    {
        $fieldExpr = ($field === '*' || $field === '') ? '*' : (isset($schema->columns[$field]) ? ("\"" . str_replace('"', '""', $field) . "\"") : $field);
        $expr = '';
        switch ($aggregation) {
            case 'sum': $expr = "SUM({$fieldExpr})"; break;
            case 'avg': $expr = "AVG({$fieldExpr})"; break;
            case 'min': $expr = "MIN({$fieldExpr})"; break;
            case 'max': $expr = "MAX({$fieldExpr})"; break;
            case 'count_distinct': $expr = "COUNT(DISTINCT {$fieldExpr})"; break;
            default: $expr = "COUNT({$fieldExpr})"; break;
        }
        if ($includeAlias && $alias !== '') {
            $expr .= ' AS "' . str_replace('"', '""', $alias) . '"';
        }
        return $expr;
    }

    private function applyExtraFilters(Query $query, array $filters, $schema): void
    {
        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null) continue;
            if (strpos($key, 'dt_search_') === 0) {
                $search = trim((string)$value);
                if ($search === '') continue;
                $or = ['or'];
                foreach ($schema->columns as $col) {
                    if (!in_array($col->name, ['id', 'created_at', 'updated_at'], true)) {
                        $or[] = ['like', $col->name, $search];
                    }
                }
                if (count($or) > 1) {
                    $query->andWhere($or);
                }
            } elseif (strpos($key, 'dt_filter_') === 0) {
                $field = substr($key, strrpos($key, '_') + 1);
                if (isset($schema->columns[$field])) {
                    $query->andWhere([$field => $value]);
                }
            } elseif (isset($schema->columns[$key])) {
                $query->andWhere([$key => $value]);
            }
        }
    }

    private function applySortAndLimit(Query $query, array $config, $schema): void
    {
        $sortField = $config['sort_field'] ?? '';
        $direction = $config['sort_direction'] ?? 'asc';
        $limit = (int)($config['limit'] ?? 10);

        if ($sortField && isset($schema->columns[$sortField])) {
            $query->orderBy([$sortField => $direction === 'desc' ? SORT_DESC : SORT_ASC]);
        } elseif ($config['group_by_field']) {
            $query->orderBy(['value' => SORT_DESC]);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }
    }

    private function formatChartData(array $rows, array $config): array
    {
        $chartType = $config['chart_type'] ?? 'bar';
        $multiSeries = !empty($config['series_config']);

        if (empty($rows)) {
            return ['labels' => [], 'series' => [], 'total' => 0];
        }

        if ($multiSeries) {
            return $this->formatMultiSeriesData($rows, $config);
        }

        $labels = [];
        $values = [];
        $total = 0;

        foreach ($rows as $row) {
            $label = $row['label'] ?? $row['group'] ?? '-';
            $value = (float)($row['value'] ?? 0);
            $labels[] = $label;
            $values[] = $value;
            $total += $value;
        }

        if (in_array($chartType, ['pie', 'donut'], true)) {
            return [
                'labels' => $labels,
                'series' => $values,
                'total' => $total,
            ];
        }

        if (in_array($chartType, ['radar', 'polar_area'], true)) {
            return [
                'labels' => $labels,
                'series' => [['name' => $config['title'] ?? 'Data', 'data' => $values]],
                'total' => $total,
            ];
        }

        if (in_array($chartType, ['bubble', 'scatter'], true)) {
            $scatterData = [];
            foreach ($rows as $i => $row) {
                $x = (float)($row['label'] ?? $row['group'] ?? $i);
                $y = (float)($row['value'] ?? 0);
                $scatterData[] = ['x' => $x, 'y' => $y];
            }
            return [
                'labels' => $labels,
                'series' => [['name' => $config['title'] ?? 'Data', 'data' => $scatterData]],
                'total' => $total,
            ];
        }

        $stacked = in_array($chartType, ['stacked_bar', 'stacked_area'], true);

        return [
            'labels' => $labels,
            'series' => [[
                'name' => $config['title'] ?? 'Data',
                'data' => $values,
                'stacked' => $stacked,
            ]],
            'total' => $total,
            'stacked' => $stacked,
        ];
    }

    private function formatMultiSeriesData(array $rows, array $config): array
    {
        $series = [];
        $labels = [];
        $seriesConfig = $config['series_config'];
        $groupByField = $config['group_by_field'] ?? '';

        foreach ($rows as $row) {
            $label = $row['label'] ?? $row[$groupByField] ?? '-';
            if (!in_array($label, $labels, true)) $labels[] = $label;
        }

        foreach ($seriesConfig as $i => $s) {
            $name = $s['name'] ?? ('Series ' . ($i + 1));
            $alias = 'series_' . $i;
            $data = [];
            foreach ($rows as $row) {
                $label = $row['label'] ?? $row[$groupByField] ?? '-';
                $data[] = (float)($row[$alias] ?? 0);
            }
            $series[] = ['name' => $name, 'data' => $data];
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'total' => 0,
            'multiSeries' => true,
        ];
    }

    public function getPalette(string $name, int $count): array
    {
        $palette = self::$colorPalettes[$name] ?? self::$colorPalettes['modern'];
        if ($count <= count($palette)) {
            return array_slice($palette, 0, $count);
        }
        $result = $palette;
        while (count($result) < $count) {
            $result[] = $this->generateRandomColor();
        }
        return $result;
    }

    private function generateRandomColor(): string
    {
        return sprintf('#%06x', rand(0, 0xffffff));
    }
}
