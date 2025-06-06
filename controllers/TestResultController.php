<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\TestResult;
use app\models\TestTemplate;
use app\models\ResultValue;
use app\models\search\TestResultSearch;

class TestResultController extends Controller
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
    $searchModel = new TestResultSearch();
    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

    return $this->render('index', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
    ]);
}

    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionCreate()
    {
        $model = new TestResult();
        $templates = TestTemplate::find()->where(['status' => 1])->all();

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $template = TestTemplate::findOne($model->test_template_id);
                    $this->saveResultValues($model, $template);
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Wynik badania został zapisany.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Błąd podczas zapisywania: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'templates' => $templates,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    ResultValue::deleteAll(['test_result_id' => $model->id]);
                    $this->saveResultValues($model, $model->testTemplate);
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Wynik badania został zaktualizowany.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Błąd podczas zapisywania: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Wynik badania został usunięty.');

        return $this->redirect(['index']);
    }

    public function actionCompare($templateId)
    {
        $template = TestTemplate::findOne($templateId);
        if (!$template) {
            throw new NotFoundHttpException('Szablon badania nie został znaleziony.');
        }

        $results = TestResult::find()
            ->where(['test_template_id' => $templateId])
            ->with(['resultValues.parameter', 'resultValues.norm'])
            ->orderBy('test_date ASC')
            ->all();

        $selectedResults = [];
        if (Yii::$app->request->isPost) {
            $selectedIds = Yii::$app->request->post('selected_results', []);
            $selectedResults = array_filter($results, function($result) use ($selectedIds) {
                return in_array($result->id, $selectedIds);
            });
        }

        return $this->render('compare', [
            'template' => $template,
            'results' => $results,
            'selectedResults' => $selectedResults,
        ]);
    }

    protected function saveResultValues($testResult, $template)
    {
        $post = Yii::$app->request->post();
        
        foreach ($template->parameters as $parameter) {
            $valueKey = 'parameter_' . $parameter->id;
            $normKey = 'norm_' . $parameter->id;
            
            if (isset($post[$valueKey]) && $post[$valueKey] !== '') {
                $resultValue = new ResultValue();
                $resultValue->test_result_id = $testResult->id;
                $resultValue->parameter_id = $parameter->id;
                $resultValue->value = $post[$valueKey];
                
                if (isset($post[$normKey]) && $post[$normKey] !== '') {
                    $resultValue->norm_id = $post[$normKey];
                }
                
                $resultValue->save();
            }
        }
        
        $testResult->updateAbnormalFlag();
    }

    protected function findModel($id)
    {
        if (($model = TestResult::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Wynik badania nie został znaleziony.');
    }
    public function actionGetTemplateParameters()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $templateId = Yii::$app->request->get('templateId');
    $template = TestTemplate::findOne($templateId);
    
    if (!$template) {
        return ['error' => 'Template not found'];
    }
    
    return $this->renderAjax('_parameters', [
        'template' => $template,
        'result' => null,
    ]);
}

public function actionValidateValue()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $value = Yii::$app->request->post('value');
    $normId = Yii::$app->request->post('normId');
    
    $norm = ParameterNorm::findOne($normId);
    if (!$norm) {
        return ['error' => 'Norm not found'];
    }
    
    $result = $norm->checkValue($value);
    
    return [
        'is_normal' => $result['is_normal'],
        'type' => $result['type'] ?? null,
        'message' => $this->getValidationMessage($result),
    ];
}

public function actionGetChartData()
{
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $resultIds = Yii::$app->request->post('resultIds', []);
    $parameterIds = Yii::$app->request->post('parameterIds', []);
    
    if (empty($resultIds) || empty($parameterIds)) {
        return ['success' => false, 'error' => 'Missing data'];
    }
    
    $results = TestResult::find()
        ->where(['id' => $resultIds])
        ->with(['resultValues' => function($query) use ($parameterIds) {
            $query->where(['parameter_id' => $parameterIds]);
        }])
        ->orderBy('test_date ASC')
        ->all();
    
    $parameters = TestParameter::find()
        ->where(['id' => $parameterIds])
        ->all();
    
    $chartData = [
        'dates' => [],
        'parameters' => [],
    ];
    
    // Prepare dates
    foreach ($results as $result) {
        $chartData['dates'][] = Yii::$app->formatter->asDate($result->test_date);
    }
    
    // Prepare parameter data
    foreach ($parameters as $parameter) {
        $parameterData = [
            'name' => $parameter->name,
            'unit' => $parameter->unit,
            'values' => [],
        ];
        
        foreach ($results as $result) {
            $value = null;
            foreach ($result->resultValues as $resultValue) {
                if ($resultValue->parameter_id == $parameter->id) {
                    $value = is_numeric($resultValue->value) ? (float)$resultValue->value : null;
                    break;
                }
            }
            $parameterData['values'][] = $value;
        }
        
        $chartData['parameters'][] = $parameterData;
    }
    
    return ['success' => true, 'data' => $chartData];
}

private function getValidationMessage($result)
{
    if ($result['is_normal']) {
        return 'Wartość w normie';
    }
    
    switch ($result['type']) {
        case 'low':
            return 'Wartość poniżej normy';
        case 'high':
            return 'Wartość powyżej normy';
        default:
            return 'Wartość nieprawidłowa';
    }
}

}


