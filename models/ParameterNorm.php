<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ParameterNorm model
 *
 * @property int $id
 * @property int $parameter_id
 * @property string $name
 * @property string $type
 * @property float|null $min_value
 * @property float|null $max_value
 * @property float|null $threshold_value
 * @property string|null $threshold_direction
 * @property string|null $thresholds_config
 * @property bool $is_primary
 * @property float $conversion_factor
 * @property float $conversion_offset
 * @property bool $warning_enabled
 * @property float|null $warning_margin_percent
 * @property float|null $warning_margin_absolute
 * @property float|null $caution_margin_percent
 * @property float|null $caution_margin_absolute
 * @property float|null $optimal_min_value
 * @property float|null $optimal_max_value
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TestParameter $parameter
 */
class ParameterNorm extends ActiveRecord
{
    // Typy norm
    const TYPE_POSITIVE_NEGATIVE = 'positive_negative';
    const TYPE_RANGE = 'range';
    const TYPE_SINGLE_THRESHOLD = 'single_threshold';
    const TYPE_MULTIPLE_THRESHOLDS = 'multiple_thresholds';

    // Poziomy ostrzeżeń
    const WARNING_LEVEL_NONE = 'none';
    const WARNING_LEVEL_OPTIMAL = 'optimal';
    const WARNING_LEVEL_CAUTION = 'caution';
    const WARNING_LEVEL_WARNING = 'warning';
    const WARNING_LEVEL_CRITICAL = 'critical';

    public static function tableName()
    {
        return 'parameter_norms';
    }

    public function rules()
    {
        return [
            [['parameter_id', 'name', 'type'], 'required'],
            [['parameter_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [
                self::TYPE_POSITIVE_NEGATIVE,
                self::TYPE_RANGE,
                self::TYPE_SINGLE_THRESHOLD,
                self::TYPE_MULTIPLE_THRESHOLDS
            ]],
            
            // Walidacja dla typu range
            [['min_value', 'max_value'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_RANGE;
            }],
            [['min_value', 'max_value'], 'number'],
            [['max_value'], 'compare', 'compareAttribute' => 'min_value', 'operator' => '>', 'when' => function($model) {
                return $model->type === self::TYPE_RANGE;
            }],
            
            // Walidacja dla typu single_threshold
            [['threshold_value', 'threshold_direction'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_SINGLE_THRESHOLD;
            }],
            [['threshold_value'], 'number'],
            [['threshold_direction'], 'in', 'range' => ['above', 'below']],
            
            // Walidacja dla typu multiple_thresholds
            [['thresholds_config'], 'string'],
            [['thresholds_config'], 'validateThresholdsConfig'],
            
            // Pozostałe pola
            [['is_primary'], 'boolean'],
            [['conversion_factor'], 'number', 'min' => 0.001],
            [['conversion_offset'], 'number'],
            [['conversion_factor'], 'default', 'value' => 1],
            [['conversion_offset'], 'default', 'value' => 0],
            
            // Pola ostrzeżeń - WYŁĄCZONA WALIDACJA PORÓWNAŃ
            [['warning_enabled'], 'boolean'],
            [['warning_margin_percent'], 'number', 'min' => 0, 'max' => 100],
            [['warning_margin_absolute'], 'number', 'min' => 0],
            [['caution_margin_percent'], 'number', 'min' => 0, 'max' => 100],
            [['caution_margin_absolute'], 'number', 'min' => 0],
            [['optimal_min_value', 'optimal_max_value'], 'number'],
            
            // WYŁĄCZONE - walidacja marginesów
            // [['caution_margin_percent'], 'compare', 'compareAttribute' => 'warning_margin_percent', 'operator' => '<'],
            
            // WYŁĄCZONE - walidacja wartości optymalnych  
            // [['optimal_max_value'], 'compare', 'compareAttribute' => 'optimal_min_value', 'operator' => '>'],
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

    /**
     * Walidacja konfiguracji progów dla typu multiple_thresholds
     */
    public function validateThresholdsConfig($attribute, $params)
{
    if ($this->type !== self::TYPE_MULTIPLE_THRESHOLDS) {
        return;
    }

    if (empty($this->$attribute)) {
        $this->addError($attribute, 'Konfiguracja progów jest wymagana dla typu "wiele progów".');
        return;
    }

    $config = json_decode($this->$attribute, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->addError($attribute, 'Konfiguracja progów musi być prawidłowym JSON.');
        return;
    }

    if (!is_array($config) || empty($config)) {
        $this->addError($attribute, 'Konfiguracja progów musi zawierać dane konfiguracyjne.');
        return;
    }

    // Sprawdź typ konfiguracji
    $measurementType = $config['measurement_type'] ?? null;
    
    if ($measurementType === 'hydrogen_breath_test') {
        // Walidacja dla testu oddechowego
        $this->validateBreathTestConfig($config, $attribute);
    } elseif ($measurementType === 'standard_thresholds') {
        // Walidacja dla standardowych progów
        $this->validateStandardThresholdsConfig($config, $attribute);
    } else {
        // Stara walidacja dla kompatybilności wstecznej
        $this->validateLegacyThresholdsConfig($config, $attribute);
    }
}

/**
 * Waliduje konfigurację testu oddechowego
 */
private function validateBreathTestConfig($config, $attribute)
{
    // Sprawdź wymagane pola
    if (!isset($config['max_increase']) || !is_numeric($config['max_increase'])) {
        $this->addError($attribute, 'Test oddechowy: pole "max_increase" jest wymagane i musi być liczbą.');
        return;
    }
    
    if ($config['max_increase'] <= 0) {
        $this->addError($attribute, 'Test oddechowy: maksymalny wzrost musi być większy od 0.');
        return;
    }
    
    // Sprawdź interpretację
    if (!isset($config['interpretation']) || !is_array($config['interpretation'])) {
        $this->addError($attribute, 'Test oddechowy: pole "interpretation" jest wymagane.');
        return;
    }
    
    if (!isset($config['interpretation']['normal']) || !isset($config['interpretation']['abnormal'])) {
        $this->addError($attribute, 'Test oddechowy: interpretacja musi zawierać pola "normal" i "abnormal".');
        return;
    }
}

/**
 * Waliduje konfigurację standardowych progów
 */
private function validateStandardThresholdsConfig($config, $attribute)
{
    if (!isset($config['thresholds']) || !is_array($config['thresholds']) || empty($config['thresholds'])) {
        $this->addError($attribute, 'Standardowe progi: pole "thresholds" musi zawierać przynajmniej jeden próg.');
        return;
    }

    foreach ($config['thresholds'] as $index => $threshold) {
        if (!isset($threshold['value']) || !is_numeric($threshold['value'])) {
            $this->addError($attribute, "Standardowe progi: próg #{$index} - wartość jest wymagana i musi być liczbą.");
        }
        
        if (!isset($threshold['is_normal'])) {
            $this->addError($attribute, "Standardowe progi: próg #{$index} - pole 'is_normal' jest wymagane.");
        }
    }
}

/**
 * Waliduje starą konfigurację progów (kompatybilność wsteczna)
 */
private function validateLegacyThresholdsConfig($config, $attribute)
{
    // Sprawdź czy to stara struktura z bezpośrednią tablicą progów
    if (isset($config['thresholds']) && is_array($config['thresholds'])) {
        $this->validateStandardThresholdsConfig($config, $attribute);
        return;
    }
    
    // Sprawdź czy to bezpośrednia tablica progów
    if (is_array($config) && !empty($config)) {
        $firstElement = reset($config);
        if (is_array($firstElement) && isset($firstElement['value'])) {
            // To wygląda jak tablica progów
            foreach ($config as $index => $threshold) {
                if (!isset($threshold['value']) || !is_numeric($threshold['value'])) {
                    $this->addError($attribute, "Próg #{$index}: Wartość jest wymagana i musi być liczbą.");
                }
            }
            return;
        }
    }
    
    // Jeśli nic nie pasuje
    $this->addError($attribute, 'Nierozpoznany format konfiguracji progów.');
}

    /**
     * Normalizuje wartość przy użyciu współczynników konwersji
     */
    public function normalizeValue($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }
        
        return ($value * $this->conversion_factor) + $this->conversion_offset;
    }

    /**
     * Podstawowe sprawdzanie wartości względem normy
     */
    public function checkValue($value)
{
    // Dla typu positive_negative ZAWSZE sprawdzaj wartość tekstową
    if ($this->type === self::TYPE_POSITIVE_NEGATIVE) {
        return $this->checkPositiveNegativeValue($value);
    }

    // Dla pozostałych typów wartość musi być numeryczna
    if (!is_numeric($value)) {
        return [
            'is_normal' => false,
            'type' => 'invalid_value',
            'message' => 'Wartość nie jest liczbą'
        ];
    }

    // Normalizuj wartość numeryczną
    $normalizedValue = $this->normalizeValue($value);

    switch ($this->type) {
        case self::TYPE_RANGE:
            return $this->checkRangeValue($normalizedValue);
        
        case self::TYPE_SINGLE_THRESHOLD:
            return $this->checkThresholdValue($normalizedValue);
        
        case self::TYPE_MULTIPLE_THRESHOLDS:
            return $this->checkMultipleThresholdsValue($normalizedValue);
        
        default:
            return [
                'is_normal' => false,
                'type' => 'unknown_type',
                'message' => 'Nieznany typ normy'
            ];
    }
}

    /**
     * Rozszerzone sprawdzanie wartości z systemem ostrzeżeń
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
     * Sprawdza wartość typu pozytywny/negatywny
     */
    private function checkPositiveNegativeValue($value)
{
    $normalizedValue = strtolower(trim($value));
    
    // Rozszerzona lista wartości negatywnych (normalnych)
    $negativeValues = [
        'negatywny', 'negative', 'ujemny', 'nie', 'no', 'false', '0', '-', 'neg',
        'nieobecny', 'absent', 'niereaktywny', 'non-reactive', 'nonreactive'
    ];
    
    // Lista wartości pozytywnych (nieprawidłowych)
    $positiveValues = [
        'pozytywny', 'positive', 'dodatny', 'tak', 'yes', 'true', '1', '+', 'pos',
        'obecny', 'present', 'reaktywny', 'reactive'
    ];
    
    $isNormal = in_array($normalizedValue, $negativeValues);
    
    // Sprawdź czy wartość jest w ogóle rozpoznawalna
    if (!in_array($normalizedValue, array_merge($negativeValues, $positiveValues))) {
        return [
            'is_normal' => false,
            'type' => 'unrecognized_value',
            'message' => 'Nierozpoznana wartość dla normy pozytywny/negatywny'
        ];
    }
    
    return [
        'is_normal' => $isNormal,
        'type' => $isNormal ? 'negative' : 'positive',
        'message' => $isNormal ? 'Wynik negatywny - normalny' : 'Wynik pozytywny - nieprawidłowy'
    ];
}

    /**
     * Sprawdza wartość typu zakres
     */
    private function checkRangeValue($value)
    {
        $isNormal = $value >= $this->min_value && $value <= $this->max_value;
        
        $type = 'normal';
        $message = 'Wartość w normie';
        
        if (!$isNormal) {
            if ($value < $this->min_value) {
                $type = 'below_range';
                $message = 'Wartość poniżej normy';
            } else {
                $type = 'above_range';
                $message = 'Wartość powyżej normy';
            }
        }
        
        return [
            'is_normal' => $isNormal,
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Sprawdza wartość typu pojedynczy próg
     */
    private function checkThresholdValue($value)
    {
        if ($this->threshold_direction === 'below') {
            // Normalny ≤ próg (wartość poniżej lub równa progowi jest normalna)
            $isNormal = $value <= $this->threshold_value;
            $type = $isNormal ? 'below_threshold' : 'above_threshold';
            $message = $isNormal ? 'Wartość poniżej/równa progowi - normalny' : 'Wartość powyżej progu - nieprawidłowy';
        } else {
            // Normalny ≥ próg (wartość powyżej lub równa progowi jest normalna)
            $isNormal = $value >= $this->threshold_value;
            $type = $isNormal ? 'above_threshold' : 'below_threshold';
            $message = $isNormal ? 'Wartość powyżej/równa progowi - normalny' : 'Wartość poniżej progu - nieprawidłowy';
        }
        
        return [
            'is_normal' => $isNormal,
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Sprawdza wartość typu wiele progów
     */
    private function checkMultipleThresholdsValue($value)
{
    if (empty($this->thresholds_config)) {
        return [
            'is_normal' => false,
            'type' => 'no_config',
            'message' => 'Brak konfiguracji progów'
        ];
    }

    $config = json_decode($this->thresholds_config, true);
    if (!$config) {
        return [
            'is_normal' => false,
            'type' => 'invalid_config',
            'message' => 'Nieprawidłowa konfiguracja progów'
        ];
    }

    // Sprawdź czy to test oddechowy z baseline
    if (isset($config['measurement_type']) && $config['measurement_type'] === 'hydrogen_breath_test') {
        return $this->checkBreathTestValues($value, $config);
    }

    // Standardowa logika progów (zachowana kompatybilność)
    return $this->checkStandardThresholds($value, $config);
}

/**
 * Nowa metoda sprawdzania testu oddechowego
 */
private function checkBreathTestValues($value, $config)
{
    // $value format: "34.0;65.0;53.0;62.0;79.0" (baseline;30min;60min;120min;180min)
    $values = explode(';', $value);
    
    if (count($values) < 2) {
        return [
            'is_normal' => false,
            'type' => 'insufficient_data',
            'message' => 'Test oddechowy wymaga co najmniej 2 pomiarów'
        ];
    }

    $baseline = floatval($values[0]);
    $timePoints = [30, 60, 120, 180]; // minuty
    $maxIncrease = $config['max_increase'] ?? 12; // domyślnie 12 ppm
    
    $abnormalResults = [];
    $allResults = [];

    for ($i = 1; $i < count($values) && $i <= count($timePoints); $i++) {
        $currentValue = floatval($values[$i]);
        $increase = $currentValue - $baseline;
        $isNormal = $increase <= $maxIncrease;
        $timePoint = $timePoints[$i - 1];
        
        $allResults[] = [
            'time_point' => $timePoint,
            'value' => $currentValue,
            'baseline' => $baseline,
            'increase' => round($increase, 1),
            'max_allowed_increase' => $maxIncrease,
            'is_normal' => $isNormal,
            'description' => "Czas {$timePoint} min"
        ];
        
        if (!$isNormal) {
            $abnormalResults[] = [
                'time_point' => $timePoint,
                'increase' => round($increase, 1),
                'max_allowed' => $maxIncrease
            ];
        }
    }

    $isOverallNormal = empty($abnormalResults);
    
    if ($isOverallNormal) {
        $message = $config['interpretation']['normal'] ?? 'Brak nietolerancji laktozy';
    } else {
        $message = $config['interpretation']['abnormal'] ?? 'Nietolerancja laktozy potwierdzona';
        $details = [];
        foreach ($abnormalResults as $abnormal) {
            $details[] = "Czas {$abnormal['time_point']}min: +{$abnormal['increase']} ppm (max +{$abnormal['max_allowed']} ppm)";
        }
        $message .= " - " . implode(', ', $details);
    }

    return [
        'is_normal' => $isOverallNormal,
        'type' => $isOverallNormal ? 'breath_test_normal' : 'breath_test_abnormal',
        'message' => $message,
        'detailed_results' => $allResults,
        'abnormal_points' => $abnormalResults,
        'baseline_value' => $baseline
    ];
}

/**
 * Standardowa logika progów (zachowana kompatybilność)
 */
private function checkStandardThresholds($value, $config)
{
    if (!isset($config['thresholds']) || !is_array($config['thresholds'])) {
        return [
            'is_normal' => false,
            'type' => 'invalid_config',
            'message' => 'Nieprawidłowa konfiguracja progów'
        ];
    }

    $thresholds = $config['thresholds'];
    
    // Sortuj progi według wartości
    usort($thresholds, function($a, $b) {
        return ($a['value'] ?? 0) <=> ($b['value'] ?? 0);
    });

    foreach ($thresholds as $threshold) {
        if ($value <= ($threshold['value'] ?? 0)) {
            $isNormal = isset($threshold['is_normal']) ? $threshold['is_normal'] : false;
            return [
                'is_normal' => $isNormal,
                'type' => 'threshold_' . ($threshold['label'] ?? 'unlabeled'),
                'message' => $threshold['description'] ?? ($isNormal ? 'Wartość w normie' : 'Wartość poza normą')
            ];
        }
    }

    // Wartość przekracza wszystkie progi
    $lastThreshold = end($thresholds);
    return [
        'is_normal' => false,
        'type' => 'above_all_thresholds',
        'message' => 'Wartość przekracza wszystkie zdefiniowane progi'
    ];
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
        
        // Określ poziom ostrzeżenia
        $warningLevel = self::WARNING_LEVEL_NONE;
        $warningMessage = null;
        $distanceFromBoundary = null;
        
        if ($value < $min || $value > $max) {
            $warningLevel = self::WARNING_LEVEL_CRITICAL;
            $warningMessage = 'Wartość poza normą';
            $distanceFromBoundary = $value < $min ? $min - $value : $value - $max;
        } elseif ($value < $warningZoneMin || $value > $warningZoneMax) {
            $warningLevel = self::WARNING_LEVEL_WARNING;
            $warningMessage = 'Wartość bliska granicy normy - zalecana kontrola';
            $distanceFromBoundary = $value < $warningZoneMin ? $warningZoneMin - $value : $value - $warningZoneMax;
        } elseif ($value < $cautionZoneMin || $value > $cautionZoneMax) {
            $warningLevel = self::WARNING_LEVEL_CAUTION;
            $warningMessage = 'Wartość w strefie uwagi - obserwacja zalecana';
            $distanceFromBoundary = $value < $cautionZoneMin ? $cautionZoneMin - $value : $value - $cautionZoneMax;
        } elseif ($value >= $optimalMin && $value <= $optimalMax) {
            $warningLevel = self::WARNING_LEVEL_OPTIMAL;
            $warningMessage = 'Wartość optymalna';
            $distanceFromBoundary = 0;
        } else {
            $warningLevel = self::WARNING_LEVEL_NONE;
            $warningMessage = 'Wartość w normie';
            $distanceFromBoundary = 0;
        }
        
        return [
            'warning_level' => $warningLevel,
            'warning_message' => $warningMessage,
            'distance_from_boundary' => $distanceFromBoundary,
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
        
        // Oblicz margines ostrzeżenia
        $warningMargin = $this->warning_margin_absolute ?? ($threshold * ($this->warning_margin_percent ?? 10) / 100);
        $cautionMargin = $this->caution_margin_absolute ?? ($threshold * ($this->caution_margin_percent ?? 5) / 100);
        
        $warningLevel = self::WARNING_LEVEL_NONE;
        $warningMessage = null;
        $distanceFromBoundary = null;
        
        if ($direction === 'below') {
            // Normalny ≤ próg
            $distanceFromBoundary = $threshold - $value;
            
            if ($value > $threshold) {
                $warningLevel = self::WARNING_LEVEL_CRITICAL;
                $warningMessage = 'Wartość powyżej progu - nieprawidłowa';
            } elseif ($value > $threshold - $warningMargin) {
                $warningLevel = self::WARNING_LEVEL_WARNING;
                $warningMessage = 'Wartość bliska progu - zalecana kontrola';
            } elseif ($value > $threshold - $cautionMargin) {
                $warningLevel = self::WARNING_LEVEL_CAUTION;
                $warningMessage = 'Wartość w strefie uwagi';
            } else {
                $warningLevel = self::WARNING_LEVEL_OPTIMAL;
                $warningMessage = 'Wartość optymalna';
            }
        } else {
            // Normalny ≥ próg
            $distanceFromBoundary = $value - $threshold;
            
            if ($value < $threshold) {
                $warningLevel = self::WARNING_LEVEL_CRITICAL;
                $warningMessage = 'Wartość poniżej progu - nieprawidłowa';
            } elseif ($value < $threshold + $warningMargin) {
                $warningLevel = self::WARNING_LEVEL_WARNING;
                $warningMessage = 'Wartość bliska progu - zalecana kontrola';
            } elseif ($value < $threshold + $cautionMargin) {
                $warningLevel = self::WARNING_LEVEL_CAUTION;
                $warningMessage = 'Wartość w strefie uwagi';
            } else {
                $warningLevel = self::WARNING_LEVEL_OPTIMAL;
                $warningMessage = 'Wartość optymalna';
            }
        }
        
        return [
            'warning_level' => $warningLevel,
            'warning_message' => $warningMessage,
            'distance_from_boundary' => abs($distanceFromBoundary),
            'optimal_range' => null
        ];
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
        if ($this->warning_margin_percent && $this->caution_margin_percent) {
            if ($this->warning_margin_percent <= $this->caution_margin_percent) {
                $this->warning_margin_percent = $this->caution_margin_percent * 2;
            }
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
     * Hook wykonywany przed zapisem - WYŁĄCZONA automatyczna walidacja marginesów
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // WYŁĄCZONE - automatyczne poprawianie marginesów
            // if ($this->warning_enabled) {
            //     $this->validateAndFixMargins();
            // }
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

    /**
     * Zwraca listę dostępnych typów norm
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_POSITIVE_NEGATIVE => 'Pozytywny/Negatywny',
            self::TYPE_RANGE => 'Zakres (min-max)',
            self::TYPE_SINGLE_THRESHOLD => 'Pojedynczy próg',
            self::TYPE_MULTIPLE_THRESHOLDS => 'Wiele progów'
        ];
    }

    /**
     * Zwraca nazwę typu normy
     */
    public function getTypeName()
    {
        $types = self::getTypeOptions();
        return $types[$this->type] ?? $this->type;
    }

    /**
     * Zwraca tekstową reprezentację zakresu normy
     */
    public function getRangeText()
    {
        switch ($this->type) {
            case self::TYPE_RANGE:
                return $this->min_value . ' - ' . $this->max_value;

            case self::TYPE_SINGLE_THRESHOLD:
                return ($this->threshold_direction === 'above' ? '≥ ' : '≤ ') . $this->threshold_value;

            case self::TYPE_POSITIVE_NEGATIVE:
                return 'Negatywny = Normalny';

            case self::TYPE_MULTIPLE_THRESHOLDS:
                if ($this->thresholds_config) {
                    $thresholds = json_decode($this->thresholds_config, true);
                    return 'Wiele progów (' . count($thresholds) . ')';
                }
                return 'Wiele progów (nie skonfigurowane)';

            default:
                return 'Nieznany typ';
        }
    }

    /**
     * Sprawdza czy można włączyć ostrzeżenia dla tej normy
     */
    public function canEnableWarnings()
    {
        return in_array($this->type, [self::TYPE_RANGE, self::TYPE_SINGLE_THRESHOLD]);
    }

    /**
     * Pobiera rekomendacje na podstawie poziomu ostrzeżenia
     */
    public function getRecommendationByWarningLevel($warningLevel)
    {
        switch ($warningLevel) {
            case self::WARNING_LEVEL_OPTIMAL:
                return 'Wartość optymalna - kontynuuj obecny tryb życia.';
            
            case self::WARNING_LEVEL_CAUTION:
                return 'Zalecana kontrola za 3-6 miesięcy i obserwacja trendów.';
            
            case self::WARNING_LEVEL_WARNING:
                return 'Rozważ kontrolę za 1-3 miesiące - wartość bliska granicy normy.';
            
            case self::WARNING_LEVEL_CRITICAL:
                return 'Skonsultuj wynik z lekarzem - wartość poza normą.';
            
            default:
                return 'Wartość w normie - regularnie kontroluj zgodnie z zaleceniami lekarza.';
        }
    }

    /**
     * Generuje szczegółowy raport analizy wartości
     */
    public function generateValueReport($value)
    {
        $result = $this->checkValueWithWarnings($value);
        
        return [
            'value' => $value,
            'normalized_value' => $this->normalizeValue($value),
            'norm_name' => $this->name,
            'norm_type' => $this->getTypeName(),
            'norm_range' => $this->getRangeText(),
            'is_normal' => $result['is_normal'],
            'status_type' => $result['type'],
            'status_message' => $result['message'],
            'warning_level' => $result['warning_level'] ?? self::WARNING_LEVEL_NONE,
            'warning_message' => $result['warning_message'],
            'distance_from_boundary' => $result['distance_from_boundary'],
            'recommendation' => $this->getRecommendationByWarningLevel($result['warning_level'] ?? self::WARNING_LEVEL_NONE),
            'margins_description' => $this->getMarginsDescription(),
            'has_warnings_enabled' => $this->warning_enabled,
        ];
    }
}