<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%parameter_norms}}`.
 */
class m250608_082524_create_parameter_norms_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%parameter_norms}}', [
            'id' => $this->primaryKey(),
            'parameter_id' => $this->integer()->notNull(),
            'age_from' => $this->integer()->null()->comment('Wiek od (w miesiącach)'),
            'age_to' => $this->integer()->null()->comment('Wiek do (w miesiącach)'),
            'gender' => $this->string(1)->null()->comment('Płeć: M/F/null dla obu'),
            'min_value' => $this->decimal(10, 4)->null()->comment('Minimalna wartość normy'),
            'max_value' => $this->decimal(10, 4)->null()->comment('Maksymalna wartość normy'),
            'unit' => $this->string(50)->null()->comment('Jednostka'),
            'description' => $this->text()->null()->comment('Opis normy'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Dodanie klucza obcego
        $this->addForeignKey(
            'fk-parameter_norms-parameter_id',
            '{{%parameter_norms}}',
            'parameter_id',
            '{{%test_parameters}}',
            'id',
            'CASCADE'
        );

        // Dodanie indeksu dla lepszej wydajności
        $this->createIndex(
            'idx-parameter_norms-parameter_id',
            '{{%parameter_norms}}',
            'parameter_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Usunięcie klucza obcego
        $this->dropForeignKey(
            'fk-parameter_norms-parameter_id',
            '{{%parameter_norms}}'
        );

        // Usunięcie indeksu
        $this->dropIndex(
            'idx-parameter_norms-parameter_id',
            '{{%parameter_norms}}'
        );

        $this->dropTable('{{%parameter_norms}}');
    }
}
