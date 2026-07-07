<?php

namespace app\services;

use Yii;
use yii\base\Component;

class FilterRegistry extends Component
{
    private static $instance;
    private $operators = [];
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

        $this->registerOperator('=', [
            'name' => '=',
            'label' => 'Equals',
            'sql' => '=',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('!=', [
            'name' => '!=',
            'label' => 'Not Equals',
            'sql' => '!=',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('>', [
            'name' => '>',
            'label' => 'Greater Than',
            'sql' => '>',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('<', [
            'name' => '<',
            'label' => 'Less Than',
            'sql' => '<',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('>=', [
            'name' => '>=',
            'label' => 'Greater Than or Equal',
            'sql' => '>=',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('<=', [
            'name' => '<=',
            'label' => 'Less Than or Equal',
            'sql' => '<=',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('LIKE', [
            'name' => 'LIKE',
            'label' => 'Contains',
            'sql' => 'LIKE',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('NOT_LIKE', [
            'name' => 'NOT_LIKE',
            'label' => 'Not Contains',
            'sql' => 'NOT LIKE',
            'inputType' => 'text',
            'requiresValue' => true,
        ]);

        $this->registerOperator('IN', [
            'name' => 'IN',
            'label' => 'In List',
            'sql' => 'IN',
            'inputType' => 'textarea',
            'requiresValue' => true,
        ]);

        $this->registerOperator('NOT_IN', [
            'name' => 'NOT_IN',
            'label' => 'Not In List',
            'sql' => 'NOT IN',
            'inputType' => 'textarea',
            'requiresValue' => true,
        ]);

        $this->registerOperator('BETWEEN', [
            'name' => 'BETWEEN',
            'label' => 'Between',
            'sql' => 'BETWEEN',
            'inputType' => 'between',
            'requiresValue' => true,
        ]);

        $this->registerOperator('IS_NULL', [
            'name' => 'IS_NULL',
            'label' => 'Is Null',
            'sql' => 'IS NULL',
            'inputType' => 'none',
            'requiresValue' => false,
        ]);

        $this->registerOperator('IS_NOT_NULL', [
            'name' => 'IS_NOT_NULL',
            'label' => 'Is Not Null',
            'sql' => 'IS NOT NULL',
            'inputType' => 'none',
            'requiresValue' => false,
        ]);

        $this->registerOperator('DATE', [
            'name' => 'DATE',
            'label' => 'Date',
            'sql' => 'DATE',
            'inputType' => 'date',
            'requiresValue' => true,
        ]);

        $this->registerOperator('RELATIVE_DATE', [
            'name' => 'RELATIVE_DATE',
            'label' => 'Relative Date',
            'sql' => 'RELATIVE',
            'inputType' => 'relative_date',
            'requiresValue' => true,
        ]);
    }

    public function registerOperator($name, $config)
    {
        $this->operators[$name] = $config;
    }

    public function getOperator($name)
    {
        $this->loadDefaults();
        return $this->operators[$name] ?? null;
    }

    public function getAllOperators()
    {
        $this->loadDefaults();
        return $this->operators;
    }

    public function getOperatorOptions()
    {
        $this->loadDefaults();
        $options = [];
        foreach ($this->operators as $name => $config) {
            $options[] = [
                'value' => $name,
                'label' => $config['label'],
                'inputType' => $config['inputType'] ?? 'text',
                'requiresValue' => $config['requiresValue'] ?? true,
            ];
        }
        return $options;
    }
}
