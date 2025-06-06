<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%test_parameters}}`.
 */
class m250530_094507_create_test_parameters_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%test_parameters}}', [
            'id' => $this->primaryKey(),
            'test_template_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'unit' => $this->string(50),
            'type' => $this->string(50)->notNull(),
            'order_index' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-test_parameters-test_template_id',
            '{{%test_parameters}}',
            'test_template_id',
            '{{%test_templates}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-test_parameters-test_template_id', '{{%test_parameters}}');
        $this->dropTable('{{%test_parameters}}');
    }
}
