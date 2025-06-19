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
use app\models\TestParameter;

class TestResultController extends Controller {

    public function behaviors() {
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

    public function actionIndex() {
        $searchModel = new TestResultSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id) {
        $model = $this->findModel($id);

        return $this->render('view', [
                    'model' => $model,
        ]);
    }

    public function actionCreate() {
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

    public function actionUpdate($id) {
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

    public function actionDelete($id) {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Wynik badania został usunięty.');

        return $this->redirect(['index']);
    }

    public function actionCompare($templateId) {
        $template = TestTemplate::find()
                ->where(['id' => $templateId])
                ->with(['parameters.norms'])
                ->one();

        if (!$template) {
            throw new NotFoundHttpException('Szablon badania nie został znaleziony.');
        }

        $results = TestResult::find()
                ->where(['test_template_id' => $templateId])
                ->with(['resultValues.parameter.norms', 'resultValues.norm'])
                ->orderBy('test_date ASC')
                ->all();

        $selectedResults = [];
        if (Yii::$app->request->isPost) {
            $selectedIds = Yii::$app->request->post('selected_results', []);
            $selectedResults = array_filter($results, function ($result) use ($selectedIds) {
                return in_array($result->id, $selectedIds);
            });
        }

        return $this->render('compare', [
                    'template' => $template,
                    'results' => $results,
                    'selectedResults' => $selectedResults,
        ]);
    }

    protected function saveResultValues($testResult, $template) {
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

    protected function findModel($id) {
        if (($model = TestResult::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Wynik badania nie został znaleziony.');
    }

    public function actionGetTemplateParameters() {
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

    private function getValidationMessage($result) {
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

    public function actionDebugNorms($id) {
        $result = TestResult::findOne($id);
        if (!$result) {
            throw new NotFoundHttpException('Wynik nie został znaleziony.');
        }

        echo "<h1>Debug norm dla wyniku #$id</h1>";
        echo "<style>pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0; }</style>";

        foreach ($result->resultValues as $resultValue) {
            echo "<hr><h2>Parametr: " . $resultValue->parameter->name . "</h2>";

            echo "<pre>";
            echo "=== INFORMACJE PODSTAWOWE ===\n";
            echo "Wartość: " . $resultValue->value . "\n";
            echo "Jednostka: " . ($resultValue->parameter->unit ?: 'brak') . "\n";
            echo "is_abnormal w bazie: " . ($resultValue->is_abnormal ? 'TRUE' : 'FALSE') . "\n";
            echo "norm_id: " . ($resultValue->norm_id ?: 'BRAK') . "\n";
            echo "</pre>";

            if ($resultValue->norm) {
                $norm = $resultValue->norm;
                echo "<pre>";
                echo "=== INFORMACJE O NORMIE ===\n";
                echo "Nazwa normy: " . $norm->name . "\n";
                echo "Typ normy: " . $norm->type . "\n";
                echo "Is primary: " . ($norm->is_primary ? 'TRUE' : 'FALSE') . "\n";

                if ($norm->type === 'single_threshold') {
                    echo "Próg: " . $norm->threshold_value . "\n";
                    echo "Kierunek: " . $norm->threshold_direction . "\n";
                    echo "Opis: " . ($norm->threshold_direction === 'below' ? 'Normalny ≤ ' . $norm->threshold_value : 'Normalny ≥ ' . $norm->threshold_value) . "\n";
                } elseif ($norm->type === 'range') {
                    echo "Min: " . $norm->min_value . "\n";
                    echo "Max: " . $norm->max_value . "\n";
                }

                echo "Ostrzeżenia włączone: " . ($norm->warning_enabled ? 'TRUE' : 'FALSE') . "\n";
                if ($norm->warning_enabled) {
                    echo "Warning margin %: " . ($norm->warning_margin_percent ?: 'brak') . "\n";
                    echo "Caution margin %: " . ($norm->caution_margin_percent ?: 'brak') . "\n";
                }
                echo "</pre>";

                // Test sprawdzania wartości
                echo "<pre>";
                echo "=== TEST SPRAWDZANIA WARTOŚCI ===\n";
                echo "Wartość do sprawdzenia: " . $resultValue->value . "\n";

                // Test checkValue
                $basicResult = $norm->checkValue($resultValue->value);
                echo "checkValue() wynik: " . json_encode($basicResult, JSON_PRETTY_PRINT) . "\n";

                // Test checkValueWithWarnings
                if (method_exists($norm, 'checkValueWithWarnings')) {
                    $warningResult = $norm->checkValueWithWarnings($resultValue->value);
                    echo "checkValueWithWarnings() wynik: " . json_encode($warningResult, JSON_PRETTY_PRINT) . "\n";
                } else {
                    echo "checkValueWithWarnings() NIE ISTNIEJE!\n";
                }

                // Manual test dla single_threshold
                if ($norm->type === 'single_threshold') {
                    $value = floatval($resultValue->value);
                    $threshold = floatval($norm->threshold_value);
                    echo "\n=== MANUAL TEST ===\n";
                    echo "Wartość (float): $value\n";
                    echo "Próg (float): $threshold\n";
                    echo "Kierunek: " . $norm->threshold_direction . "\n";

                    if ($norm->threshold_direction === 'below') {
                        echo "Test: $value <= $threshold = " . ($value <= $threshold ? 'TRUE (normalny)' : 'FALSE (nieprawidłowy)') . "\n";
                    } else {
                        echo "Test: $value >= $threshold = " . ($value >= $threshold ? 'TRUE (normalny)' : 'FALSE (nieprawidłowy)') . "\n";
                    }
                }
                echo "</pre>";
            } else {
                echo "<pre>BRAK NORMY!</pre>";
            }
        }

        exit; // Zatrzymaj wykonywanie
    }

    public function actionGetChartData() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Nieprawidłowa metoda żądania'];
        }

        try {
            $resultIds = Yii::$app->request->post('resultIds', []);
            $parameterIds = Yii::$app->request->post('parameterIds', []);

            if (empty($resultIds) || empty($parameterIds)) {
                return ['success' => false, 'error' => 'Brak wybranych wyników lub parametrów'];
            }

            $results = TestResult::find()
                    ->where(['id' => $resultIds])
                    ->with(['resultValues' => function ($query) use ($parameterIds) {
                            $query->where(['parameter_id' => $parameterIds]);
                        }])
                    ->orderBy('test_date ASC')
                    ->all();

            // Załaduj parametry z normami
            $parameters = TestParameter::find()
                    ->where(['id' => $parameterIds])
                    ->with(['norms' => function ($query) {
                            $query->where(['is_primary' => 1]); // Tylko primary normy
                        }])
                    ->all();

            $chartData = [
                'dates' => [],
                'parameters' => [],
            ];

            // Przygotuj daty
            foreach ($results as $result) {
                $chartData['dates'][] = Yii::$app->formatter->asDate($result->test_date);
            }

            // Przygotuj dane parametrów z normami
            foreach ($parameters as $parameter) {
                $parameterData = [
                    'name' => $parameter->name,
                    'unit' => $parameter->unit,
                    'values' => [],
                    'norms' => [] // Dodane dane norm
                ];

                // Dodaj wartości parametru
                foreach ($results as $result) {
                    $value = null;
                    foreach ($result->resultValues as $resultValue) {
                        if ($resultValue->parameter_id == $parameter->id) {
                            $value = is_numeric($resultValue->value) ? (float) $resultValue->value : null;
                            break;
                        }
                    }
                    $parameterData['values'][] = $value;
                }

                // Dodaj normy parametru
                foreach ($parameter->norms as $norm) {
                    $normData = [
                        'type' => $norm->type,
                        'name' => $norm->name
                    ];

                    if ($norm->type === 'range') {
                        $normData['min_value'] = (float) $norm->min_value;
                        $normData['max_value'] = (float) $norm->max_value;
                    } elseif ($norm->type === 'single_threshold') {
                        $normData['threshold_value'] = (float) $norm->threshold_value;
                        $normData['threshold_direction'] = $norm->threshold_direction;
                    }

                    $parameterData['norms'][] = $normData;
                }

                $chartData['parameters'][] = $parameterData;
            }

            return ['success' => true, 'data' => $chartData];
        } catch (\Exception $e) {
            Yii::error('Chart data error: ' . $e->getMessage(), 'chart');
            return ['success' => false, 'error' => 'Błąd podczas pobierania danych: ' . $e->getMessage()];
        }
    }
public function actionValidateValue() {
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    $value = Yii::$app->request->post('value');
    $normId = Yii::$app->request->post('normId');

    // Normalizuj wartość (zamień przecinek na kropkę)
    $normalizedValue = $this->normalizeValueForValidation($value);

    $norm = ParameterNorm::findOne($normId);
    if (!$norm) {
        return ['error' => 'Norm not found'];
    }

    // Użyj znormalizowanej wartości do sprawdzenia
    $result = $norm->checkValue($normalizedValue);

    return [
        'is_normal' => $result['is_normal'],
        'type' => $result['type'] ?? null,
        'message' => $this->getValidationMessage($result),
        'normalized_value' => $normalizedValue, // Zwróć znormalizowaną wartość
        'original_value' => $value // Zwróć oryginalną wartość
    ];
}

/**
 * Normalizuje wartość dla walidacji
 */
private function normalizeValueForValidation($value) {
    if (!empty($value) && is_string($value)) {
        // Usuń białe znaki
        $value = trim($value);
        
        // Zamień przecinek na kropkę
        $value = str_replace(',', '.', $value);
        
        // Jeśli to liczba, zwróć jako float, w przeciwnym razie jako string
        if (is_numeric($value)) {
            return (float) $value;
        }
    }
    
    return $value;
}

// Dodatkowo - można dodać funkcję pomocniczą do formularza
public function actionGetNormalizedValue() {
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
    $value = Yii::$app->request->post('value');
    $normalizedValue = $this->normalizeValueForValidation($value);
    
    return [
        'original' => $value,
        'normalized' => $normalizedValue,
        'is_numeric' => is_numeric(str_replace(',', '.', trim($value)))
    ];
}
    
}
