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

/**
 * Planuje ponowne badanie na podstawie szablonu
 */
public function actionScheduleRetest()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    if (!\Yii::$app->request->isPost) {
        return ['success' => false, 'error' => 'Nieprawidłowa metoda żądania'];
    }
    
    $templateId = \Yii::$app->request->post('test_template_id');
    $scheduledDate = \Yii::$app->request->post('scheduled_date');
    $comment = \Yii::$app->request->post('comment', '');
    
    // Walidacja danych
    if (!$templateId || !$scheduledDate) {
        return ['success' => false, 'error' => 'Brak wymaganych danych'];
    }
    
    // Sprawdź czy szablon istnieje
    $template = \app\models\TestTemplate::findOne($templateId);
    if (!$template) {
        return ['success' => false, 'error' => 'Szablon badania nie został znaleziony'];
    }
    
    // Walidacja daty
    $selectedDate = \DateTime::createFromFormat('Y-m-d', $scheduledDate);
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    
    if (!$selectedDate || $selectedDate <= $today) {
        return ['success' => false, 'error' => 'Data badania musi być w przyszłości'];
    }
    
    // Sprawdź czy nie ma już zaplanowanego badania z tego szablonu w przyszłości
    $existingQueueItem = TestQueue::find()
        ->where([
            'test_template_id' => $templateId,
            'status' => TestQueue::STATUS_PENDING
        ])
        ->andWhere(['>', 'scheduled_date', date('Y-m-d')])
        ->one();
    
    if ($existingQueueItem) {
        return [
            'success' => false, 
            'error' => 'Badanie z tym szablonem jest już zaplanowane na ' . 
                      \Yii::$app->formatter->asDate($existingQueueItem->scheduled_date)
        ];
    }
    
    // Utwórz nowy wpis w kolejce
    $queueItem = new TestQueue();
    $queueItem->test_template_id = $templateId;
    $queueItem->scheduled_date = $scheduledDate;
    $queueItem->status = TestQueue::STATUS_PENDING;
    $queueItem->reminder_sent = false;
    
    // Dodaj informację o tym, że to ponowne badanie do komentarza
    $retestComment = 'Ponowne badanie - ';
    if ($comment) {
        $queueItem->comment = $retestComment . $comment;
    } else {
        $queueItem->comment = $retestComment . 'zaplanowane z powodu nieprawidłowych wyników';
    }
    
    if ($queueItem->save()) {
        return [
            'success' => true,
            'message' => 'Badanie zostało zaplanowane na ' . \Yii::$app->formatter->asDate($scheduledDate),
            'queue_id' => $queueItem->id
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Błąd podczas zapisywania: ' . implode(', ', $queueItem->getFirstErrors())
        ];
    }
}

}