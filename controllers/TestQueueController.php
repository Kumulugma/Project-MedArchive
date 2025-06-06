<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\TestQueue;
use app\models\TestTemplate;
use app\models\search\TestQueueSearch;

class TestQueueController extends Controller
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
    $searchModel = new TestQueueSearch();
    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

    return $this->render('index', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
    ]);
}

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new TestQueue();
        $templates = TestTemplate::find()->where(['status' => 1])->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Badanie zostało dodane do kolejki.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'templates' => $templates,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Badanie w kolejce zostało zaktualizowane.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Badanie zostało usunięte z kolejki.');

        return $this->redirect(['index']);
    }

    public function actionComplete($id)
    {
        $model = $this->findModel($id);
        $model->status = TestQueue::STATUS_COMPLETED;
        
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Badanie zostało oznaczone jako wykonane.');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = TestQueue::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Element kolejki nie został znaleziony.');
    }
    public function actionSendReminder()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $id = Yii::$app->request->post('id');
    $model = TestQueue::findOne($id);
    
    if (!$model) {
        return ['success' => false, 'error' => 'Queue item not found'];
    }
    
    // Tutaj byłoby wysyłanie maila
    // $this->sendReminderEmail($model);
    
    $model->reminder_sent = true;
    $model->save(false);
    
    return ['success' => true];
}

public function actionBulkComplete()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $ids = Yii::$app->request->post('ids', []);
    
    if (empty($ids)) {
        return ['success' => false, 'error' => 'No items selected'];
    }
    
    TestQueue::updateAll(
        ['status' => TestQueue::STATUS_COMPLETED], 
        ['id' => $ids, 'status' => TestQueue::STATUS_PENDING]
    );
    
    return ['success' => true, 'updated' => count($ids)];
}

public function actionGetCalendarData()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $items = TestQueue::find()
        ->with('testTemplate')
        ->where(['status' => TestQueue::STATUS_PENDING])
        ->all();
    
    $events = [];
    foreach ($items as $item) {
        $events[] = [
            'id' => $item->id,
            'title' => $item->testTemplate->name,
            'date' => $item->scheduled_date,
            'isDue' => $item->isDue(),
            'url' => Url::to(['view', 'id' => $item->id]),
        ];
    }
    
    return $events;
}
}