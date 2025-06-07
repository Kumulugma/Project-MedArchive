<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $last_login_at
 * @property string $auth_key
 * @property string $password_reset_token
 */
class User extends ActiveRecord implements IdentityInterface
{
    // POPRAWNE STAŁE STATUSU - zgodne z bazą danych
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            
            [['username', 'email'], 'required'],
            [['username', 'email'], 'string', 'max' => 255],
            ['username', 'unique'],
            ['email', 'email'],
            ['email', 'unique'],
            
            [['first_name', 'last_name'], 'string', 'max' => 100],
            ['phone', 'string', 'max' => 20],
            
            [['created_at', 'updated_at', 'last_login_at'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Nazwa użytkownika',
            'email' => 'E-mail',
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'phone' => 'Telefon',
            'status' => 'Status',
            'created_at' => 'Data utworzenia',
            'updated_at' => 'Data aktualizacji',
            'last_login_at' => 'Ostatnie logowanie',
        ];
    }

    public static function tableName()
    {
        return '{{%users}}';
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->generateAuthKey();
            }
            return true;
        }
        return false;
    }

    public function getFullName()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->username;
    }

    public function updateLastLogin()
    {
        $this->last_login_at = time();
        $this->save(false);
    }

    /**
     * Znajduje użytkownika po tokenie resetowania hasła
     *
     * @param string $token token resetowania hasła
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Sprawdza czy token resetowania hasła jest prawidłowy
     *
     * @param string $token token resetowania hasła
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? 3600;
        return $timestamp + $expire >= time();
    }

    /**
     * Generuje token resetowania hasła
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Usuwa token resetowania hasła
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
}