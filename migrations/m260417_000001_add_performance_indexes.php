<?php

use yii\db\Migration;

class m260417_000001_add_performance_indexes extends Migration
{
    public function safeUp()
    {
        $this->createIndex('idx-forms-user_created_id', 'forms', ['user_id', 'created_at', 'id']);
        $this->createIndex('idx-submissions-form_created_id', 'form_submissions', ['form_id', 'created_at', 'id']);
        $this->createIndex('idx-published_forms-form_user', 'published_forms', ['form_id', 'user_id']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-published_forms-form_user', 'published_forms');
        $this->dropIndex('idx-submissions-form_created_id', 'form_submissions');
        $this->dropIndex('idx-forms-user_created_id', 'forms');
    }
}
