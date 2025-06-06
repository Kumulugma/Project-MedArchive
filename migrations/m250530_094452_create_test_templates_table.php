<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%test_templates}}`.
 */
class m250530_094452_create_test_templates_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%test_templates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'description' => $this->text(),
            'status' => $this->smallInteger()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%test_templates}}');
    }
}
