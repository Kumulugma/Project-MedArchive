<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class ResultValue extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%result_values}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ]
        ];
    }

    public function rules()
    {
        return [
            [['test_result_id', 'parameter_id', 'value'], 'required'],
            [['test_result_id', 'parameter_id', 'norm_id'], 'integer'],
            [['value'], 'string', 'max' => 255],
            [['normalized_value'], 'number'],
            [['is_abnormal'], 'boolean'],
            [['abnormality_type'], 'string', 'max' => 20],
            
            // Nowe pola dla systemu ostrzeżeń
            [['warning_level'], 'string', 'max' => 20],
            [['warning_message'], 'string', 'max' => 500],
            [['distance_from_boundary'], 'number'],
            [['is_borderline'], 'boolean'],
            [['recommendation'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'test_result_id' => 'Wynik badania',
            'parameter_id' => 'Parametr',
            'norm_id' => 'Norma',
            'value' => 'Wartość',
            'normalized_value' => 'Wartość znormalizowana',
            'is_abnormal' => 'Nieprawidłowa',
            'abnormality_type' => 'Typ nieprawidłowości',
            
            // Nowe etykiety
            'warning_level' => 'Poziom ostrzeżenia',
            'warning_message' => 'Wiadomość ostrzeżenia',
            'distance_from_boundary' => 'Odległość od granicy',
            'is_borderline' => 'Wartość graniczna',
            'recommendation' => 'Rekomendacja',
        ];
    }

    public function getTestResult()
    {
        return $this->hasOne(TestResult::class, ['id' => 'test_result_id']);
    }

    public function getParameter()
    {
        return $this->hasOne(TestParameter::class, ['id' => 'parameter_id']);
    }

    public function getNorm()
    {
        return $this->hasOne(ParameterNorm::class, ['id' => 'norm_id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->norm_id && $this->norm) {
                echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; margin: 10px 0;'>";
                echo "=== DEBUG RESULT VALUE ===\n";
                echo "Parametr: " . $this->parameter->name . "\n";
                echo "Wartość: " . $this->value . "\n";
                echo "Norma ID: " . $this->norm_id . "\n";
                echo "Norma: " . $this->norm->name . "\n";
                echo "Typ normy: " . $this->norm->type . "\n";
                
                if ($this->norm->type === 'single_threshold') {
                    echo "Próg: " . $this->norm->threshold_value . "\n";
                    echo "Kierunek: " . $this->norm->threshold_direction . "\n";
                }
                
                $this->normalized_value = $this->norm->normalizeValue($this->value);
                echo "Wartość znormalizowana: " . $this->normalized_value . "\n";
                
                // Test podstawowej metody checkValue
                $basicCheck = $this->norm->checkValue($this->value);
                echo "Podstawowe sprawdzenie: " . json_encode($basicCheck, JSON_PRETTY_PRINT) . "\n";
                
                // Test rozszerzonej metody
                $check = $this->norm->checkValueWithWarnings($this->value);
                echo "Sprawdzenie z ostrzeżeniami: " . json_encode($check, JSON_PRETTY_PRINT) . "\n";
                
                // Ustaw wartości
                $this->is_abnormal = !$check['is_normal'];
                echo "is_normal z check: " . ($check['is_normal'] ? 'TRUE' : 'FALSE') . "\n";
                echo "is_abnormal ustawione na: " . ($this->is_abnormal ? 'TRUE' : 'FALSE') . "\n";
                
                $this->abnormality_type = $check['type'] ?? null;
                $this->warning_level = $check['warning_level'] ?? null;
                $this->warning_message = $check['warning_message'] ?? null;
                $this->distance_from_boundary = $check['distance_from_boundary'] ?? null;
                $this->is_borderline = in_array($check['warning_level'] ?? '', [
                    \app\models\ParameterNorm::WARNING_LEVEL_WARNING, 
                    \app\models\ParameterNorm::WARNING_LEVEL_CAUTION
                ]);
                
                echo "========================\n";
                echo "</pre>";
                
                // Generuj rekomendację
                $this->recommendation = $this->generateRecommendation($check);
            }
            return true;
        }
        return false;
    }

    /**
     * Generuje rekomendację na podstawie wyniku sprawdzenia
     */
    private function generateRecommendation($checkResult)
    {
        if (!$checkResult['is_normal']) {
            return 'Skonsultuj wynik z lekarzem - wartość poza normą.';
        }
        
        switch ($checkResult['warning_level'] ?? '') {
            case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                return 'Rozważ kontrolę za 1-3 miesiące - wartość bliska granicy normy.';
                
            case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                return 'Zalecana kontrola za 3-6 miesięcy i obserwacja trendów.';
                
            case \app\models\ParameterNorm::WARNING_LEVEL_OPTIMAL:
                return 'Wartość optymalna - kontynuuj obecny tryb życia.';
                
            default:
                return 'Wartość w normie - regularne kontrole według zaleceń lekarza.';
        }
    }

    /**
     * Sprawdza czy wartość jest normalna (helper method)
     */
    public function isNormal()
    {
        return !$this->is_abnormal;
    }

    /**
     * Zwraca status jako tekst
     */
    public function getStatusText()
    {
        if ($this->is_abnormal) {
            return 'Nieprawidłowe';
        }
        
        switch ($this->warning_level) {
            case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                return 'Ostrzeżenie';
            case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                return 'Uwaga';
            case \app\models\ParameterNorm::WARNING_LEVEL_OPTIMAL:
                return 'Optymalne';
            default:
                return 'Prawidłowe';
        }
    }

    /**
     * Zwraca kolor bootstrap dla statusu
     */
    public function getStatusColor()
    {
        if ($this->is_abnormal) {
            return 'danger';
        }
        
        switch ($this->warning_level) {
            case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                return 'warning';
            case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                return 'info';
            case \app\models\ParameterNorm::WARNING_LEVEL_OPTIMAL:
                return 'success';
            default:
                return 'success';
        }
    }

    /**
     * Sprawdza czy wartość wymaga uwagi (ostrzeżenie lub nieprawidłowa)
     */
    public function requiresAttention()
    {
        return $this->is_abnormal || in_array($this->warning_level, [
            \app\models\ParameterNorm::WARNING_LEVEL_WARNING,
            \app\models\ParameterNorm::WARNING_LEVEL_CAUTION
        ]);
    }

    /**
     * Po zapisaniu - aktualizuj flagę w TestResult
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Aktualizuj flagę has_abnormal_values w TestResult
        if ($this->testResult) {
            $this->testResult->updateAbnormalFlag();
        }
    }

    /**
     * Po usunięciu - aktualizuj flagę w TestResult
     */
    public function afterDelete()
    {
        parent::afterDelete();
        
        // Aktualizuj flagę has_abnormal_values w TestResult
        if ($this->testResult) {
            $this->testResult->updateAbnormalFlag();
        }
    }
}