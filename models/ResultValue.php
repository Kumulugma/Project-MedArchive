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
                $this->normalized_value = $this->norm->normalizeValue($this->value);
                
                // Użyj nowego systemu ostrzeżeń
                $check = $this->norm->checkValueWithWarnings($this->value);
                
                // Stare pola dla kompatybilności
                $this->is_abnormal = !$check['is_normal'];
                $this->abnormality_type = $check['type'];
                
                // Nowe pola ostrzeżeń
                $this->warning_level = $check['warning_level'];
                $this->warning_message = $check['warning_message'];
                $this->distance_from_boundary = $check['distance_from_boundary'];
                $this->is_borderline = in_array($check['warning_level'], [
                    ParameterNorm::WARNING_LEVEL_WARNING, 
                    ParameterNorm::WARNING_LEVEL_CAUTION
                ]);
                
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
        
        switch ($checkResult['warning_level']) {
            case ParameterNorm::WARNING_LEVEL_WARNING:
                return 'Rozważ kontrolę za 1-3 miesiące - wartość bliska granicy normy.';
                
            case ParameterNorm::WARNING_LEVEL_CAUTION:
                return 'Zalecana kontrola za 3-6 miesięcy i obserwacja trendów.';
                
            case ParameterNorm::WARNING_LEVEL_NONE:
                return 'Wartość optymalna - kontynuuj obecny tryb życia.';
                
            default:
                return null;
        }
    }

    /**
     * Zwraca klasę CSS dla wyświetlania w interfejsie
     */
    public function getDisplayClass()
    {
        if (!$this->is_abnormal) {
            switch ($this->warning_level) {
                case ParameterNorm::WARNING_LEVEL_WARNING:
                    return 'text-warning';
                case ParameterNorm::WARNING_LEVEL_CAUTION:
                    return 'text-info';
                default:
                    return 'text-success';
            }
        }
        return 'text-danger';
    }

    /**
     * Zwraca ikonę dla poziomu ostrzeżenia
     */
    public function getWarningIcon()
    {
        if (!$this->is_abnormal) {
            switch ($this->warning_level) {
                case ParameterNorm::WARNING_LEVEL_WARNING:
                    return 'fas fa-exclamation-triangle';
                case ParameterNorm::WARNING_LEVEL_CAUTION:
                    return 'fas fa-eye';
                default:
                    return 'fas fa-check-circle';
            }
        }
        return 'fas fa-times-circle';
    }

    /**
     * Zwraca badge HTML dla poziomu ostrzeżenia
     */
    public function getWarningBadge()
    {
        if (!$this->is_abnormal) {
            switch ($this->warning_level) {
                case ParameterNorm::WARNING_LEVEL_WARNING:
                    return '<span class="badge bg-warning text-dark">Uwaga</span>';
                case ParameterNorm::WARNING_LEVEL_CAUTION:
                    return '<span class="badge bg-info">Obserwacja</span>';
                default:
                    return '<span class="badge bg-success">Optymalne</span>';
            }
        }
        return '<span class="badge bg-danger">Nieprawidłowe</span>';
    }

    /**
     * Sprawdza czy wartość wymaga uwagi lekarskiej
     */
    public function requiresMedicalAttention()
    {
        return $this->is_abnormal || 
               $this->warning_level === ParameterNorm::WARNING_LEVEL_WARNING;
    }

    /**
     * Sprawdza czy wartość wymaga monitorowania
     */
    public function requiresMonitoring()
    {
        return $this->warning_level === ParameterNorm::WARNING_LEVEL_CAUTION || 
               $this->is_borderline;
    }
}