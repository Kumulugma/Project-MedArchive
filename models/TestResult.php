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
}
