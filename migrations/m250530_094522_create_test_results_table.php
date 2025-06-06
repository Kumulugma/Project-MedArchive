<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%test_results}}`.
 */
class m250530_094522_create_test_results_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%test_results}}', [
            'id' => $this->primaryKey(),
            'test_template_id' => $this->integer()->notNull(),
            'test_date' => $this->date()->notNull(),
            'comment' => $this->text(),
            'has_abnormal_values' => $this->boolean()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-test_results-test_template_id',
            '{{%test_results}}',
            'test_template_id',
            '{{%test_templates}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-test_results-test_template_id', '{{%test_results}}');
        $this->dropTable('{{%test_results}}');
    }
}
