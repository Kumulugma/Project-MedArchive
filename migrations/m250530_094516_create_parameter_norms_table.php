<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%parameter_norms}}`.
 */
class m250530_094516_create_parameter_norms_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%parameter_norms}}', [
            'id' => $this->primaryKey(),
            'parameter_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'type' => $this->string(50)->notNull(),
            'min_value' => $this->decimal(10, 4),
            'max_value' => $this->decimal(10, 4),
            'threshold_value' => $this->decimal(10, 4),
            'threshold_direction' => $this->string(10),
            'thresholds_config' => $this->text(),
            'is_primary' => $this->boolean()->defaultValue(false),
            'conversion_factor' => $this->decimal(10, 6)->defaultValue(1),
            'conversion_offset' => $this->decimal(10, 4)->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-parameter_norms-parameter_id',
            '{{%parameter_norms}}',
            'parameter_id',
            '{{%test_parameters}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-parameter_norms-parameter_id', '{{%parameter_norms}}');
        $this->dropTable('{{%parameter_norms}}');
    }
}
