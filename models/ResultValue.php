<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class ResultValue extends ActiveRecord {

    public static function tableName() {
        return '{{%result_values}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ]
        ];
    }

    public function rules() {
        return [
            [['test_result_id', 'parameter_id', 'value'], 'required'],
            [['test_result_id', 'parameter_id', 'norm_id'], 'integer'],
            [['value'], 'string', 'max' => 255],
            // TYMCZASOWO WYŁĄCZONE - problemy z walidacją
            // [['value'], 'validateNumericValue'], 
            [['normalized_value'], 'number'],
            [['is_abnormal'], 'boolean'],
            [['abnormality_type'], 'string', 'max' => 20],
            // Nowe pola dla systemu ostrzeżeń
            [['warning_level'], 'string', 'max' => 20],
            [['warning_message'], 'string', 'max' => 500],
            [['distance_from_boundary'], 'number'],
            [['is_borderline'], 'boolean'],
            [['recommendation'], 'string'],
//            [['value'], 'validateBreathTestValue'], // Dodaj tę linię
        ];
    }

    public function attributeLabels() {
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

    public function getTestResult() {
        return $this->hasOne(TestResult::class, ['id' => 'test_result_id']);
    }

    public function getParameter() {
        return $this->hasOne(TestParameter::class, ['id' => 'parameter_id']);
    }

    public function getNorm() {
        return $this->hasOne(ParameterNorm::class, ['id' => 'norm_id']);
    }

    public function beforeSave($insert) {
        if (YII_DEBUG) {
            Yii::debug("=== DEBUG RESULT VALUE BEFORE SAVE ===", __METHOD__);
            Yii::debug("Insert: " . ($insert ? 'TRUE' : 'FALSE'), __METHOD__);
            Yii::debug("Wartość: '{$this->value}'", __METHOD__);
            Yii::debug("Parameter ID: {$this->parameter_id}", __METHOD__);
            Yii::debug("Norm ID: " . ($this->norm_id ?: 'NULL'), __METHOD__);
        }

        if (parent::beforeSave($insert)) {
            // Normalizuj wartość (zamień przecinek na kropkę) TYLKO dla parametrów numerycznych
            if ($this->parameter && $this->parameter->isNumeric()) {
                $this->normalizeValue();
                if (YII_DEBUG) {
                    Yii::debug("Wartość po normalizacji: '{$this->value}'", __METHOD__);
                }
            }

            if ($this->norm_id && $this->norm) {
                if (YII_DEBUG) {
                    Yii::debug("Typ parametru: " . ($this->parameter ? $this->parameter->type : 'N/A'), __METHOD__);
                    Yii::debug("Typ normy: " . ($this->norm ? $this->norm->type : 'N/A'), __METHOD__);
                }

                // Sprawdź wartość względem normy
                $result = $this->norm->checkValueWithWarnings($this->value);

                if (YII_DEBUG) {
                    Yii::debug("Wynik walidacji normy: " . json_encode($result), __METHOD__);
                }

                $this->is_abnormal = !$result['is_normal'];
                $this->abnormality_type = $result['type'] ?? null;
                $this->warning_level = $result['warning_level'] ?? null;
                $this->warning_message = $result['warning_message'] ?? null;
                $this->distance_from_boundary = $result['distance_from_boundary'] ?? null;
                $this->is_borderline = isset($result['warning_level']) && in_array($result['warning_level'], [
                            \app\models\ParameterNorm::WARNING_LEVEL_WARNING,
                            \app\models\ParameterNorm::WARNING_LEVEL_CAUTION
                ]);

                // POPRAWKA: Przekaż $result do generateRecommendation()
                $this->recommendation = $this->generateRecommendation($result);

                if (YII_DEBUG) {
                    Yii::debug("is_abnormal: " . ($this->is_abnormal ? 'TRUE' : 'FALSE'), __METHOD__);
                    Yii::debug("recommendation: " . ($this->recommendation ?: 'NULL'), __METHOD__);
                }
            }

            if (YII_DEBUG) {
                Yii::debug("beforeSave() zakończone pomyślnie", __METHOD__);
            }

            return true;
        }

        if (YII_DEBUG) {
            Yii::debug("parent::beforeSave() zwróciło FALSE", __METHOD__);
        }

        return false;
    }

    /**
     * Normalizuje wartość - zamienia przecinek na kropkę dla liczb dziesiętnych
     */
    private function normalizeValue() {
        if (!empty($this->value)) {
            $value = trim($this->value);

            // Sprawdź czy wartość wygląda na numeryczną
            $testValue = str_replace(',', '.', $value);
            if (is_numeric($testValue)) {
                $this->value = $testValue;
                $this->normalized_value = (float) $testValue;
            } else {
                // Dla wartości tekstowych nie zmieniaj nic
                $this->normalized_value = null;
            }
        }
    }

    /**
     * Walidacja wartości dla testów oddechowych (multiple_thresholds)
     */
    public function validateBreathTestValue($attribute, $params) {
        if ($this->norm && $this->norm->type === \app\models\ParameterNorm::TYPE_MULTIPLE_THRESHOLDS) {
            $config = json_decode($this->norm->thresholds_config, true);

            if (isset($config['measurement_type']) && $config['measurement_type'] === 'hydrogen_breath_test') {
                $value = trim($this->$attribute);

                // Sprawdź format wartości (powinny być oddzielone średnikami)
                if (strpos($value, ';') === false) {
                    $this->addError($attribute, 'Test oddechowy wymaga wartości oddzielonych średnikami (np. 34.0;65.0;53.0)');
                    return;
                }

                $values = explode(';', $value);

                if (count($values) < 2) {
                    $this->addError($attribute, 'Test oddechowy wymaga co najmniej 2 pomiarów (baseline + kontrolny)');
                    return;
                }

                // Sprawdź czy wszystkie wartości są numeryczne
                foreach ($values as $index => $val) {
                    $val = trim($val);
                    if ($val !== '' && !is_numeric(str_replace(',', '.', $val))) {
                        $timePoint = $index === 0 ? 'baseline' : (($index * 30) . ' min');
                        $this->addError($attribute, "Wartość dla punktu {$timePoint} musi być liczbą");
                    }
                }
            }
        }
    }

    /**
     * Metoda do oceny wartości (bez output debug)
     */
    private function evaluateValue() {
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
    public function getNumericValue() {
        return $this->normalized_value !== null ? $this->normalized_value : (is_numeric($this->value) ? (float) $this->value : null);
    }

    /**
     * Zwraca wartość sformatowaną do wyświetlenia (z polskim formatowaniem)
     */
    public function getDisplayValue() {
        if ($this->normalized_value !== null) {
            // Wyświetl z przecinkiem dla lepszej czytelności w polskim kontekście
            return str_replace('.', ',', $this->value);
        }
        return $this->value;
    }

    /**
     * Sprawdza czy wartość jest numeryczna
     */
    public function isNumeric() {
        return $this->normalized_value !== null || is_numeric($this->value);
    }

    /**
     * Generuje rekomendację na podstawie wyniku sprawdzenia
     */
    private function generateRecommendation($checkResult) {
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
    public function isNormal() {
        return !$this->is_abnormal;
    }

    /**
     * Zwraca status jako tekst
     */
    public function getStatusText() {
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
    public function getStatusColor() {
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
    public function requiresAttention() {
        return $this->is_abnormal || in_array($this->warning_level, [
                    \app\models\ParameterNorm::WARNING_LEVEL_WARNING,
                    \app\models\ParameterNorm::WARNING_LEVEL_CAUTION
        ]);
    }

    /**
     * Po zapisaniu - aktualizuj flagę w TestResult
     */
    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);

        // Aktualizuj flagę has_abnormal_values w TestResult
        if ($this->testResult) {
            $this->testResult->updateAbnormalFlag();
        }
    }

    /**
     * Po usunięciu - aktualizuj flagę w TestResult
     */
    public function afterDelete() {
        parent::afterDelete();

        // Aktualizuj flagę has_abnormal_values w TestResult
        if ($this->testResult) {
            $this->testResult->updateAbnormalFlag();
        }
    }

    /**
     * Walidacja wartości numerycznej - obsługuje format z przecinkiem
     */
    public function validateNumericValue($attribute, $params) {
        if (!empty($this->$attribute)) {
            $value = trim($this->$attribute);

            // Sprawdź typ parametru
            if ($this->parameter) {
                // Jeśli parametr jest typu TEXT - pozwól na dowolny tekst
                if ($this->parameter->type === \app\models\TestParameter::TYPE_TEXT) {
                    return; // Nie waliduj - parametr tekstowy
                }

                // Jeśli parametr ma normę typu positive_negative - sprawdź tylko akceptowane wartości tekstowe
                if ($this->norm && $this->norm->type === \app\models\ParameterNorm::TYPE_POSITIVE_NEGATIVE) {
                    if (!$this->isTextValue($value)) {
                        $this->addError($attribute, 'Dla tego typu normy użyj wartości: negatywny, pozytywny, negative, positive, ujemny, dodatny itp.');
                    }
                    return;
                }
            }

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
    private function isTextValue($value) {
        // Lista akceptowanych wartości tekstowych dla badań medycznych
        $acceptedTextValues = [
            // Pozytywny/Negatywny
            'ujemny', 'negatywny', 'negative', '-', 'neg',
            'dodatny', 'pozytywny', 'positive', '+', 'pos',
            // Ślady
            'ślad', 'trace', 'tr', 'slad',
            // Nieoznaczalne
            'nieoznaczalny', 'niedostępny', 'n/a', 'nd', 'nie dotyczy',
            // Problemy z próbką
            'hemoliza', 'lipemia', 'ikteryczne', 'hemolizowana', 'lipemiczna',
            // Ogólne
            'prawidłowy', 'nieprawidłowy', 'normal', 'abnormal',
            'obecny', 'nieobecny', 'present', 'absent',
            'reaktywny', 'niereaktywny', 'reactive', 'non-reactive',
            // Dodatkowe wartości tekstowe
            'tak', 'nie', 'yes', 'no', 'true', 'false',
            'wysokie', 'niskie', 'high', 'low',
            'graniczne', 'borderline',
            'indeterminate', 'niejednoznaczny'
        ];

        return in_array(strtolower(trim($value)), $acceptedTextValues);
    }

}
