<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class TestParameter extends ActiveRecord
{
    const TYPE_POSITIVE_NEGATIVE = 'positive_negative';
    const TYPE_RANGE = 'range';
    const TYPE_SINGLE_THRESHOLD = 'single_threshold';
    const TYPE_MULTIPLE_THRESHOLDS = 'multiple_thresholds';
    const TYPE_NUMERIC = 'numeric';

    public static function tableName()
    {
        return '{{%test_parameters}}';
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
            [['test_template_id', 'name', 'type'], 'required'],
            [['test_template_id', 'order_index'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['unit'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 50],
            [['type'], 'in', 'range' => [
                self::TYPE_POSITIVE_NEGATIVE,
                self::TYPE_RANGE,
                self::TYPE_SINGLE_THRESHOLD,
                self::TYPE_MULTIPLE_THRESHOLDS,
                self::TYPE_NUMERIC,
            ]],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'test_template_id' => 'Szablon badania',
            'name' => 'Nazwa parametru',
            'unit' => 'Jednostka',
            'type' => 'Typ parametru',
            'order_index' => 'Kolejność',
        ];
    }

    public function getTestTemplate()
    {
        return $this->hasOne(TestTemplate::class, ['id' => 'test_template_id']);
    }

    public function getNorms()
    {
        return $this->hasMany(ParameterNorm::class, ['parameter_id' => 'id']);
    }

    public function getPrimaryNorm()
    {
        return $this->hasOne(ParameterNorm::class, ['parameter_id' => 'id'])->where(['is_primary' => true]);
    }

    public function getResultValues()
    {
        return $this->hasMany(ResultValue::class, ['parameter_id' => 'id']);
    }

    public static function getTypeOptions()
    {
        return [
            self::TYPE_POSITIVE_NEGATIVE => 'Pozytywny/Negatywny',
            self::TYPE_RANGE => 'Zakres min-max',
            self::TYPE_SINGLE_THRESHOLD => 'Pojedynczy próg',
            self::TYPE_MULTIPLE_THRESHOLDS => 'Wiele progów',
            self::TYPE_NUMERIC => 'Numeryczny',
        ];
    }
}




