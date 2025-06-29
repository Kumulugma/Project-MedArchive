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

    // Zastąp w controllers/DashboardController.php metodę actionIndex()

public function actionIndex()
{
    $totalResults = TestResult::find()->count();
    $totalTemplates = TestTemplate::find()->where(['status' => 1])->count();
    
    $totalTests = TestQueue::find()->count();
    
    $normalResults = TestResult::find()
        ->where(['has_abnormal_values' => false])
        ->count();
    
    $pendingTests = TestQueue::find()
        ->where(['status' => TestQueue::STATUS_PENDING])
        ->count();
    
    // POPRAWKA: Badania nadchodzące w ciągu 30 dni (nie tylko 7)
    $upcomingTests = TestQueue::find()
        ->where(['status' => TestQueue::STATUS_PENDING])
        ->andWhere(['>=', 'scheduled_date', date('Y-m-d')]) // Od dzisiaj
        ->andWhere(['<=', 'scheduled_date', date('Y-m-d', strtotime('+30 days'))]) // Do 30 dni
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
        'totalTests' => $totalTests,
        'normalResults' => $normalResults,
        'pendingTests' => $pendingTests,
        'upcomingTests' => $upcomingTests,
        'recentResults' => $recentResults,
        'abnormalResults' => $abnormalResults,
    ]);
}
}