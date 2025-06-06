<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\TestResult;
use app\models\TestQueue;
use app\models\TestTemplate;

class DashboardController extends Controller
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

    public function actionIndex()
    {
        $totalResults = TestResult::find()->count();
        $totalTemplates = TestTemplate::find()->where(['status' => 1])->count();
        
        $upcomingTests = TestQueue::find()
            ->where(['status' => TestQueue::STATUS_PENDING])
            ->andWhere(['<=', 'scheduled_date', date('Y-m-d', strtotime('+7 days'))])
            ->with('testTemplate')
            ->orderBy('scheduled_date ASC')
            ->limit(10)
            ->all();

        $recentResults = TestResult::find()
            ->with(['testTemplate', 'resultValues'])
            ->orderBy('test_date DESC, created_at DESC')
            ->limit(10)
            ->all();

        $abnormalResults = TestResult::find()
            ->where(['has_abnormal_values' => true])
            ->count();

        return $this->render('index', [
            'totalResults' => $totalResults,
            'totalTemplates' => $totalTemplates,
            'upcomingTests' => $upcomingTests,
            'recentResults' => $recentResults,
            'abnormalResults' => $abnormalResults,
        ]);
    }
}



