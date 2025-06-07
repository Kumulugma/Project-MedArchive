<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use app\models\User;
use app\models\PasswordForm;

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

    public function actionProfile()
    {
        $model = Yii::$app->user->identity;
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Profil został zaktualizowany.');
            return $this->refresh();
        }

        return $this->render('profile', [
            'model' => $model,
        ]);
    }

    public function actionSettings()
    {
        $model = Yii::$app->user->identity;
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Ustawienia zostały zapisane.');
            return $this->refresh();
        }

        return $this->render('settings', [
            'model' => $model,
        ]);
    }

    public function actionChangePassword()
    {
        $model = new PasswordForm();
        $model->user = Yii::$app->user->identity;

        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            Yii::$app->session->setFlash('success', 'Hasło zostało zmienione.');
            return $this->redirect(['profile']);
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }
}