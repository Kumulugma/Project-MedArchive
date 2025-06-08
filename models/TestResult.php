<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class TestResult extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%test_results}}';
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
            [['test_template_id', 'test_date'], 'required'],
            [['test_template_id'], 'integer'],
            [['test_date'], 'date', 'format' => 'php:Y-m-d'],
            [['comment'], 'string'],
            [['has_abnormal_values'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'test_template_id' => 'Badanie',
            'test_date' => 'Data badania',
            'comment' => 'Komentarz',
            'has_abnormal_values' => 'Nieprawidłowe wartości',
        ];
    }

    public function getTestTemplate()
    {
        return $this->hasOne(TestTemplate::class, ['id' => 'test_template_id']);
    }

    public function getResultValues()
    {
        return $this->hasMany(ResultValue::class, ['test_result_id' => 'id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->updateAbnormalFlag();
    }

    public function updateAbnormalFlag()
    {
        $hasAbnormal = ResultValue::find()
            ->where(['test_result_id' => $this->id, 'is_abnormal' => true])
            ->exists();

        if ($this->has_abnormal_values !== $hasAbnormal) {
            $this->updateAttributes(['has_abnormal_values' => $hasAbnormal]);
        }
    }
    
    /**
 * Zwraca szczegółowy status wyników z uwzględnieniem ostrzeżeń
 * @return array [status, badge_class, icon, message, warning_count]
 */
public function getDetailedStatus()
{
    if ($this->has_abnormal_values) {
        return [
            'status' => 'abnormal',
            'badge_class' => 'bg-danger',
            'icon' => 'fas fa-times-circle',
            'message' => 'Nieprawidłowe',
            'warning_count' => 0
        ];
    }
    
    // Sprawdź ostrzeżenia w wynikach
    $warningCount = 0;
    $cautionCount = 0;
    $criticalWarnings = 0;
    
    foreach ($this->resultValues as $resultValue) {
        if ($resultValue->warning_level) {
            switch ($resultValue->warning_level) {
                case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                    $warningCount++;
                    break;
                case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                    $cautionCount++;
                    break;
                case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL:
                    $criticalWarnings++;
                    break;
            }
        }
    }
    
    // Hierarchia statusów (od najważniejszego)
    if ($criticalWarnings > 0) {
        return [
            'status' => 'critical_warning',
            'badge_class' => 'bg-danger',
            'icon' => 'fas fa-exclamation-triangle',
            'message' => 'Wymaga uwagi',
            'warning_count' => $criticalWarnings
        ];
    }
    
    if ($warningCount > 0) {
        return [
            'status' => 'warning',
            'badge_class' => 'bg-warning text-dark',
            'icon' => 'fas fa-exclamation-triangle',
            'message' => 'Ostrzeżenie',
            'warning_count' => $warningCount
        ];
    }
    
    if ($cautionCount > 0) {
        return [
            'status' => 'caution',
            'badge_class' => 'bg-info',
            'icon' => 'fas fa-info-circle',
            'message' => 'Do obserwacji',
            'warning_count' => $cautionCount
        ];
    }
    
    return [
        'status' => 'normal',
        'badge_class' => 'bg-success',
        'icon' => 'fas fa-check-circle',
        'message' => 'Prawidłowe',
        'warning_count' => 0
    ];
}

/**
 * Sprawdza czy wynik ma jakiekolwiek ostrzeżenia
 */
public function hasWarnings()
{
    foreach ($this->resultValues as $resultValue) {
        if ($resultValue->warning_level && $resultValue->warning_level !== \app\models\ParameterNorm::WARNING_LEVEL_NONE) {
            return true;
        }
    }
    return false;
}

/**
 * Zwraca liczbę ostrzeżeń określonego typu
 */
public function getWarningCount($level = null)
{
    if (!$level) {
        return array_sum([
            $this->getWarningCount(\app\models\ParameterNorm::WARNING_LEVEL_WARNING),
            $this->getWarningCount(\app\models\ParameterNorm::WARNING_LEVEL_CAUTION),
            $this->getWarningCount(\app\models\ParameterNorm::WARNING_LEVEL_CRITICAL)
        ]);
    }
    
    $count = 0;
    foreach ($this->resultValues as $resultValue) {
        if ($resultValue->warning_level === $level) {
            $count++;
        }
    }
    return $count;
}
}
