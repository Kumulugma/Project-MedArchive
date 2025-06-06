<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%result_values}}`.
 */
class m250530_094530_create_result_values_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%result_values}}', [
            'id' => $this->primaryKey(),
            'test_result_id' => $this->integer()->notNull(),
            'parameter_id' => $this->integer()->notNull(),
            'norm_id' => $this->integer(),
            'value' => $this->string()->notNull(),
            'normalized_value' => $this->decimal(10, 4),
            'is_abnormal' => $this->boolean()->defaultValue(false),
            'abnormality_type' => $this->string(20),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-result_values-test_result_id',
            '{{%result_values}}',
            'test_result_id',
            '{{%test_results}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-result_values-parameter_id',
            '{{%result_values}}',
            'parameter_id',
            '{{%test_parameters}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-result_values-norm_id',
            '{{%result_values}}',
            'norm_id',
            '{{%parameter_norms}}',
            'id',
            'SET NULL'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-result_values-test_result_id', '{{%result_values}}');
        $this->dropForeignKey('fk-result_values-parameter_id', '{{%result_values}}');
        $this->dropForeignKey('fk-result_values-norm_id', '{{%result_values}}');
        $this->dropTable('{{%result_values}}');
    }
}
