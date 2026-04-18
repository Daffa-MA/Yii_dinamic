<?php

use yii\db\Migration;
use yii\db\Query;

class m260417_000003_create_projects_and_add_project_context extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        $this->createTable('projects', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(150)->notNull(),
            'description' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-projects-user_id', 'projects', 'user_id');
        $this->createIndex('uq-projects-user_id-name', 'projects', ['user_id', 'name'], true);
        if (!$isSqlite) {
            $this->addForeignKey('fk-projects-user_id', 'projects', 'user_id', 'users', 'id', 'CASCADE');
        }

        $this->addColumn('db_tables', 'project_id', $this->integer());
        $this->createIndex('idx-db_tables-project_id', 'db_tables', 'project_id');
        if (!$isSqlite) {
            $this->addForeignKey('fk-db_tables-project_id', 'db_tables', 'project_id', 'projects', 'id', 'CASCADE');
        }

        $this->addColumn('forms', 'project_id', $this->integer());
        $this->createIndex('idx-forms-project_id', 'forms', 'project_id');
        if (!$isSqlite) {
            $this->addForeignKey('fk-forms-project_id', 'forms', 'project_id', 'projects', 'id', 'CASCADE');
        }

        $this->backfillExistingProjectIds();

        try {
            $this->dropIndex('uq-db_tables-user_id-name', 'db_tables');
        } catch (\Throwable $e) {
            // Ignore when legacy index is missing.
        }

        try {
            $this->dropIndex('name', 'db_tables');
        } catch (\Throwable $e) {
            // Ignore when legacy index is missing.
        }

        $this->createIndex('uq-db_tables-user_project_name', 'db_tables', ['user_id', 'project_id', 'name'], true);
    }

    public function safeDown()
    {
        try {
            $this->dropIndex('uq-db_tables-user_project_name', 'db_tables');
        } catch (\Throwable $e) {
            // Ignore when index was already removed.
        }

        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-forms-project_id', 'forms');
            $this->dropForeignKey('fk-db_tables-project_id', 'db_tables');
        }

        $this->dropIndex('idx-forms-project_id', 'forms');
        $this->dropColumn('forms', 'project_id');

        $this->dropIndex('idx-db_tables-project_id', 'db_tables');
        $this->dropColumn('db_tables', 'project_id');

        try {
            $this->createIndex('uq-db_tables-user_id-name', 'db_tables', ['user_id', 'name'], true);
        } catch (\Throwable $e) {
            // Ignore when index already exists.
        }

        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-projects-user_id', 'projects');
        }

        $this->dropIndex('uq-projects-user_id-name', 'projects');
        $this->dropIndex('idx-projects-user_id', 'projects');
        $this->dropTable('projects');
    }

    private function backfillExistingProjectIds(): void
    {
        $formUserIds = (new Query())
            ->select('user_id')
            ->distinct()
            ->from('forms')
            ->where(['not', ['user_id' => null]])
            ->column($this->db);

        $tableUserIds = (new Query())
            ->select('user_id')
            ->distinct()
            ->from('db_tables')
            ->where(['not', ['user_id' => null]])
            ->column($this->db);

        $userIds = array_values(array_unique(array_map('intval', array_merge($formUserIds, $tableUserIds))));

        foreach ($userIds as $userId) {
            if ($userId <= 0) {
                continue;
            }

            $projectId = $this->ensureDefaultProjectForUser($userId);
            if ($projectId === null) {
                continue;
            }

            $this->update('forms', ['project_id' => $projectId], ['and', ['user_id' => $userId], ['project_id' => null]]);
            $this->update('db_tables', ['project_id' => $projectId], ['and', ['user_id' => $userId], ['project_id' => null]]);
        }
    }

    private function ensureDefaultProjectForUser(int $userId): ?int
    {
        $existingId = (new Query())
            ->select('id')
            ->from('projects')
            ->where(['user_id' => $userId, 'name' => 'Default Project'])
            ->scalar($this->db);

        if ($existingId !== false && $existingId !== null) {
            return (int)$existingId;
        }

        $baseName = 'Default Project';
        $attempt = 0;

        while ($attempt < 20) {
            $attempt++;
            $name = $attempt === 1 ? $baseName : $baseName . ' ' . $attempt;

            try {
                $this->insert('projects', [
                    'user_id' => $userId,
                    'name' => $name,
                    'description' => 'Automatically generated project for existing records.',
                ]);

                return (int)$this->db->getLastInsertID();
            } catch (\Throwable $e) {
                $duplicate = stripos($e->getMessage(), 'duplicate') !== false
                    || stripos($e->getMessage(), 'unique') !== false
                    || stripos($e->getMessage(), 'constraint') !== false;

                if (!$duplicate) {
                    throw $e;
                }
            }
        }

        return null;
    }
}
