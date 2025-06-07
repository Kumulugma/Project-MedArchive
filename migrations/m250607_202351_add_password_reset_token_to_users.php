<?php

use yii\db\Migration;

class m250607_202351_add_password_reset_token_to_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Dodaj kolumnę password_reset_token jeśli nie istnieje
        $tableSchema = $this->db->getTableSchema('{{%users}}');
        
        if (!isset($tableSchema->columns['password_reset_token'])) {
            $this->addColumn('{{%users}}', 'password_reset_token', $this->string()->unique()->null());
        }
        
        // Dodaj indeks dla szybszego wyszukiwania
        $this->createIndex('idx-users-password_reset_token', '{{%users}}', 'password_reset_token');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-users-password_reset_token', '{{%users}}');
        $this->dropColumn('{{%users}}', 'password_reset_token');
    }
}
