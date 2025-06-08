<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class ParameterNorm extends ActiveRecord
{
    const TYPE_POSITIVE_NEGATIVE = 'positive_negative';
    const TYPE_RANGE = 'range';
    const TYPE_SINGLE_THRESHOLD = 'single_threshold';
    const TYPE_MULTIPLE_THRESHOLDS = 'multiple_thresholds';

    // Nowe stałe dla systemu ostrzeżeń
    const WARNING_LEVEL_NONE = 'none';
    const WARNING_LEVEL_CAUTION = 'caution';
    const WARNING_LEVEL_WARNING = 'warning';
    const WARNING_LEVEL_CRITICAL = 'critical';

    public static function tableName()
    {
        return '{{%parameter_norms}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['parameter_id', 'name', 'type'], 'required'],
            [['parameter_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['type'], 'in', 'range' => [
                self::TYPE_POSITIVE_NEGATIVE,
                self::TYPE_RANGE,
                self::TYPE_SINGLE_THRESHOLD,
                self::TYPE_MULTIPLE_THRESHOLDS,
            ]],
            [['min_value', 'max_value', 'threshold_value', 'conversion_factor', 'conversion_offset'], 'number'],
            [['threshold_direction'], 'string', 'max' => 10],
            [['threshold_direction'], 'in', 'range' => ['above', 'below']],
            [['thresholds_config'], 'string'],
            [['is_primary'], 'boolean'],
            [['conversion_factor'], 'default', 'value' => 1],
            [['conversion_offset'], 'default', 'value' => 0],
            
            // Nowe pola dla systemu ostrzeżeń
            [['warning_enabled'], 'boolean'],
            [['warning_margin_percent'], 'number', 'min' => 0, 'max' => 50],
            [['warning_margin_absolute'], 'number', 'min' => 0],
            [['caution_margin_percent'], 'number', 'min' => 0, 'max' => 30],
            [['caution_margin_absolute'], 'number', 'min' => 0],
            [['optimal_min_value', 'optimal_max_value'], 'number'],
            
            // Walidacja że marginy ostrzeżeń są mniejsze niż główne wartości
            [['warning_margin_percent'], 'compare', 'compareValue' => 50, 'operator' => '<'],
            [['caution_margin_percent'], 'compare', 'compareAttribute' => 'warning_margin_percent', 'operator' => '<'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parameter_id' => 'Parametr',
            'name' => 'Nazwa normy',
            'type' => 'Typ',
            'min_value' => 'Wartość minimalna',
            'max_value' => 'Wartość maksymalna',
            'threshold_value' => 'Próg',
            'threshold_direction' => 'Kierunek progu',
            'thresholds_config' => 'Konfiguracja progów',
            'is_primary' => 'Norma podstawowa',
            'conversion_factor' => 'Współczynnik konwersji',
            'conversion_offset' => 'Offset konwersji',
            
            // Nowe etykiety
            'warning_enabled' => 'Włącz ostrzeżenia o wartościach granicznych',
            'warning_margin_percent' => 'Margines ostrzeżenia (%)',
            'warning_margin_absolute' => 'Margines ostrzeżenia (wartość bezwzględna)',
            'caution_margin_percent' => 'Margines uwagi (%)',
            'caution_margin_absolute' => 'Margines uwagi (wartość bezwzględna)',
            'optimal_min_value' => 'Optymalna wartość minimalna',
            'optimal_max_value' => 'Optymalna wartość maksymalna',
        ];
    }

    public function getParameter()
    {
        return $this->hasOne(TestParameter::class, ['id' => 'parameter_id']);
    }

    public function normalizeValue($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }
        
        return ($value * $this->conversion_factor) + $this->conversion_offset;
    }

    /**
     * Rozszerzone sprawdzanie wartości z systemem ostrzeżeń
     * Zwraca szczegółowe informacje o statusie wartości
     */
    public function checkValueWithWarnings($value)
    {
        $basicCheck = $this->checkValue($value);
        
        if (!is_numeric($value) || !$this->warning_enabled) {
            return array_merge($basicCheck, [
                'warning_level' => self::WARNING_LEVEL_NONE,
                'warning_message' => null,
                'distance_from_boundary' => null,
                'optimal_range' => null
            ]);
        }

        $normalizedValue = $this->normalizeValue($value);
        $warningData = $this->calculateWarningLevel($normalizedValue);
        
        return array_merge($basicCheck, $warningData);
    }

    /**
     * Oblicza poziom ostrzeżenia dla podanej wartości
     */
    private function calculateWarningLevel($normalizedValue)
    {
        switch ($this->type) {
            case self::TYPE_RANGE:
                return $this->calculateRangeWarningLevel($normalizedValue);
            case self::TYPE_SINGLE_THRESHOLD:
                return $this->calculateThresholdWarningLevel($normalizedValue);
            default:
                return [
                    'warning_level' => self::WARNING_LEVEL_NONE,
                    'warning_message' => null,
                    'distance_from_boundary' => null,
                    'optimal_range' => null
                ];
        }
    }

    /**
     * Oblicza ostrzeżenia dla typu "range"
     */
    private function calculateRangeWarningLevel($value)
    {
        $min = $this->min_value;
        $max = $this->max_value;
        $range = $max - $min;
        
        // Oblicz marginesy ostrzeżeń
        $warningMargin = $this->getEffectiveWarningMargin($range);
        $cautionMargin = $this->getEffectiveCautionMargin($range);
        
        // Oblicz strefy
        $warningZoneMin = $min + $warningMargin;
        $warningZoneMax = $max - $warningMargin;
        $cautionZoneMin = $min + $cautionMargin;
        $cautionZoneMax = $max - $cautionMargin;
        
        // Optymalne wartości (jeśli zdefiniowane)
        $optimalMin = $this->optimal_min_value ?? $cautionZoneMin;
        $optimalMax = $this->optimal_max_value ?? $cautionZoneMax;
        
        // Sprawdź poziom ostrzeżenia
        if ($value < $min || $value > $max) {
            return [
                'warning_level' => self::WARNING_LEVEL_CRITICAL,
                'warning_message' => 'Wartość poza normą',
                'distance_from_boundary' => min(abs($value - $min), abs($value - $max)),
                'optimal_range' => [$optimalMin, $optimalMax]
            ];
        }
        
        if ($value <= $warningZoneMin || $value >= $warningZoneMax) {
            $distance = min(abs($value - $min), abs($value - $max));
            return [
                'warning_level' => self::WARNING_LEVEL_WARNING,
                'warning_message' => 'Wartość bliska granicy normy',
                'distance_from_boundary' => $distance,
                'optimal_range' => [$optimalMin, $optimalMax]
            ];
        }
        
        if ($value <= $cautionZoneMin || $value >= $cautionZoneMax) {
            $distance = min(abs($value - $min), abs($value - $max));
            return [
                'warning_level' => self::WARNING_LEVEL_CAUTION,
                'warning_message' => 'Wartość w strefie uwagi - rozważ kontrolę',
                'distance_from_boundary' => $distance,
                'optimal_range' => [$optimalMin, $optimalMax]
            ];
        }
        
        if ($value < $optimalMin || $value > $optimalMax) {
            return [
                'warning_level' => self::WARNING_LEVEL_CAUTION,
                'warning_message' => 'Wartość poza optymalnym zakresem',
                'distance_from_boundary' => min(abs($value - $optimalMin), abs($value - $optimalMax)),
                'optimal_range' => [$optimalMin, $optimalMax]
            ];
        }
        
        return [
            'warning_level' => self::WARNING_LEVEL_NONE,
            'warning_message' => 'Wartość optymalna',
            'distance_from_boundary' => min(abs($value - $min), abs($value - $max)),
            'optimal_range' => [$optimalMin, $optimalMax]
        ];
    }

    /**
     * Oblicza ostrzeżenia dla typu "single_threshold"
     */
    private function calculateThresholdWarningLevel($value)
    {
        $threshold = $this->threshold_value;
        $direction = $this->threshold_direction;
        
        // Estimate range for margin calculation (10% of threshold value)
        $estimatedRange = abs($threshold * 0.2);
        $warningMargin = $this->getEffectiveWarningMargin($estimatedRange);
        $cautionMargin = $this->getEffectiveCautionMargin($estimatedRange);
        
        $distance = abs($value - $threshold);
        
        if ($direction === 'above') {
            // Normal: value <= threshold
            if ($value > $threshold) {
                return [
                    'warning_level' => self::WARNING_LEVEL_CRITICAL,
                    'warning_message' => 'Wartość powyżej dopuszczalnego progu',
                    'distance_from_boundary' => $distance,
                    'optimal_range' => null
                ];
            }
            
            if ($value > ($threshold - $warningMargin)) {
                return [
                    'warning_level' => self::WARNING_LEVEL_WARNING,
                    'warning_message' => 'Wartość bliska progu',
                    'distance_from_boundary' => $distance,
                    'optimal_range' => null
                ];
            }
            
            if ($value > ($threshold - $cautionMargin)) {
                return [
                    'warning_level' => self::WARNING_LEVEL_CAUTION,
                    'warning_message' => 'Wartość w strefie uwagi',
                    'distance_from_boundary' => $distance,
                    'optimal_range' => null
                ];
            }
        } else {
            // Normal: value >= threshold
            if ($value < $threshold) {
                return [
                    'warning_level' => self::WARNING_LEVEL_CRITICAL,
                    'warning_message' => 'Wartość poniżej dopuszczalnego progu',
                    'distance_from_boundary' => $distance,
                    'optimal_range' => null
                ];
            }
            
            if ($value < ($threshold + $warningMargin)) {
                return [
                    'warning_level' => self::WARNING_LEVEL_WARNING,
                    'warning_message' => 'Wartość bliska progu',
                    'distance_from_boundary' => $distance,
                    'optimal_range' => null
                ];
            }
            
            if ($value < ($threshold + $cautionMargin)) {
                return [
                    'warning_level' => self::WARNING_LEVEL_CAUTION,
                    'warning_message' => 'Wartość w strefie uwagi',
                    'distance_from_boundary' => $distance,
                    'optimal_range' => null
                ];
            }
        }
        
        return [
            'warning_level' => self::WARNING_LEVEL_NONE,
            'warning_message' => 'Wartość optymalna',
            'distance_from_boundary' => $distance,
            'optimal_range' => null
        ];
    }

    /**
     * Sprawdza wartość (stara metoda dla kompatybilności)
     */
    public function checkValue($value)
    {
        if (!is_numeric($value)) {
            if ($this->type === self::TYPE_POSITIVE_NEGATIVE) {
                return [
                    'is_normal' => in_array(strtolower($value), ['negatywny', 'negative', 'neg', '-']),
                    'type' => null
                ];
            }
            return ['is_normal' => true, 'type' => null];
        }

        $normalizedValue = $this->normalizeValue($value);

        switch ($this->type) {
            case self::TYPE_RANGE:
                $isNormal = $normalizedValue >= $this->min_value && $normalizedValue <= $this->max_value;
                $type = null;
                if (!$isNormal) {
                    $type = $normalizedValue < $this->min_value ? 'low' : 'high';
                }
                return ['is_normal' => $isNormal, 'type' => $type];

            case self::TYPE_SINGLE_THRESHOLD:
                if ($this->threshold_direction === 'above') {
                    $isNormal = $normalizedValue <= $this->threshold_value;
                    $type = $isNormal ? null : 'high';
                } else {
                    $isNormal = $normalizedValue >= $this->threshold_value;
                    $type = $isNormal ? null : 'low';
                }
                return ['is_normal' => $isNormal, 'type' => $type];

            case self::TYPE_MULTIPLE_THRESHOLDS:
                if ($this->thresholds_config) {
                    $thresholds = json_decode($this->thresholds_config, true);
                    foreach ($thresholds as $threshold) {
                        if ($normalizedValue <= $threshold['value']) {
                            return [
                                'is_normal' => $threshold['is_normal'] ?? true,
                                'type' => $threshold['type'] ?? null,
                                'label' => $threshold['label'] ?? null
                            ];
                        }
                    }
                }
                return ['is_normal' => true, 'type' => null];
        }

        return ['is_normal' => true, 'type' => null];
    }

    /**
     * Zwraca rekomendowane domyślne marginesy dla różnych typów badań
     */
    public static function getDefaultMargins($parameterType = null)
    {
        $defaults = [
            'basic_chemistry' => ['warning' => 15, 'caution' => 8],
            'lipid_profile' => ['warning' => 12, 'caution' => 6],
            'liver_function' => ['warning' => 20, 'caution' => 10],
            'kidney_function' => ['warning' => 18, 'caution' => 9],
            'thyroid' => ['warning' => 10, 'caution' => 5],
            'diabetes' => ['warning' => 8, 'caution' => 4],
            'electrolytes' => ['warning' => 5, 'caution' => 3],
            'default' => ['warning' => 10, 'caution' => 5]
        ];
        
        return $defaults[$parameterType] ?? $defaults['default'];
    }
    public function autoSetMargins($patientAge = null, $conditions = [])
    {
        if (!$this->parameter) {
            return false;
        }
        
        $thresholdManager = new MedicalThresholdManager();
        $margins = $thresholdManager->getParameterMargins(
            $this->parameter->name, 
            $patientAge, 
            $conditions
        );
        
        $this->warning_enabled = true;
        $this->warning_margin_percent = $margins['warning_percent'];
        $this->caution_margin_percent = $margins['caution_percent'];
        
        return true;
    }
    
    /**
     * Pobiera predefiniowane ustawienia marginesów
     */
    public static function getPresetMargins()
    {
        $thresholdManager = new MedicalThresholdManager();
        return $thresholdManager->getPresetOptions();
    }
    
    /**
     * Sprawdza czy parametr należy do kategorii krytycznych
     */
    public function isCriticalParameter()
    {
        if (!$this->parameter) {
            return false;
        }
        
        $thresholdManager = new MedicalThresholdManager();
        return $thresholdManager->getParameterCategory($this->parameter->name) === 'critical';
    }
    
    /**
     * Rozszerzone sprawdzanie wartości z użyciem MedicalThresholdManager
     */
    public function checkValueWithSmartRecommendations($value, $patientAge = null)
    {
        $basicCheck = $this->checkValueWithWarnings($value);
        
        if ($basicCheck['warning_level'] !== 'none') {
            $thresholdManager = new MedicalThresholdManager();
            $smartRecommendation = $thresholdManager->generateRecommendation(
                $basicCheck['warning_level'], 
                $this->parameter->name
            );
            
            $basicCheck['smart_recommendation'] = $smartRecommendation;
        }
        
        return $basicCheck;
    }
    
    /**
     * Oblicza efektywny margines ostrzeżenia
     */
    public function getEffectiveWarningMargin($range)
    {
        if ($this->warning_margin_absolute > 0) {
            return $this->warning_margin_absolute;
        }
        
        $percent = $this->warning_margin_percent ?? 10;
        return $range * ($percent / 100);
    }
    
    /**
     * Oblicza efektywny margines uwagi
     */
    public function getEffectiveCautionMargin($range)
    {
        if ($this->caution_margin_absolute > 0) {
            return $this->caution_margin_absolute;
        }
        
        $percent = $this->caution_margin_percent ?? 5;
        return $range * ($percent / 100);
    }
    
    /**
     * Waliduje i poprawia marginesy jeśli są nieprawidłowe
     */
    public function validateAndFixMargins()
    {
        // Upewnij się że margines ostrzeżenia > margines uwagi
        if ($this->warning_margin_percent <= $this->caution_margin_percent) {
            $this->warning_margin_percent = $this->caution_margin_percent * 2;
        }
        
        // Ogranicz maksymalne marginesy
        if ($this->warning_margin_percent > 50) {
            $this->warning_margin_percent = 50;
        }
        
        if ($this->caution_margin_percent > 30) {
            $this->caution_margin_percent = 30;
        }
        
        return true;
    }
    
    /**
     * Hook wykonywany przed zapisem - automatyczna walidacja marginesów
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->warning_enabled) {
                $this->validateAndFixMargins();
            }
            return true;
        }
        return false;
    }
    
    /**
     * Sprawdza czy norma ma skonfigurowane ostrzeżenia
     */
    public function hasWarningsConfigured()
    {
        return $this->warning_enabled && 
               ($this->warning_margin_percent > 0 || $this->warning_margin_absolute > 0);
    }
    
    /**
     * Pobiera opis marginesów dla UI
     */
    public function getMarginsDescription()
    {
        if (!$this->hasWarningsConfigured()) {
            return 'Ostrzeżenia wyłączone';
        }
        
        $desc = [];
        
        if ($this->warning_margin_percent > 0) {
            $desc[] = "Ostrzeżenie: {$this->warning_margin_percent}%";
        }
        if ($this->warning_margin_absolute > 0) {
            $desc[] = "Ostrzeżenie: ±{$this->warning_margin_absolute}";
        }
        
        if ($this->caution_margin_percent > 0) {
            $desc[] = "Uwaga: {$this->caution_margin_percent}%";
        }
        if ($this->caution_margin_absolute > 0) {
            $desc[] = "Uwaga: ±{$this->caution_margin_absolute}";
        }
        
        return implode(', ', $desc);
    }
}