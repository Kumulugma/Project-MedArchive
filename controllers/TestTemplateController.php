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

        if ($this->request->isPost && $parameter->load($this->request->post())) {
            if ($parameter->save()) {
                Yii::$app->session->setFlash('success', 'Parametr został dodany.');
                
                if ($this->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'Parametr został dodany.'];
                }
                
                return $this->redirect(['view', 'id' => $template->id]);
            } else {
                $errors = implode(', ', $parameter->getFirstErrors());
                Yii::$app->session->setFlash('error', 'Błąd zapisywania parametru: ' . $errors);
                
                if ($this->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => false, 'message' => 'Błąd zapisywania parametru: ' . $errors];
                }
            }
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

        if ($this->request->isPost && $parameter->load($this->request->post())) {
            if ($parameter->save()) {
                Yii::$app->session->setFlash('success', 'Parametr został zaktualizowany.');
                
                if ($this->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'Parametr został zaktualizowany.'];
                }
                
                return $this->redirect(['view', 'id' => $template->id]);
            } else {
                $errors = implode(', ', $parameter->getFirstErrors());
                Yii::$app->session->setFlash('error', 'Błąd zapisywania parametru: ' . $errors);
                
                if ($this->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => false, 'message' => 'Błąd zapisywania parametru: ' . $errors];
                }
            }
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

        if ($parameter->deleteWithNorms()) {
            Yii::$app->session->setFlash('success', 'Parametr został usunięty wraz z normami.');
        } else {
            Yii::$app->session->setFlash('error', 'Nie można usunąć parametru. Sprawdź czy nie ma wprowadzonych wyników.');
        }

        return $this->redirect(['view', 'id' => $template->id]);
    }

    public function actionConfigureWarnings($id) {
        $template = $this->findModel($id);
        
        // Znajdź parametry bez skonfigurowanych ostrzeżeń
        $parametersWithoutWarnings = $template->getParametersWithoutWarnings();
        
        return $this->render('configure-warnings', [
            'model' => $template,
            'parametersWithoutWarnings' => $parametersWithoutWarnings,
        ]);
    }

    /**
     * OPERACJE NA NORMACH - POPRAWIONE
     */

    public function actionAddNorm($id, $parameterId) {
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $template->id) {
            throw new NotFoundHttpException('Parametr nie został znaleziony.');
        }

        $norm = new ParameterNorm();
        $norm->parameter_id = $parameter->id;

        if ($this->request->isPost && $norm->load($this->request->post())) {
            // Obsługa wielu progów
            if ($norm->type === 'multiple_thresholds') {
                $thresholdValues = $this->request->post('threshold_value', []);
                $thresholdLabels = $this->request->post('threshold_label', []);
                $thresholdNormal = $this->request->post('threshold_normal', []);
                $thresholdTypes = $this->request->post('threshold_type', []);

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

                // Sortuj według wartości
                usort($thresholds, function ($a, $b) {
                    return $a['value'] <=> $b['value'];
                });

                $norm->thresholds_config = json_encode($thresholds);
            }

            if ($norm->save()) {
                Yii::$app->session->setFlash('success', 'Norma została dodana.');
                
                // Sprawdź czy to żądanie AJAX
                if ($this->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'Norma została dodana.'];
                }
                
                return $this->redirect(['view', 'id' => $template->id]);
            } else {
                $errors = implode(', ', $norm->getFirstErrors());
                Yii::$app->session->setFlash('error', 'Błąd zapisywania normy: ' . $errors);
                
                if ($this->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => false, 'message' => 'Błąd zapisywania normy: ' . $errors];
                }
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

    // DEBUG - sprawdź czy to POST request
    if ($this->request->isPost) {
        echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
        echo "=== DEBUG ZAPISYWANIA NORMY ===\n";
        echo "POST Data otrzymane:\n";
        print_r($this->request->post());
        echo "\nAktualne wartości normy przed load():\n";
        echo "ID: " . $norm->id . "\n";
        echo "Nazwa: " . $norm->name . "\n";
        echo "Typ: " . $norm->type . "\n";
        echo "Threshold value: " . $norm->threshold_value . "\n";
        echo "Threshold direction: " . $norm->threshold_direction . "\n";
        echo "Warning enabled: " . ($norm->warning_enabled ? 'TRUE' : 'FALSE') . "\n";
        echo "</pre>";
    }

    if ($this->request->isPost && $norm->load($this->request->post())) {
        echo "<pre style='background: #e8f5e8; padding: 10px; border: 1px solid #4CAF50;'>";
        echo "=== PO LOAD() ===\n";
        echo "Nowe wartości normy po load():\n";
        echo "Nazwa: " . $norm->name . "\n";
        echo "Typ: " . $norm->type . "\n";
        echo "Threshold value: " . $norm->threshold_value . "\n";
        echo "Threshold direction: " . $norm->threshold_direction . "\n";
        echo "Warning enabled: " . ($norm->warning_enabled ? 'TRUE' : 'FALSE') . "\n";
        echo "Warning margin %: " . $norm->warning_margin_percent . "\n";
        echo "Caution margin %: " . $norm->caution_margin_percent . "\n";
        
        echo "\nValidation before save:\n";
        if ($norm->validate()) {
            echo "Validation: SUCCESS\n";
        } else {
            echo "Validation: FAILED\n";
            echo "Errors: " . json_encode($norm->getErrors(), JSON_PRETTY_PRINT) . "\n";
        }
        echo "</pre>";
        
        // Obsługa wielu progów (pozostaw istniejący kod)
        if ($norm->type === 'multiple_thresholds') {
            // ... kod dla multiple_thresholds
        }

        if ($norm->save()) {
            echo "<pre style='background: #d4edda; padding: 10px; border: 1px solid #28a745;'>";
            echo "=== SAVE SUCCESS ===\n";
            echo "Norma została zapisana pomyślnie!\n";
            echo "ID w bazie: " . $norm->id . "\n";
            echo "</pre>";
            
            Yii::$app->session->setFlash('success', 'Norma została zaktualizowana.');
            
            if ($this->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'Norma została zaktualizowana.'];
            }
            
            return $this->redirect(['view', 'id' => $template->id]);
        } else {
            echo "<pre style='background: #f8d7da; padding: 10px; border: 1px solid #dc3545;'>";
            echo "=== SAVE FAILED ===\n";
            echo "Błędy walidacji:\n";
            print_r($norm->getErrors());
            echo "Attributes:\n";
            print_r($norm->getAttributes());
            echo "</pre>";
            
            $errors = implode(', ', $norm->getFirstErrors());
            Yii::$app->session->setFlash('error', 'Błąd zapisywania normy: ' . $errors);
            
            if ($this->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Błąd zapisywania normy: ' . $errors];
            }
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

        if ($norm->delete()) {
            Yii::$app->session->setFlash('success', 'Norma została usunięta.');
        } else {
            Yii::$app->session->setFlash('error', 'Błąd podczas usuwania normy.');
        }

        return $this->redirect(['view', 'id' => $template->id]);
    }

    /**
     * AJAX ENDPOINTS - POPRAWIONE
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

    public function actionDeleteNormAjax() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->request->isPost) {
            return ['success' => false, 'message' => 'Nieprawidłowa metoda żądania.'];
        }
        
        $templateId = $this->request->post('id');
        $parameterId = $this->request->post('parameterId');
        $normId = $this->request->post('normId');

        // Walidacja parametrów
        if (!$templateId || !$parameterId || !$normId) {
            return ['success' => false, 'message' => 'Brakuje wymaganych parametrów.'];
        }

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

    public function actionEnableNormWarnings() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->request->isPost) {
            return ['success' => false, 'message' => 'Nieprawidłowa metoda żądania.'];
        }
        
        $templateId = $this->request->post('id');
        $parameterId = $this->request->post('parameterId');
        $normId = $this->request->post('normId');

        // Walidacja parametrów
        if (!$templateId || !$parameterId || !$normId) {
            return ['success' => false, 'message' => 'Brakuje wymaganych parametrów.'];
        }

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

    public function actionDisableNormWarnings() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->request->isPost) {
            return ['success' => false, 'message' => 'Nieprawidłowa metoda żądania.'];
        }
        
        $templateId = $this->request->post('id');
        $parameterId = $this->request->post('parameterId');
        $normId = $this->request->post('normId');

        // Walidacja parametrów
        if (!$templateId || !$parameterId || !$normId) {
            return ['success' => false, 'message' => 'Brakuje wymaganych parametrów.'];
        }

        $template = $this->findModel($templateId);
        $parameter = TestParameter::findOne($parameterId);
        $norm = ParameterNorm::findOne($normId);

        if (!$parameter || $parameter->test_template_id != $templateId) {
            return ['success' => false, 'message' => 'Parametr nie został znaleziony.'];
        }

        if (!$norm || $norm->parameter_id != $parameterId) {
            return ['success' => false, 'message' => 'Norma nie została znaleziona.'];
        }

        $norm->warning_enabled = false;

        if ($norm->save()) {
            return ['success' => true, 'message' => 'Ostrzeżenia zostały wyłączone.'];
        } else {
            return ['success' => false, 'message' => 'Błąd podczas zapisywania: ' . implode(', ', $norm->getFirstErrors())];
        }
    }

    public function actionQuickEnableWarning($id, $parameterId) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $template = $this->findModel($id);
        $parameter = TestParameter::findOne($parameterId);

        if (!$parameter || $parameter->test_template_id != $id) {
            return ['success' => false, 'message' => 'Parametr nie został znaleziony.'];
        }

        $primaryNorm = $parameter->primaryNorm;
        if (!$primaryNorm) {
            return ['success' => false, 'message' => 'Parametr nie ma skonfigurowanej podstawowej normy.'];
        }

        $primaryNorm->warning_enabled = true;
        
        // Ustaw domyślne marginesy jeśli nie są ustawione
        if (!$primaryNorm->warning_margin_percent) {
            $primaryNorm->warning_margin_percent = 10;
        }
        if (!$primaryNorm->caution_margin_percent) {
            $primaryNorm->caution_margin_percent = 5;
        }

        if ($primaryNorm->save()) {
            return ['success' => true, 'message' => 'Ostrzeżenia zostały włączone dla parametru.'];
        } else {
            return ['success' => false, 'message' => 'Błąd podczas zapisywania: ' . implode(', ', $primaryNorm->getFirstErrors())];
        }
    }

    /**
     * Bulk operations
     */
    public function actionBulkEnableWarnings($id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $template = $this->findModel($id);
        $parameters = $template->parameters;
        
        $enabledCount = 0;
        $errors = [];
        
        foreach ($parameters as $parameter) {
            $primaryNorm = $parameter->primaryNorm;
            if ($primaryNorm && !$primaryNorm->warning_enabled) {
                $primaryNorm->warning_enabled = true;
                
                // Ustaw domyślne marginesy
                if (!$primaryNorm->warning_margin_percent) {
                    $primaryNorm->warning_margin_percent = 10;
                }
                if (!$primaryNorm->caution_margin_percent) {
                    $primaryNorm->caution_margin_percent = 5;
                }
                
                if ($primaryNorm->save()) {
                    $enabledCount++;
                } else {
                    $errors[] = "Błąd dla parametru {$parameter->name}: " . implode(', ', $primaryNorm->getFirstErrors());
                }
            }
        }
        
        if ($enabledCount > 0) {
            $message = "Włączono ostrzeżenia dla {$enabledCount} parametrów.";
            if (!empty($errors)) {
                $message .= ' Błędy: ' . implode('; ', $errors);
            }
            return ['success' => true, 'message' => $message];
        } else {
            return ['success' => false, 'message' => 'Brak parametrów do aktywacji lub wystąpiły błędy: ' . implode('; ', $errors)];
        }
    }

    protected function findModel($id) {
        if (($model = TestTemplate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Szablon badania nie został znaleziony.');
    }
}