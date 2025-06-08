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
        // Dodany searchModel dla zgodności z widokiem
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

    public function actionAddNorm($id, $parameterId) {
        error_log("ADD NORM ACTION CALLED - Template ID: $id, Parameter ID: $parameterId");

        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        $norm = new ParameterNorm();
        $norm->parameter_id = $parameter->id;

        error_log("POST data: " . json_encode(Yii::$app->request->post()));

        if ($norm->load(Yii::$app->request->post())) {
            error_log("Norm loaded successfully");
            error_log("Norm data: " . json_encode($norm->attributes));

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
                error_log("Thresholds config: " . $norm->thresholds_config);
            }

            // Validate before save
            if ($norm->validate()) {
                error_log("Validation passed");
                if ($norm->save()) {
                    error_log("Norm saved successfully with ID: " . $norm->id);
                    Yii::$app->session->setFlash('success', 'Norma została dodana.');
                    return $this->redirect(['view', 'id' => $template->id]);
                } else {
                    error_log("Save failed");
                    Yii::$app->session->setFlash('error', 'Błąd zapisywania normy w bazie danych.');
                }
            } else {
                error_log("Validation failed");
                error_log("Validation errors: " . json_encode($norm->getErrors()));
                Yii::$app->session->setFlash('error', 'Błąd walidacji: ' . implode(', ', $norm->getFirstErrors()));
            }
        } else {
            error_log("Norm NOT loaded from POST");
        }

        return $this->render('add-norm', [
                    'template' => $template,
                    'parameter' => $parameter,
                    'norm' => $norm,
        ]);
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
                // Debug - wyświetl błędy walidacji
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

    protected function findModel($id) {
        if (($model = TestTemplate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Szablon badania nie został znaleziony.');
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
                    'patient_age' => $postData['patient_age'] ?? null,
                    'conditions' => $postData['conditions'] ?? []
                ];

                if ($model->setupWarningsForAllParameters($options)) {
                    Yii::$app->session->setFlash('success', 'Ostrzeżenia zostały automatycznie skonfigurowane.');
                } else {
                    Yii::$app->session->setFlash('error', 'Wystąpił błąd podczas konfiguracji ostrzeżeń.');
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }

            if (isset($postData['clone_from'])) {
                // Klonowanie z innego szablonu
                $sourceTemplateId = $postData['source_template_id'];
                if ($model->cloneWarningsFromTemplate($sourceTemplateId)) {
                    Yii::$app->session->setFlash('success', 'Konfiguracja ostrzeżeń została skopiowana.');
                } else {
                    Yii::$app->session->setFlash('error', 'Wystąpił błąd podczas kopiowania konfiguracji.');
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $stats = $model->getWarningsStatistics();
        $parametersWithoutWarnings = $model->getParametersWithoutWarnings();
        $presets = $thresholdManager->getPresetOptions();
        $otherTemplates = TestTemplate::find()
                ->where(['!=', 'id', $id])
                ->orderBy('name')
                ->all();

        return $this->render('configure-warnings', [
                    'model' => $model,
                    'stats' => $stats,
                    'parametersWithoutWarnings' => $parametersWithoutWarnings,
                    'presets' => $presets,
                    'otherTemplates' => $otherTemplates,
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
                    return $this->asJson(['success' => true, 'message' => 'Ostrzeżenia skonfigurowane']);
                }
            }

            return $this->asJson(['success' => false, 'message' => 'Błąd konfiguracji']);
        }

        return $this->renderAjax('_quick_warning_setup', [
                    'parameter' => $parameter,
                    'template' => $template
        ]);
    }

    /**
     * Szybkie włączenie ostrzeżeń dla parametru
     */
    public function actionQuickEnableWarning($id) {
        $template = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $parameterId = Yii::$app->request->post('parameterId');
            $parameter = TestParameter::findOne($parameterId);

            if (!$parameter || $parameter->test_template_id != $id) {
                return $this->asJson(['success' => false, 'message' => 'Parametr nie został znaleziony']);
            }

            $norm = $parameter->primaryNorm;
            if ($norm) {
                // Użyj MedicalThresholdManager do automatycznego ustawienia marginesów
                if ($norm->autoSetMargins()) {
                    if ($norm->save()) {
                        return $this->asJson(['success' => true, 'message' => 'Ostrzeżenia zostały włączone']);
                    }
                }
            }

            return $this->asJson(['success' => false, 'message' => 'Nie udało się włączyć ostrzeżeń']);
        }

        return $this->asJson(['success' => false, 'message' => 'Nieprawidłowe żądanie']);
    }

    /**
     * Pobiera sugerowane marginesy dla parametru na podstawie kontekstu
     */
    public function actionGetSuggestedMargins() {
        if (Yii::$app->request->isPost) {
            $parameterName = Yii::$app->request->post('parameterName');
            $age = Yii::$app->request->post('age');
            $conditions = Yii::$app->request->post('conditions', []);

            $thresholdManager = new \app\components\MedicalThresholdManager();
            $margins = $thresholdManager->getParameterMargins($parameterName, $age, $conditions);

            return $this->asJson([
                        'success' => true,
                        'margins' => $margins
            ]);
        }

        return $this->asJson(['success' => false, 'message' => 'Nieprawidłowe żądanie']);
    }

    /**
     * Eksport konfiguracji ostrzeżeń szablonu
     */
    public function actionExportWarningsConfig($id) {
        $template = $this->findModel($id);

        $config = [
            'template_name' => $template->name,
            'template_id' => $template->id,
            'export_date' => date('Y-m-d H:i:s'),
            'parameters' => []
        ];

        foreach ($template->parameters as $parameter) {
            if ($parameter->primaryNorm && $parameter->primaryNorm->warning_enabled) {
                $norm = $parameter->primaryNorm;
                $config['parameters'][] = [
                    'name' => $parameter->name,
                    'unit' => $parameter->unit,
                    'type' => $parameter->type,
                    'norm_type' => $norm->type,
                    'min_value' => $norm->min_value,
                    'max_value' => $norm->max_value,
                    'threshold_value' => $norm->threshold_value,
                    'threshold_direction' => $norm->threshold_direction,
                    'warning_enabled' => $norm->warning_enabled,
                    'warning_margin_percent' => $norm->warning_margin_percent,
                    'warning_margin_absolute' => $norm->warning_margin_absolute,
                    'caution_margin_percent' => $norm->caution_margin_percent,
                    'caution_margin_absolute' => $norm->caution_margin_absolute,
                    'optimal_min_value' => $norm->optimal_min_value,
                    'optimal_max_value' => $norm->optimal_max_value,
                ];
            }
        }

        $filename = "warnings_config_{$template->name}_" . date('Y-m-d') . ".json";

        Yii::$app->response->headers->set('Content-Type', 'application/json');
        Yii::$app->response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import konfiguracji ostrzeżeń
     */
    public function actionImportWarningsConfig($id) {
        $template = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $uploadedFile = \yii\web\UploadedFile::getInstanceByName('config_file');

            if ($uploadedFile && $uploadedFile->extension === 'json') {
                $configContent = file_get_contents($uploadedFile->tempName);
                $config = json_decode($configContent, true);

                if ($config && isset($config['parameters'])) {
                    $imported = 0;
                    $skipped = 0;

                    foreach ($config['parameters'] as $paramConfig) {
                        // Znajdź odpowiadający parametr w bieżącym szablonie
                        $parameter = null;
                        foreach ($template->parameters as $p) {
                            if ($p->name === $paramConfig['name']) {
                                $parameter = $p;
                                break;
                            }
                        }

                        if ($parameter && $parameter->primaryNorm) {
                            $norm = $parameter->primaryNorm;

                            // Importuj ustawienia ostrzeżeń
                            $norm->warning_enabled = $paramConfig['warning_enabled'] ?? true;
                            $norm->warning_margin_percent = $paramConfig['warning_margin_percent'] ?? null;
                            $norm->warning_margin_absolute = $paramConfig['warning_margin_absolute'] ?? null;
                            $norm->caution_margin_percent = $paramConfig['caution_margin_percent'] ?? null;
                            $norm->caution_margin_absolute = $paramConfig['caution_margin_absolute'] ?? null;
                            $norm->optimal_min_value = $paramConfig['optimal_min_value'] ?? null;
                            $norm->optimal_max_value = $paramConfig['optimal_max_value'] ?? null;

                            if ($norm->save()) {
                                $imported++;
                            } else {
                                $skipped++;
                            }
                        } else {
                            $skipped++;
                        }
                    }

                    Yii::$app->session->setFlash('success',
                            "Import zakończony. Zaimportowano: $imported, pominięto: $skipped parametrów.");
                } else {
                    Yii::$app->session->setFlash('error', 'Nieprawidłowy format pliku konfiguracji.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Proszę wybrać prawidłowy plik JSON.');
            }

            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('import-warnings-config', [
                    'model' => $template
        ]);
    }

    /**
     * Podgląd raportów ostrzeżeń dla szablonu
     */
    public function actionWarningsReport($id) {
        $template = $this->findModel($id);
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));

        // Pobierz wyniki z ostatnich 30 dni (lub z wybranego zakresu)
        $results = \app\models\TestResult::find()
                ->where(['test_template_id' => $id])
                ->andWhere(['between', 'test_date', $dateFrom, $dateTo])
                ->orderBy(['test_date' => SORT_DESC])
                ->all();

        // Przygotuj statystyki ostrzeżeń
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
            $patientAge = Yii::$app->request->post('patient_age');
            $conditions = Yii::$app->request->post('conditions', []);

            $success = 0;
            $failed = 0;

            foreach ($templateIds as $templateId) {
                $template = TestTemplate::findOne($templateId);
                if ($template) {
                    $options = [
                        'preset' => $preset,
                        'patient_age' => $patientAge,
                        'conditions' => $conditions
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
        $thresholdManager = new \app\components\MedicalThresholdManager();
        $presets = $thresholdManager->getPresetOptions();

        return $this->render('batch-configure-warnings', [
                    'templates' => $templates,
                    'presets' => $presets
        ]);
    }

}
