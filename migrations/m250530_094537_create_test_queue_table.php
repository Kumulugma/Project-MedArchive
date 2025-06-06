<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%test_queue}}`.
 */
class m250530_094537_create_test_queue_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%test_queue}}', [
            'id' => $this->primaryKey(),
            'test_template_id' => $this->integer()->notNull(),
            'scheduled_date' => $this->date()->notNull(),
            'comment' => $this->text(),
            'status' => $this->string(20)->defaultValue('pending'),
            'reminder_sent' => $this->boolean()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-test_queue-test_template_id',
            '{{%test_queue}}',
            'test_template_id',
            '{{%test_templates}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-test_queue-test_template_id', '{{%test_queue}}');
        $this->dropTable('{{%test_queue}}');
    }
}
