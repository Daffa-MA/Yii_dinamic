<?php

use yii\db\Migration;

class m260413_095239_add_firebase_fields_to_form_submissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('form_submissions', 'firebase_uid', $this->string(255)->null()->after('user_id'));
        $this->addColumn('form_submissions', 'firebase_email', $this->string(255)->null()->after('firebase_uid'));
        $this->addColumn('form_submissions', 'firebase_name', $this->string(255)->null()->after('firebase_email'));
        
        $this->createIndex('idx-submissions-firebase_uid', 'form_submissions', 'firebase_uid');
        $this->createIndex('idx-submissions-firebase_email', 'form_submissions', 'firebase_email');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260413_095239_add_firebase_fields_to_form_submissions cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260413_095239_add_firebase_fields_to_form_submissions cannot be reverted.\n";

        return false;
    }
    */
}
