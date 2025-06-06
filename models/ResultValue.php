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
                $check = $this->norm->checkValue($this->value);
                $this->is_abnormal = !$check['is_normal'];
                $this->abnormality_type = $check['type'];
            }
            return true;
        }
        return false;
    }
}
