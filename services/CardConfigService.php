<?php

namespace app\services;

use Yii;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;

class CardConfigService
{
    public function getBuilderConfig()
    {
        $widgetRegistry = WidgetRegistry::getInstance();
        $datasourceRegistry = DatasourceRegistry::getInstance();
        $aggregateRegistry = AggregateRegistry::getInstance();
        $iconRegistry = IconRegistry::getInstance();
        $filterRegistry = FilterRegistry::getInstance();
        $formatterRegistry = FormatterRegistry::getInstance();
        $refreshRegistry = RefreshRegistry::getInstance();

        return [
            'widget' => $widgetRegistry->get('card'),
            'widgets' => $widgetRegistry->getAll(),
            'datasources' => $datasourceRegistry->getOptions(),
            'aggregates' => $aggregateRegistry->getOptions(),
            'iconLibraries' => $iconRegistry->getLibraryOptions(),
            'filterOperators' => $filterRegistry->getOperatorOptions(),
            'formats' => $formatterRegistry->getOptions(),
            'refreshStrategies' => $refreshRegistry->getOptions(),
            'tables' => $this->getAvailableTables(),
            'shadowOptions' => $this->getShadowOptions(),
            'borderOptions' => $this->getBorderOptions(),
            'bgTypes' => $this->getBgTypes(),
            'iconShapes' => $this->getIconShapes(),
            'alignOptions' => $this->getAlignOptions(),
            'fontWeightOptions' => $this->getFontWeightOptions(),
            'fontFamilyOptions' => $this->getFontFamilyOptions(),
            'refreshIntervals' => $this->getRefreshIntervals(),
        ];
    }

    public function getAvailableTables()
    {
        try {
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            $effectiveUserId = (new CommanderAuthContext())->isSuperAdmin() ? null : (int)(Yii::$app->user->id ?? 0);
            if ($effectiveUserId === 0) $effectiveUserId = null;

            return \app\services\TableService::getUserTableOptions($effectiveUserId, $activeProjectId);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getTableColumns($tableId)
    {
        try {
            $table = DbTable::findOne($tableId);
            if (!$table) return [];

            $columns = DbTableColumn::find()
                ->where(['table_id' => $tableId])
                ->orderBy(['sort_order' => SORT_ASC])
                ->asArray()
                ->all();

            return array_map(function ($c) {
                $dataType = strtolower($c['type'] ?? '');
                $isNumeric = in_array($dataType, [
                    'int', 'tinyint', 'smallint', 'mediumint', 'bigint',
                    'float', 'double', 'decimal', 'numeric',
                    'real', 'bit', 'boolean', 'serial'
                ]);

                return [
                    'id' => $c['id'],
                    'name' => $c['name'] ?? $c['column_name'] ?? '',
                    'label' => $c['label'] ?: ($c['name'] ?? $c['column_name'] ?? ''),
                    'dataType' => $dataType,
                    'isNumeric' => $isNumeric,
                ];
            }, $columns);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getCardPreviewData($config)
    {
        try {
            if (empty($config['datasource']) || $config['datasource'] === 'static') {
                return [
                    'value' => null,
                    'formatted' => $config['title'] ?? 'Card Title',
                    'aggregate' => null,
                    'rawValue' => null,
                ];
            }

            if ($config['datasource'] !== 'database') {
                return ['value' => null, 'formatted' => 'Data dari ' . $config['datasource'], 'aggregate' => null, 'rawValue' => null];
            }

            $aggregate = $config['aggregate'] ?? 'COUNT';
            $tableId = $config['tableId'] ?? null;

            if (!$tableId) {
                return ['value' => null, 'formatted' => 'Pilih tabel terlebih dahulu', 'aggregate' => null, 'rawValue' => null];
            }

            $table = DbTable::findOne($tableId);
            if (!$table) {
                return ['value' => null, 'formatted' => 'Tabel tidak ditemukan', 'aggregate' => null, 'rawValue' => null];
            }

            $value = $this->executeAggregateQuery($table->name, $aggregate, $config['column'] ?? null, $config['filterJson'] ?? '[]', $config['customSql'] ?? '', !empty($config['timeFilterEnabled']), $config['timeFilterPeriod'] ?? 'all', $config['timeFilterColumn'] ?? '');
            $formatted = $this->formatValue($value, $config);

            return [
                'value' => $value,
                'formatted' => $formatted,
                'aggregate' => $aggregate,
                'rawValue' => $value,
            ];
        } catch (\Exception $e) {
            return [
                'value' => null,
                'formatted' => null,
                'aggregate' => null,
                'rawValue' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function executeAggregateQuery($tableName, $aggregate, $column, $filterJson, $customSql = '', $timeFilterEnabled = false, $timeFilterPeriod = 'all', $timeFilterColumn = '')
    {
        $db = Yii::$app->db;
        $schema = $db->schema;
        $tableName = $schema->quoteTableName($tableName);

        $aggregateRegistry = AggregateRegistry::getInstance();
        $aggConfig = $aggregateRegistry->get($aggregate);

        if (!$aggConfig) {
            return 0;
        }

        $sqlFunction = $aggConfig['sqlFunction'];

        if ($aggregate === 'CUSTOM' && !empty(trim($customSql))) {
            $expression = trim($customSql);
            $sql = "SELECT {$expression} AS value FROM {$tableName}";
        } elseif ($aggregate === 'COUNT') {
            $sql = "SELECT COUNT(*) AS value FROM {$tableName}";
        } elseif ($aggregate === 'DISTINCT_COUNT' && $column) {
            $col = $schema->quoteColumnName($column);
            $sql = "SELECT COUNT(DISTINCT {$col}) AS value FROM {$tableName}";
        } elseif ($sqlFunction && $column) {
            $col = $schema->quoteColumnName($column);
            $sql = "SELECT {$sqlFunction}({$col}) AS value FROM {$tableName}";
        } else {
            $sql = "SELECT COUNT(*) AS value FROM {$tableName}";
        }

        $filters = json_decode($filterJson, true) ?? [];
        $whereClauses = [];
        $params = [];

        if (!empty($filters)) {
            $this->buildWhereClause($filters, $whereClauses, $params, $schema);
        }

        if ($timeFilterEnabled) {
            if (!$timeFilterColumn) {
                $timeFilterColumn = $this->detectDateColumn($tableName);
            }
            if ($timeFilterColumn) {
                $col = $schema->quoteColumnName($timeFilterColumn);
                $timeSql = $this->getTimeFilterSql($timeFilterPeriod, $col);
                if ($timeSql) {
                    $whereClauses[] = $timeSql;
                }
            }
        }

        if (!empty($whereClauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $result = $db->createCommand($sql, $params)->queryOne();
        return $result['value'] ?? 0;
    }

    private function getTimeFilterSql($period, $quotedColumn)
    {
        switch ($period) {
            case 'today':
                return "DATE({$quotedColumn}) = CURDATE()";
            case 'yesterday':
                return "DATE({$quotedColumn}) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            case 'last_7_days':
                return "DATE({$quotedColumn}) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'last_30_days':
                return "DATE({$quotedColumn}) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            case 'this_month':
                return "DATE({$quotedColumn}) >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            case 'last_month':
                return "DATE({$quotedColumn}) >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) AND DATE({$quotedColumn}) < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            case 'this_year':
                return "YEAR({$quotedColumn}) = YEAR(CURDATE())";
            default:
                return '';
        }
    }

    private function detectDateColumn($tableName)
    {
        try {
            $tableName = trim($tableName, '`"');
            $tableSchema = Yii::$app->db->schema->getTableSchema($tableName);
            if (!$tableSchema) return '';

            $dateTypes = ['date', 'datetime', 'timestamp'];
            foreach ($tableSchema->columns as $column) {
                $type = strtolower($column->type);
                if (in_array($type, $dateTypes)) {
                    return $column->name;
                }
            }
        } catch (\Exception $e) {
        }
        return '';
    }

    private function buildWhereClause($filters, &$clauses, &$params, $schema, $groupOperator = 'AND')
    {
        foreach ($filters as $filter) {
            if (isset($filter['group'])) {
                $subClauses = [];
                $subOperator = ($filter['groupOperator'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
                $this->buildWhereClause($filter['conditions'] ?? $filter['group'], $subClauses, $params, $schema, $subOperator);
                if (!empty($subClauses)) {
                    $clauses[] = '(' . implode(" {$subOperator} ", $subClauses) . ')';
                }
                continue;
            }

            $field = $filter['field'] ?? '';
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? '';
            $paramKey = 'p' . count($params);

            if (!$field) continue;

            $col = $schema->quoteColumnName($field);

            switch ($operator) {
                case '=':
                    $clauses[] = "{$col} = :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
                case '!=':
                    $clauses[] = "{$col} != :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
                case '>':
                    $clauses[] = "{$col} > :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
                case '<':
                    $clauses[] = "{$col} < :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
                case '>=':
                    $clauses[] = "{$col} >= :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
                case '<=':
                    $clauses[] = "{$col} <= :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
                case 'LIKE':
                    $clauses[] = "{$col} LIKE :{$paramKey}";
                    $params[$paramKey] = '%' . $value . '%';
                    break;
                case 'NOT_LIKE':
                    $clauses[] = "{$col} NOT LIKE :{$paramKey}";
                    $params[$paramKey] = '%' . $value . '%';
                    break;
                case 'IN':
                    $vals = array_map('trim', explode(',', $value));
                    $placeholders = [];
                    foreach ($vals as $i => $v) {
                        $pk = "{$paramKey}_{$i}";
                        $placeholders[] = ":{$pk}";
                        $params[$pk] = $v;
                    }
                    $clauses[] = "{$col} IN (" . implode(',', $placeholders) . ")";
                    break;
                case 'NOT_IN':
                    $vals = array_map('trim', explode(',', $value));
                    $placeholders = [];
                    foreach ($vals as $i => $v) {
                        $pk = "{$paramKey}_{$i}";
                        $placeholders[] = ":{$pk}";
                        $params[$pk] = $v;
                    }
                    $clauses[] = "{$col} NOT IN (" . implode(',', $placeholders) . ")";
                    break;
                case 'BETWEEN':
                    $parts = array_map('trim', explode(',', $value));
                    $pk1 = "{$paramKey}_1";
                    $pk2 = "{$paramKey}_2";
                    $clauses[] = "{$col} BETWEEN :{$pk1} AND :{$pk2}";
                    $params[$pk1] = $parts[0] ?? '';
                    $params[$pk2] = $parts[1] ?? '';
                    break;
                case 'IS_NULL':
                    $clauses[] = "{$col} IS NULL";
                    break;
                case 'IS_NOT_NULL':
                    $clauses[] = "{$col} IS NOT NULL";
                    break;
                case 'DATE':
                    $clauses[] = "DATE({$col}) = :{$paramKey}";
                    $params[$paramKey] = $value;
                    break;
            }
        }
    }

    private function formatValue($value, $config)
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $format = $config['outputFormat'] ?? 'auto';
        $decimal = (int)($config['numberDecimal'] ?? 0);
        $separator = $config['numberSeparator'] ?? ',';
        $prefix = $config['numberPrefix'] ?? '';
        $suffix = $config['numberSuffix'] ?? '';
        $locale = $config['numberLocale'] ?? 'id-ID';

        if (!is_numeric($value)) {
            return $value;
        }

        $num = (float) $value;

        switch ($format) {
            case 'currency':
                try {
                    $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
                    return $fmt->format($num);
                } catch (\Exception $e) {
                    return $prefix . number_format($num, $decimal, '.', $separator) . $suffix;
                }

            case 'percentage':
                return number_format($num * 100, $decimal) . '%';

            case 'number':
                return $prefix . number_format($num, $decimal, '.', $separator) . $suffix;

            default:
                if ($num == (int)$num) {
                    return $prefix . number_format($num, 0, '.', $separator) . $suffix;
                }
                return $prefix . number_format($num, $decimal, '.', $separator) . $suffix;
        }
    }

    private function getShadowOptions()
    {
        return [
            ['value' => 'none', 'label' => 'None'],
            ['value' => 'sm', 'label' => 'Small'],
            ['value' => 'md', 'label' => 'Medium'],
            ['value' => 'lg', 'label' => 'Large'],
            ['value' => 'xl', 'label' => 'Extra Large'],
            ['value' => '2xl', 'label' => '2X Large'],
            ['value' => 'inner', 'label' => 'Inner'],
            ['value' => 'colored', 'label' => 'Colored'],
        ];
    }

    private function getBorderOptions()
    {
        return [
            ['value' => 'none', 'label' => 'None'],
            ['value' => 'solid', 'label' => 'Solid'],
            ['value' => 'dashed', 'label' => 'Dashed'],
            ['value' => 'dotted', 'label' => 'Dotted'],
            ['value' => 'double', 'label' => 'Double'],
        ];
    }

    private function getBgTypes()
    {
        return [
            ['value' => 'solid', 'label' => 'Solid Color'],
            ['value' => 'gradient', 'label' => 'Gradient'],
            ['value' => 'image', 'label' => 'Image'],
            ['value' => 'pattern', 'label' => 'Pattern'],
            ['value' => 'glass', 'label' => 'Glass / Frosted'],
            ['value' => 'transparent', 'label' => 'Transparent'],
        ];
    }

    private function getIconShapes()
    {
        return [
            ['value' => 'none', 'label' => 'None'],
            ['value' => 'circle', 'label' => 'Circle'],
            ['value' => 'rounded', 'label' => 'Rounded'],
            ['value' => 'square', 'label' => 'Square'],
        ];
    }

    private function getAlignOptions()
    {
        return [
            ['value' => 'left', 'label' => 'Left'],
            ['value' => 'center', 'label' => 'Center'],
            ['value' => 'right', 'label' => 'Right'],
        ];
    }

    private function getFontWeightOptions()
    {
        return [
            ['value' => '300', 'label' => 'Light (300)'],
            ['value' => '400', 'label' => 'Regular (400)'],
            ['value' => '500', 'label' => 'Medium (500)'],
            ['value' => '600', 'label' => 'Semi Bold (600)'],
            ['value' => '700', 'label' => 'Bold (700)'],
            ['value' => '800', 'label' => 'Extra Bold (800)'],
            ['value' => '900', 'label' => 'Black (900)'],
        ];
    }

    private function getFontFamilyOptions()
    {
        return [
            ['value' => '', 'label' => 'Default (System)'],
            ['value' => 'Inter', 'label' => 'Inter'],
            ['value' => 'Poppins', 'label' => 'Poppins'],
            ['value' => 'Roboto', 'label' => 'Roboto'],
            ['value' => 'Open Sans', 'label' => 'Open Sans'],
            ['value' => 'Montserrat', 'label' => 'Montserrat'],
            ['value' => 'Plus Jakarta Sans', 'label' => 'Plus Jakarta Sans'],
            ['value' => 'DM Sans', 'label' => 'DM Sans'],
        ];
    }

    private function getRefreshIntervals()
    {
        return [
            ['value' => '5', 'label' => '5 detik'],
            ['value' => '10', 'label' => '10 detik'],
            ['value' => '30', 'label' => '30 detik'],
            ['value' => '60', 'label' => '1 menit'],
            ['value' => '300', 'label' => '5 menit'],
            ['value' => 'custom', 'label' => 'Custom'],
        ];
    }
}
