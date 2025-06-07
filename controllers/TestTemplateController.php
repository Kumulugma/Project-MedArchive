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

    public function actionAddNorm($id, $parameterId)
    {
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
                            'value' => (float)$thresholdValues[$i],
                            'label' => $thresholdLabels[$i] ?? '',
                            'is_normal' => (bool)($thresholdNormal[$i] ?? false),
                            'type' => $thresholdTypes[$i] ?? null,
                        ];
                    }
                }
                
                // Sort by value
                usort($thresholds, function($a, $b) {
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
}