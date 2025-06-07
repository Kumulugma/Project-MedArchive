<?php

use yii\db\Migration;

class m250607_092704_add_user_profile_fields extends Migration
{
     /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Dodaj kolumny jeśli nie istnieją
        $tableSchema = $this->db->getTableSchema('{{%users}}');
        
        if (!isset($tableSchema->columns['first_name'])) {
            $this->addColumn('{{%users}}', 'first_name', $this->string(100)->null());
        }
        
        if (!isset($tableSchema->columns['last_name'])) {
            $this->addColumn('{{%users}}', 'last_name', $this->string(100)->null());
        }
        
        if (!isset($tableSchema->columns['phone'])) {
            $this->addColumn('{{%users}}', 'phone', $this->string(20)->null());
        }
        
        if (!isset($tableSchema->columns['last_login_at'])) {
            $this->addColumn('{{%users}}', 'last_login_at', $this->integer()->null());
        }
        
        // Dodaj indeksy
        $this->createIndex('idx-users-last_login_at', '{{%users}}', 'last_login_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-users-last_login_at', '{{%users}}');
        
        $this->dropColumn('{{%users}}', 'first_name');
        $this->dropColumn('{{%users}}', 'last_name');
        $this->dropColumn('{{%users}}', 'phone');
        $this->dropColumn('{{%users}}', 'last_login_at');
    }
}
