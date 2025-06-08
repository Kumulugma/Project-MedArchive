<?php

use yii\db\Migration;

class m250608_111425_add_description_to_test_parameters extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Sprawdź czy kolumna już istnieje
        $tableSchema = $this->db->getTableSchema('test_parameters');
        if ($tableSchema && !isset($tableSchema->columns['description'])) {
            $this->addColumn('test_parameters', 'description', $this->text()->after('name'));
            echo "Added 'description' column to test_parameters table.\n";
        } else {
            echo "Column 'description' already exists in test_parameters table.\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Sprawdź czy kolumna istnieje przed usunięciem
        $tableSchema = $this->db->getTableSchema('test_parameters');
        if ($tableSchema && isset($tableSchema->columns['description'])) {
            $this->dropColumn('test_parameters', 'description');
            echo "Removed 'description' column from test_parameters table.\n";
        } else {
            echo "Column 'description' does not exist in test_parameters table.\n";
        }
    }
}
