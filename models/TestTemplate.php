<?php
// models/TestTemplate.php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class TestTemplate extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%test_templates}}';
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
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['status'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Nazwa badania',
            'description' => 'Opis',
            'status' => 'Status',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }

    public function getParameters()
    {
        return $this->hasMany(TestParameter::class, ['test_template_id' => 'id'])->orderBy('order_index');
    }

    public function getResults()
    {
        return $this->hasMany(TestResult::class, ['test_template_id' => 'id']);
    }

    public function getQueueItems()
    {
        return $this->hasMany(TestQueue::class, ['test_template_id' => 'id']);
    }
}
