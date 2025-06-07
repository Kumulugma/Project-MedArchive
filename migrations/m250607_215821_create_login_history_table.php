<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%login_history}}`.
 */
class m250607_215821_create_login_history_table extends Migration
{
     public function safeUp()
    {
        $this->createTable('{{%login_history}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'ip_address' => $this->string(45)->notNull(),
            'user_agent' => $this->string(500),
            'login_time' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'logout_time' => $this->timestamp()->null(),
            'success' => $this->boolean()->defaultValue(true),
            'location' => $this->string(255),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-login_history-user_id',
            '{{%login_history}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );

        $this->createIndex('idx-login_history-user_id', '{{%login_history}}', 'user_id');
        $this->createIndex('idx-login_history-login_time', '{{%login_history}}', 'login_time');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-login_history-user_id', '{{%login_history}}');
        $this->dropTable('{{%login_history}}');
    }
}
