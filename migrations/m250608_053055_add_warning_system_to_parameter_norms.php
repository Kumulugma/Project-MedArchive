<?php

use yii\db\Migration;

class m250608_053055_add_warning_system_to_parameter_norms extends Migration
{
    public function safeUp()
    {
        // Dodaj pola do tabeli parameter_norms
        $this->addColumn('{{%parameter_norms}}', 'warning_enabled', $this->boolean()->defaultValue(true)->after('is_primary'));
        $this->addColumn('{{%parameter_norms}}', 'warning_margin_percent', $this->decimal(5,2)->defaultValue(10)->after('warning_enabled'));
        $this->addColumn('{{%parameter_norms}}', 'warning_margin_absolute', $this->decimal(10,3)->null()->after('warning_margin_percent'));
        $this->addColumn('{{%parameter_norms}}', 'caution_margin_percent', $this->decimal(5,2)->defaultValue(5)->after('warning_margin_absolute'));
        $this->addColumn('{{%parameter_norms}}', 'caution_margin_absolute', $this->decimal(10,3)->null()->after('caution_margin_percent'));
        $this->addColumn('{{%parameter_norms}}', 'optimal_min_value', $this->decimal(10,3)->null()->after('caution_margin_absolute'));
        $this->addColumn('{{%parameter_norms}}', 'optimal_max_value', $this->decimal(10,3)->null()->after('optimal_min_value'));

        // Dodaj pola do tabeli result_values
        $this->addColumn('{{%result_values}}', 'warning_level', $this->string(20)->defaultValue('none')->after('abnormality_type'));
        $this->addColumn('{{%result_values}}', 'warning_message', $this->string(500)->null()->after('warning_level'));
        $this->addColumn('{{%result_values}}', 'distance_from_boundary', $this->decimal(10,3)->null()->after('warning_message'));
        $this->addColumn('{{%result_values}}', 'is_borderline', $this->boolean()->defaultValue(false)->after('distance_from_boundary'));
        $this->addColumn('{{%result_values}}', 'recommendation', $this->text()->null()->after('is_borderline'));

        // Dodaj indeksy dla lepszej wydajności
        $this->createIndex('idx_result_values_warning_level', '{{%result_values}}', 'warning_level');
        $this->createIndex('idx_result_values_borderline', '{{%result_values}}', 'is_borderline');
        $this->createIndex('idx_parameter_norms_warning_enabled', '{{%parameter_norms}}', 'warning_enabled');

        // Ustaw domyślne marginesy dla istniejących norm
        $this->execute("
            UPDATE {{%parameter_norms}} 
            SET warning_margin_percent = CASE 
                WHEN type = 'range' THEN 10
                WHEN type = 'single_threshold' THEN 15
                ELSE 10
            END,
            caution_margin_percent = CASE 
                WHEN type = 'range' THEN 5
                WHEN type = 'single_threshold' THEN 8
                ELSE 5
            END
            WHERE warning_enabled = 1
        ");
    }

    public function safeDown()
    {
        // Usuń indeksy
        $this->dropIndex('idx_result_values_warning_level', '{{%result_values}}');
        $this->dropIndex('idx_result_values_borderline', '{{%result_values}}');
        $this->dropIndex('idx_parameter_norms_warning_enabled', '{{%parameter_norms}}');

        // Usuń pola z tabeli result_values
        $this->dropColumn('{{%result_values}}', 'recommendation');
        $this->dropColumn('{{%result_values}}', 'is_borderline');
        $this->dropColumn('{{%result_values}}', 'distance_from_boundary');
        $this->dropColumn('{{%result_values}}', 'warning_message');
        $this->dropColumn('{{%result_values}}', 'warning_level');

        // Usuń pola z tabeli parameter_norms
        $this->dropColumn('{{%parameter_norms}}', 'optimal_max_value');
        $this->dropColumn('{{%parameter_norms}}', 'optimal_min_value');
        $this->dropColumn('{{%parameter_norms}}', 'caution_margin_absolute');
        $this->dropColumn('{{%parameter_norms}}', 'caution_margin_percent');
        $this->dropColumn('{{%parameter_norms}}', 'warning_margin_absolute');
        $this->dropColumn('{{%parameter_norms}}', 'warning_margin_percent');
        $this->dropColumn('{{%parameter_norms}}', 'warning_enabled');
    }
}
