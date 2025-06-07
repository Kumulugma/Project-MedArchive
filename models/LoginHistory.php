<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class LoginHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%login_history}}';
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
            [['user_id', 'ip_address', 'user_agent'], 'required'],
            [['user_id'], 'integer'],
            [['login_time', 'logout_time'], 'safe'],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
            [['success'], 'boolean'],
            [['location'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Użytkownik',
            'ip_address' => 'Adres IP',
            'user_agent' => 'Przeglądarka',
            'login_time' => 'Czas logowania',
            'logout_time' => 'Czas wylogowania',
            'success' => 'Sukces',
            'location' => 'Lokalizacja',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Zapisz próbę logowania
     */
    public static function recordLogin($userId, $success = true)
    {
        $history = new self();
        $history->user_id = $userId;
        $history->ip_address = Yii::$app->request->userIP;
        $history->user_agent = Yii::$app->request->userAgent;
        $history->success = $success;
        $history->login_time = date('Y-m-d H:i:s');
        $history->location = self::getLocationFromIP(Yii::$app->request->userIP);
        $history->save();
    }

    /**
     * Zapisz wylogowanie
     */
    public static function recordLogout($userId)
    {
        $lastLogin = self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['is', 'logout_time', null])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if ($lastLogin) {
            $lastLogin->logout_time = date('Y-m-d H:i:s');
            $lastLogin->save();
        }
    }

    /**
     * Pobierz lokalizację na podstawie IP (uproszczona wersja)
     */
    private static function getLocationFromIP($ip)
    {
        // Uproszczona implementacja - w rzeczywistości można użyć API jak GeoIP
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }
        return 'Nieznana lokalizacja';
    }
}