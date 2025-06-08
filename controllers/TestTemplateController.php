<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\TestTemplate;
use app\models\TestParameter;
use app\models\ParameterNorm;
use app\models\search\TestTemplateSearch;
use app\components\MedicalThresholdManager;

class TestTemplateController extends Controller {

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
        $searchModel = new TestTemplateSearch();
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
        $model = new TestTemplate();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Szablon badania został utworzony.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Szablon badania został zaktualizowany.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id) {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Szablon badania został usunięty.');

        return $this->redirect(['index']);
    }

    public function actionAddParameter($id) {
        $template = $this->findModel($id);
        $parameter = new TestParameter();
        $parameter->test_template_id = $template->id;

        if ($parameter->load(Yii::$app->request->post()) && $parameter->save()) {
            Yii::$app->session->setFlash('success', 'Parametr został dodany.');
            return $this->redirect(['view', 'id' => $template->id]);
        }

        return $this->render('add-parameter', [
            'template' => $template,
            'parameter' => $parameter,
        ]);
    }

    public function actionUpdateParameter($id, $parameterId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        if ($parameter->load(Yii::$app->request->post()) && $parameter->save()) {
            Yii::$app->session->setFlash('success', 'Parametr został zaktualizowany.');
            return $this->redirect(['view', 'id' => $template->id]);
        }

        return $this->render('update-parameter', [
            'template' => $template,
            'parameter' => $parameter,
        ]);
    }


    public function actionDeleteParameter($id, $parameterId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        $parameter->delete();
        Yii::$app->session->setFlash('success', 'Parametr został usunięty.');

        return $this->redirect(['view', 'id' => $template->id]);
    }

    public function actionReorderParameters() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $orders = Yii::$app->request->post('orders', []);

        foreach ($orders as $order) {
            TestParameter::updateAll(
                ['order_index' => $order['order']],
                ['id' => $order['id']]
            );
        }

        return ['success' => true];
    }

    public function actionAddNorm($id, $parameterId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        $norm = new ParameterNorm();
        $norm->parameter_id = $parameter->id;

        if ($norm->load(Yii::$app->request->post())) {
            // Handle multiple thresholds
            if ($norm->type === 'multiple_thresholds') {
                $thresholdValues = Yii::$app->request->post('threshold_value', []);
                $thresholdLabels = Yii::$app->request->post('threshold_label', []);
                $thresholdNormal = Yii::$app->request->post('threshold_normal', []);
                $thresholdTypes = Yii::$app->request->post('threshold_type', []);

                $thresholds = [];
                for ($i = 0; $i < count($thresholdValues); $i++) {
                    if (!empty($thresholdValues[$i])) {
                        $thresholds[] = [
                            'value' => (float) $thresholdValues[$i],
                            'label' => $thresholdLabels[$i] ?? '',
                            'is_normal' => (bool) ($thresholdNormal[$i] ?? false),
                            'type' => $thresholdTypes[$i] ?? null,
                        ];
                    }
                }

                // Sort by value
                usort($thresholds, function ($a, $b) {
                    return $a['value'] <=> $b['value'];
                });

                $norm->thresholds_config = json_encode($thresholds);
            }

            if ($norm->save()) {
                Yii::$app->session->setFlash('success', 'Norma została dodana.');
                return $this->redirect(['view', 'id' => $template->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Błąd zapisywania normy: ' . implode(', ', $norm->getFirstErrors()));
            }
        }

        return $this->render('add-norm', [
            'template' => $template,
            'parameter' => $parameter,
            'norm' => $norm,
        ]);
    }

    public function actionUpdateNorm($id, $parameterId, $normId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);
        $norm = ParameterNorm::findOne($normId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        if (!$norm || $norm->parameter_id != $parameter->id) {
            throw new NotFoundHttpException('Norma nie została znaleziona.');
        }

        if ($norm->load(Yii::$app->request->post())) {
            // Handle multiple thresholds
            if ($norm->type === 'multiple_thresholds') {
                $thresholdValues = Yii::$app->request->post('threshold_value', []);
                $thresholdLabels = Yii::$app->request->post('threshold_label', []);
                $thresholdNormal = Yii::$app->request->post('threshold_normal', []);
                $thresholdTypes = Yii::$app->request->post('threshold_type', []);

                $thresholds = [];
                for ($i = 0; $i < count($thresholdValues); $i++) {
                    if (!empty($thresholdValues[$i])) {
                        $thresholds[] = [
                            'value' => (float) $thresholdValues[$i],
                            'label' => $thresholdLabels[$i] ?? '',
                            'is_normal' => (bool) ($thresholdNormal[$i] ?? false),
                            'type' => $thresholdTypes[$i] ?? null,
                        ];
                    }
                }

                // Sort by value
                usort($thresholds, function ($a, $b) {
                    return $a['value'] <=> $b['value'];
                });

                $norm->thresholds_config = json_encode($thresholds);
            }

            if ($norm->save()) {
                Yii::$app->session->setFlash('success', 'Norma została zaktualizowana.');
                return $this->redirect(['view', 'id' => $template->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Błąd zapisywania normy: ' . implode(', ', $norm->getFirstErrors()));
            }
        }

        return $this->render('update-norm', [
            'template' => $template,
            'parameter' => $parameter,
            'norm' => $norm,
        ]);
    }

    public function actionDeleteNorm($id, $parameterId, $normId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);
        $norm = ParameterNorm::findOne($normId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        if (!$norm || $norm->parameter_id != $parameter->id) {
            throw new NotFoundHttpException('Norma nie została znaleziona.');
        }

        $norm->delete();
        Yii::$app->session->setFlash('success', 'Norma została usunięta.');

        return $this->redirect(['view', 'id' => $template->id]);
    }

    /**
     * Pobieranie norm parametru dla modalu
     */
    public function actionGetParameterNorms($id, $parameterId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        $norms = $parameter->norms;

        return $this->renderPartial('_norms_modal_content', [
            'template' => $template,
            'parameter' => $parameter,
            'norms' => $norms,
        ]);
    }

    /**
     * Włączanie ostrzeżeń dla normy (AJAX)
     */
    public function actionEnableNormWarnings() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $templateId = Yii::$app->request->post('id');
        $parameterId = Yii::$app->request->post('parameterId');
        $normId = Yii::$app->request->post('normId');

        $template = $this->findModel($templateId);
        $parameter = TestParameter::findOne($parameterId);
        $norm = ParameterNorm::findOne($normId);

        if (!$parameter || $parameter->test_template_id != $templateId) {
            return ['success' => false, 'message' => 'Parametr nie został znaleziony.'];
        }

        if (!$norm || $norm->parameter_id != $parameterId) {
            return ['success' => false, 'message' => 'Norma nie została znaleziona.'];
        }

        $norm->warning_enabled = true;
        
        // Ustaw domyślne marginesy jeśli nie są ustawione
        if (!$norm->warning_margin_percent) {
            $norm->warning_margin_percent = 10;
        }
        if (!$norm->caution_margin_percent) {
            $norm->caution_margin_percent = 5;
        }

        if ($norm->save()) {
            return ['success' => true, 'message' => 'Ostrzeżenia zostały włączone.'];
        } else {
            return ['success' => false, 'message' => 'Błąd podczas zapisywania: ' . implode(', ', $norm->getFirstErrors())];
        }
    }

    /**
     * Usuwanie normy (AJAX) - nowa implementacja
     */
    public function actionDeleteNormAjax() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $templateId = Yii::$app->request->post('id');
        $parameterId = Yii::$app->request->post('parameterId');
        $normId = Yii::$app->request->post('normId');

        $template = $this->findModel($templateId);
        $parameter = TestParameter::findOne($parameterId);
        $norm = ParameterNorm::findOne($normId);

        if (!$parameter || $parameter->test_template_id != $templateId) {
            return ['success' => false, 'message' => 'Parametr nie został znaleziony.'];
        }

        if (!$norm || $norm->parameter_id != $parameterId) {
            return ['success' => false, 'message' => 'Norma nie została znaleziona.'];
        }

        if ($norm->delete()) {
            return ['success' => true, 'message' => 'Norma została usunięta.'];
        } else {
            return ['success' => false, 'message' => 'Błąd podczas usuwania normy.'];
        }
    }

    /**
     * Szybkie włączenie ostrzeżeń dla pojedynczego parametru
     */
    public function actionQuickEnableWarning($id, $parameterId) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $id) {
            return ['success' => false, 'message' => 'Parametr nie został znaleziony.'];
        }

        $norms = $parameter->norms;
        if (empty($norms)) {
            return ['success' => false, 'message' => 'Parametr nie ma skonfigurowanych norm.'];
        }

        $updated = 0;
        foreach ($norms as $norm) {
            if (!$norm->warning_enabled) {
                $norm->warning_enabled = true;
                // Ustaw domyślne marginesy jeśli nie są ustawione
                if (!$norm->warning_margin_percent) {
                    $norm->warning_margin_percent = 10; // 10% domyślny margines ostrzeżenia
                }
                if (!$norm->caution_margin_percent) {
                    $norm->caution_margin_percent = 5; // 5% domyślny margines uwagi
                }
                
                if ($norm->save()) {
                    $updated++;
                }
            }
        }

        if ($updated > 0) {
            return ['success' => true, 'message' => "Włączono ostrzeżenia dla $updated norm."];
        } else {
            return ['success' => false, 'message' => 'Ostrzeżenia były już włączone.'];
        }
    }

    /**
     * Konfiguracja ostrzeżeń dla szablonu
     */
    public function actionConfigureWarnings($id) {
        $model = $this->findModel($id);
        $thresholdManager = new MedicalThresholdManager();

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            if (isset($postData['auto_setup'])) {
                // Automatyczna konfiguracja
                $options = [
                    'preset' => $postData['preset'] ?? 'standard',
                ];

                if ($model->setupWarningsForAllParameters($options)) {
                    Yii::$app->session->setFlash('success', 'Ostrzeżenia zostały automatycznie skonfigurowane.');
                } else {
                    Yii::$app->session->setFlash('error', 'Wystąpił błąd podczas konfiguracji ostrzeżeń.');
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $stats = $model->getWarningsStatistics();
        $parametersWithoutWarnings = $model->getParametersWithoutWarnings();
        $presets = $thresholdManager->getPresetOptions();

        return $this->render('configure-warnings', [
            'model' => $model,
            'stats' => $stats,
            'parametersWithoutWarnings' => $parametersWithoutWarnings,
            'presets' => $presets,
        ]);
    }

    /**
     * Szybka konfiguracja ostrzeżeń dla pojedynczego parametru
     */
    public function actionQuickSetupWarning($id, $parameterId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            $norm = $parameter->primaryNorm;

            if ($norm) {
                $thresholdManager = new MedicalThresholdManager();
                $margins = $thresholdManager->getParameterMargins(
                    $parameter->name,
                    $postData['patient_age'] ?? null,
                    $postData['conditions'] ?? []
                );

                $norm->warning_enabled = true;
                $norm->warning_margin_percent = $margins['warning_percent'];
                $norm->caution_margin_percent = $margins['caution_percent'];

                if ($norm->save()) {
                    Yii::$app->session->setFlash('success', 'Ostrzeżenia zostały skonfigurowane dla parametru.');
                } else {
                    Yii::$app->session->setFlash('error', 'Błąd podczas konfiguracji ostrzeżeń.');
                }
            }

            return $this->redirect(['view', 'id' => $template->id]);
        }

        $thresholdManager = new MedicalThresholdManager();
        $suggestedMargins = $thresholdManager->getParameterMargins($parameter->name);

        return $this->render('quick-warning-setup', [
            'template' => $template,
            'parameter' => $parameter,
            'suggestedMargins' => $suggestedMargins,
        ]);
    }

    /**
     * Raport ostrzeżeń dla szablonu
     */
    public function actionWarningsReport($id) {
        $template = $this->findModel($id);
        
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        if (!$dateFrom) {
            $dateFrom = date('Y-m-01'); // Pierwszy dzień bieżącego miesiąca
        }
        if (!$dateTo) {
            $dateTo = date('Y-m-d'); // Dzisiaj
        }

        $results = $template->getResults()
            ->where(['between', 'test_date', $dateFrom, $dateTo])
            ->with(['resultValues.parameter', 'resultValues.norm'])
            ->orderBy('test_date DESC')
            ->all();

        $warningsStats = [
            'total_results' => count($results),
            'results_with_warnings' => 0,
            'total_abnormal_values' => 0,
            'total_warning_values' => 0,
            'total_caution_values' => 0,
            'parameter_stats' => []
        ];

        foreach ($results as $result) {
            $hasWarnings = false;
            
            foreach ($result->resultValues as $value) {
                $paramName = $value->parameter->name;
                
                if (!isset($warningsStats['parameter_stats'][$paramName])) {
                    $warningsStats['parameter_stats'][$paramName] = [
                        'total' => 0,
                        'abnormal' => 0,
                        'warning' => 0,
                        'caution' => 0,
                        'optimal' => 0
                    ];
                }

                $warningsStats['parameter_stats'][$paramName]['total']++;

                if ($value->is_abnormal) {
                    $warningsStats['total_abnormal_values']++;
                    $warningsStats['parameter_stats'][$paramName]['abnormal']++;
                    $hasWarnings = true;
                } elseif ($value->warning_level === 'warning') {
                    $warningsStats['total_warning_values']++;
                    $warningsStats['parameter_stats'][$paramName]['warning']++;
                    $hasWarnings = true;
                } elseif ($value->warning_level === 'caution') {
                    $warningsStats['total_caution_values']++;
                    $warningsStats['parameter_stats'][$paramName]['caution']++;
                    $hasWarnings = true;
                } else {
                    $warningsStats['parameter_stats'][$paramName]['optimal']++;
                }
            }

            if ($hasWarnings) {
                $warningsStats['results_with_warnings']++;
            }
        }

        return $this->render('warnings-report', [
            'model' => $template,
            'results' => $results,
            'warningsStats' => $warningsStats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }

    /**
     * Batch konfiguracja ostrzeżeń dla wielu szablonów
     */
    public function actionBatchConfigureWarnings() {
        if (Yii::$app->request->isPost) {
            $templateIds = Yii::$app->request->post('template_ids', []);
            $preset = Yii::$app->request->post('preset', 'standard');

            $success = 0;
            $failed = 0;

            foreach ($templateIds as $templateId) {
                $template = TestTemplate::findOne($templateId);
                if ($template) {
                    $options = [
                        'preset' => $preset,
                    ];

                    if ($template->setupWarningsForAllParameters($options)) {
                        $success++;
                    } else {
                        $failed++;
                    }
                }
            }

            Yii::$app->session->setFlash('success',
                "Konfiguracja batch zakończona. Skonfigurowano: $success, błędy: $failed szablonów.");

            return $this->redirect(['index']);
        }

        $templates = TestTemplate::find()->orderBy('name')->all();
        $thresholdManager = new MedicalThresholdManager();
        $presets = $thresholdManager->getPresetOptions();

        return $this->render('batch-configure-warnings', [
            'templates' => $templates,
            'presets' => $presets
        ]);
    }

    protected function findModel($id) {
        if (($model = TestTemplate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Szablon badania nie został znaleziony.');
    }
}