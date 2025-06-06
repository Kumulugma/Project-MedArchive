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
            [['threshold_direction'], 'in', 'range' => ['above', 'below'], 'when' => function($model) {
                return $model->type === self::TYPE_SINGLE_THRESHOLD;
            }],
            [['thresholds_config'], 'string'],
            [['is_primary'], 'boolean'],
            [['conversion_factor'], 'default', 'value' => 1],
            [['conversion_offset'], 'default', 'value' => 0],
            
            // Conditional validation for range type
            [['min_value', 'max_value'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_RANGE;
            }],
            [['min_value'], 'compare', 'compareAttribute' => 'max_value', 'operator' => '<', 'when' => function($model) {
                return $model->type === self::TYPE_RANGE && $model->min_value !== null && $model->max_value !== null;
            }],
            
            // Conditional validation for single threshold
            [['threshold_value', 'threshold_direction'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_SINGLE_THRESHOLD;
            }],
            
            // Conditional validation for multiple thresholds
            [['thresholds_config'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_MULTIPLE_THRESHOLDS;
            }],
            [['thresholds_config'], 'validateThresholdsConfig'],
        ];
    }

    public function validateThresholdsConfig($attribute, $params)
    {
        if ($this->type === self::TYPE_MULTIPLE_THRESHOLDS && !empty($this->$attribute)) {
            $thresholds = json_decode($this->$attribute, true);
            if (!is_array($thresholds) || empty($thresholds)) {
                $this->addError($attribute, 'Konfiguracja progów musi zawierać przynajmniej jeden próg.');
                return;
            }
            
            foreach ($thresholds as $threshold) {
                if (!isset($threshold['value']) || !is_numeric($threshold['value'])) {
                    $this->addError($attribute, 'Każdy próg musi mieć prawidłową wartość numeryczną.');
                    return;
                }
            }
        }
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
}