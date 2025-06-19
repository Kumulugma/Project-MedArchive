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
        [['value'], 'validateNumericValue'], // Dodana walidacja numeryczna
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
        // Normalizuj wartość (zamień przecinek na kropkę)
        $this->normalizeValue();
        
        if ($this->norm_id && $this->norm) {
            // Zamiast echo, użyj Yii::debug() lub Yii::info() do logowania
            Yii::debug("=== DEBUG RESULT VALUE ===", __METHOD__);
            Yii::debug("Parametr: " . ($this->parameter ? $this->parameter->name : 'N/A'), __METHOD__);
            Yii::debug("Wartość oryginalna: " . $this->value, __METHOD__);
            Yii::debug("Norma ID: " . $this->norm_id, __METHOD__);
            Yii::debug("Norma: " . ($this->norm ? $this->norm->name : 'N/A'), __METHOD__);
            Yii::debug("Typ normy: " . ($this->norm ? $this->norm->type : 'N/A'), __METHOD__);
            
            if ($this->norm->type === 'single_threshold') {
                Yii::debug("Próg: " . $this->norm->threshold_value, __METHOD__);
            }
            
            // Waliduj wartość względem normy
            $this->evaluateValue();
        }
        return true;
    }
    return false;
}

/**
 * Normalizuje wartość - zamienia przecinek na kropkę dla liczb dziesiętnych
 */
private function normalizeValue()
{
    if (!empty($this->value) && is_string($this->value)) {
        // Usuń białe znaki z początku i końca
        $this->value = trim($this->value);
        
        // Zamień przecinek na kropkę dla liczb dziesiętnych (format polski -> angielski)
        $this->value = str_replace(',', '.', $this->value);
        
        // Usuń wielokrotne kropki (zachowaj tylko pierwszą)
        if (substr_count($this->value, '.') > 1) {
            $parts = explode('.', $this->value);
            $this->value = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        
        // Sprawdź czy to jest poprawna liczba
        if (is_numeric($this->value)) {
            // Zapisz znormalizowaną wartość numeryczną dla porównań
            $this->normalized_value = (float) $this->value;
            
            Yii::debug("Wartość znormalizowana: {$this->value} -> {$this->normalized_value}", __METHOD__);
        } else {
            // Jeśli nie można przekonwertować na liczbę, pozostaw oryginalną wartość
            // (może to być wartość tekstowa jak "ujemny", "dodatni" itp.)
            $this->normalized_value = null;
            Yii::debug("Wartość tekstowa (nie numeryczna): {$this->value}", __METHOD__);
        }
    }
}

/**
 * Metoda do oceny wartości (bez output debug)
 */
private function evaluateValue()
{
    if (!$this->norm) {
        return;
    }
    
    // Użyj znormalizowanej wartości jeśli jest dostępna, w przeciwnym razie oryginalnej
    $valueToCheck = $this->normalized_value !== null ? $this->normalized_value : $this->value;
    
    // Sprawdź wartość względem normy
    $result = $this->norm->checkValue($valueToCheck);
    
    // Ustaw flagi
    $this->is_abnormal = !$result['is_normal'];
    
    if (isset($result['type'])) {
        $this->abnormality_type = $result['type'];
    }
    
    // Sprawdź ostrzeżenia jeśli dostępne
    if (method_exists($this->norm, 'checkValueWithWarnings')) {
        $warningResult = $this->norm->checkValueWithWarnings($valueToCheck);
        
        if (isset($warningResult['warning_level'])) {
            $this->warning_level = $warningResult['warning_level'];
        }
        
        if (isset($warningResult['warning_message'])) {
            $this->warning_message = $warningResult['warning_message'];
        }
        
        if (isset($warningResult['recommendation'])) {
            $this->recommendation = $warningResult['recommendation'];
        }
    }
    
    Yii::debug("Ocena wartości: {$valueToCheck} -> abnormal: " . ($this->is_abnormal ? 'TAK' : 'NIE'), __METHOD__);
}

/**
 * Zwraca wartość numeryczną dla obliczeń (znormalizowaną jeśli dostępna)
 */
public function getNumericValue()
{
    return $this->normalized_value !== null ? $this->normalized_value : (is_numeric($this->value) ? (float) $this->value : null);
}

/**
 * Zwraca wartość sformatowaną do wyświetlenia (z polskim formatowaniem)
 */
public function getDisplayValue()
{
    if ($this->normalized_value !== null) {
        // Wyświetl z przecinkiem dla lepszej czytelności w polskim kontekście
        return str_replace('.', ',', $this->value);
    }
    return $this->value;
}

/**
 * Sprawdza czy wartość jest numeryczna
 */
public function isNumeric()
{
    return $this->normalized_value !== null || is_numeric($this->value);
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
    
    /**
 * Walidacja wartości numerycznej - obsługuje format z przecinkiem
 */
public function validateNumericValue($attribute, $params)
{
    if (!empty($this->$attribute)) {
        $value = trim($this->$attribute);
        
        // Zamień przecinek na kropkę dla testowania numeryczności
        $testValue = str_replace(',', '.', $value);
        
        // Sprawdź czy może być to wartość numeryczna
        if (!is_numeric($testValue) && !$this->isTextValue($value)) {
            $this->addError($attribute, 'Wartość musi być liczbą (np. 5,45 lub 5.45) lub poprawną wartością tekstową.');
        }
    }
}

/**
 * Sprawdza czy wartość jest poprawną wartością tekstową (nie numeryczną)
 */
private function isTextValue($value)
{
    // Lista akceptowanych wartości tekstowych dla badań medycznych
    $acceptedTextValues = [
        'ujemny', 'negatywny', 'negative', '-',
        'dodatny', 'pozytywny', 'positive', '+',
        'ślad', 'trace', 'tr',
        'nieoznaczalny', 'niedostępny', 'n/a', 'nd',
        'hemoliza', 'lipemia', 'ikteryczne',
        'prawidłowy', 'nieprawidłowy', 'normal', 'abnormal'
    ];
    
    return in_array(strtolower(trim($value)), $acceptedTextValues);
}

}