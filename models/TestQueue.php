<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class TestQueue extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function tableName()
    {
        return '{{%test_queue}}';
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
            [['test_template_id', 'scheduled_date'], 'required'],
            [['test_template_id'], 'integer'],
            [['scheduled_date'], 'date', 'format' => 'php:Y-m-d'],
            [['comment'], 'string'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_CANCELLED]],
            [['reminder_sent'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'test_template_id' => 'Badanie',
            'scheduled_date' => 'Planowana data',
            'comment' => 'Komentarz',
            'status' => 'Status',
            'reminder_sent' => 'Przypomnienie wysłane',
        ];
    }

    public function getTestTemplate()
    {
        return $this->hasOne(TestTemplate::class, ['id' => 'test_template_id']);
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Oczekujące',
            self::STATUS_COMPLETED => 'Wykonane',
            self::STATUS_CANCELLED => 'Anulowane',
        ];
    }

    public function isUpcoming()
    {
        return $this->status === self::STATUS_PENDING && 
               strtotime($this->scheduled_date) >= strtotime(date('Y-m-d'));
    }

    public function isDue()
    {
        return $this->status === self::STATUS_PENDING && 
               strtotime($this->scheduled_date) <= strtotime('+7 days');
    }
}