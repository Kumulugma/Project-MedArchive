<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\models\User;
use app\models\LoginHistory;
use app\models\TestResult;
use app\models\TestTemplate;
use app\models\TestQueue;
use app\models\ChangePasswordForm;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Historia logowań użytkownika
     */
    public function actionLoginHistory()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => LoginHistory::find()
                ->where(['user_id' => Yii::$app->user->id])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('login-history', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Profil użytkownika
     */
    public function actionProfile()
    {
        $user = Yii::$app->user->identity;
        
        // Statystyki użytkownika
        $stats = [
            'total_results' => \app\models\TestResult::find()->count(),
            'total_templates' => \app\models\TestTemplate::find()->count(),
            'pending_tests' => \app\models\TestQueue::find()->where(['status' => 'pending'])->count(),
            'last_login' => LoginHistory::find()
                ->where(['user_id' => $user->id, 'success' => true])
                ->orderBy(['created_at' => SORT_DESC])
                ->one(),
        ];

        return $this->render('profile', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Ustawienia użytkownika
     */
    public function actionSettings()
    {
        $user = Yii::$app->user->identity;

        if ($user->load(Yii::$app->request->post()) && $user->save()) {
            Yii::$app->session->setFlash('success', 'Ustawienia zostały zapisane.');
            return $this->refresh();
        }

        return $this->render('settings', [
            'user' => $user,
        ]);
    }

    /**
     * Zmiana hasła - NOWA IMPLEMENTACJA
     */
    public function actionChangePassword()
    {
        $model = new ChangePasswordForm();
        
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate() && $model->changePassword()) {
                Yii::$app->session->setFlash('success', 'Hasło zostało pomyślnie zmienione. Możesz teraz korzystać z nowego hasła.');
                return $this->redirect(['settings']);
            }
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }
}