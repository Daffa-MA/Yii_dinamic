<?php

namespace app\services;

use app\models\DbTable;
use Yii;

class TableService
{
    /**
     * Get all tables that are considered "User Tables".
     * These are tables created by users and should be visible in dropdowns.
     */
    public static function getUserTables(int $userId = null, int $projectId = null): array
    {
        $query = DbTable::find();
        
        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        if ($projectId !== null) {
            $query->andWhere(['project_id' => $projectId]);
        }
        
        $allTables = $query->all();
        $userTables = [];
        
        foreach ($allTables as $table) {
            if (!DbTable::isSystemTable($table->name)) {
                $userTables[] = $table;
            }
        }
        
        return $userTables;
    }

    /**
     * Get all tables that are considered "System Tables".
     */
    public static function getSystemTables(int $userId = null, int $projectId = null): array
    {
        $query = DbTable::find();
        
        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        if ($projectId !== null) {
            $query->andWhere(['project_id' => $projectId]);
        }
        
        $allTables = $query->all();
        $systemTables = [];
        
        foreach ($allTables as $table) {
            if (DbTable::isSystemTable($table->name)) {
                $systemTables[] = $table;
            }
        }
        
        return $systemTables;
    }

    /**
     * Get all tables (User + System).
     */
    public static function getAllTables(int $userId = null, int $projectId = null): array
    {
        $query = DbTable::find();
        
        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        if ($projectId !== null) {
            $query->andWhere(['project_id' => $projectId]);
        }
        
        return $query->all();
    }
    
    /**
     * Get list of user tables formatted for dropdown options.
     */
    public static function getUserTableOptions(int $userId = null, int $projectId = null): array
    {
        $tables = self::getUserTables($userId, $projectId);
        $options = [];
        foreach ($tables as $table) {
            $options[] = [
                'id' => $table->id,
                'name' => $table->name,
                'label' => $table->label ?: $table->name,
            ];
        }
        return $options;
    }
}
