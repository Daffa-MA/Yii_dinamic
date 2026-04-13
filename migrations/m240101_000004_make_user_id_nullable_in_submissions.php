<?php

use yii\db\Migration;

class m240101_000004_make_user_id_nullable_in_submissions extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        if ($isSqlite) {
            // SQLite cannot alter columns; m240101_000003 already creates user_id as nullable.
            return true;
        }

        $this->dropForeignKey('fk-submissions-user_id', 'form_submissions');
        
        // Alter column to allow NULL
        $this->alterColumn('form_submissions', 'user_id', $this->integer());
        
        $this->addForeignKey(
            'fk-submissions-user_id',
            'form_submissions',
            'user_id',
            'users',
            'id',
            'SET NULL'
        );
    }

    public function safeDown()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        if ($isSqlite) {
            return true;
        }

        $this->dropForeignKey('fk-submissions-user_id', 'form_submissions');
        
        // Set NULL user_ids to a default user or delete them
        $this->delete('form_submissions', ['user_id' => null]);
        
        $this->alterColumn('form_submissions', 'user_id', $this->integer()->notNull());
        
        $this->addForeignKey(
            'fk-submissions-user_id',
            'form_submissions',
            'user_id',
            'users',
            'id',
            'CASCADE'
        );
    }
}
