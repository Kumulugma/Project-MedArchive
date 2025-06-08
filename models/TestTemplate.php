<?php

// models/TestTemplate.php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\components\MedicalThresholdManager;

class TestTemplate extends ActiveRecord {

    public static function tableName() {
        return '{{%test_templates}}';
    }

    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules() {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['status'], 'integer'],
        ];
    }

    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => 'Nazwa badania',
            'description' => 'Opis',
            'status' => 'Status',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }

    public function getParameters() {
        return $this->hasMany(TestParameter::class, ['test_template_id' => 'id'])->orderBy('order_index');
    }

    public function getResults() {
        return $this->hasMany(TestResult::class, ['test_template_id' => 'id']);
    }

    public function getQueueItems() {
        return $this->hasMany(TestQueue::class, ['test_template_id' => 'id']);
    }

    /**
     * Automatycznie konfiguruje ostrzeżenia dla wszystkich parametrów w szablonie
     * 
     * @param array $options Opcje konfiguracji
     * @return bool
     */
    public function setupWarningsForAllParameters($options = []) {
        $thresholdManager = new MedicalThresholdManager();
        $defaultPreset = $options['preset'] ?? 'standard';
        $patientAge = $options['patient_age'] ?? null;
        $conditions = $options['conditions'] ?? [];

        $success = true;

        foreach ($this->parameters as $parameter) {
            foreach ($parameter->norms as $norm) {
                if (!$norm->warning_enabled) {
                    $margins = $thresholdManager->getParameterMargins(
                            $parameter->name,
                            $patientAge,
                            $conditions
                    );

                    $norm->warning_enabled = true;
                    $norm->warning_margin_percent = $margins['warning_percent'];
                    $norm->caution_margin_percent = $margins['caution_percent'];

                    if (!$norm->save()) {
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Pobiera statystyki ostrzeżeń dla szablonu
     */
    public function getWarningsStatistics() {
        $stats = [
            'total_parameters' => 0,
            'warnings_enabled' => 0,
            'warnings_disabled' => 0,
            'critical_parameters' => 0,
            'coverage_percent' => 0
        ];

        // Załaduj parametry z relacjami, aby uniknąć problemów z lazy loading
        $parameters = $this->getParameters()->with('norms')->all();
        
        if (empty($parameters)) {
            return $stats;
        }

        $thresholdManager = new \app\components\MedicalThresholdManager();

        foreach ($parameters as $parameter) {
            $stats['total_parameters']++;

            $hasWarnings = false;
            $norms = $parameter->norms ?? [];
            
            foreach ($norms as $norm) {
                if ($norm->warning_enabled) {
                    $hasWarnings = true;
                    break;
                }
            }

            if ($hasWarnings) {
                $stats['warnings_enabled']++;
            } else {
                $stats['warnings_disabled']++;
            }

            // Sprawdź czy to parametr krytyczny (tylko jeśli MedicalThresholdManager istnieje)
            try {
                if (method_exists($thresholdManager, 'getParameterCategory') && 
                    $thresholdManager->getParameterCategory($parameter->name) === 'critical') {
                    $stats['critical_parameters']++;
                }
            } catch (\Exception $e) {
                // Jeśli wystąpi błąd z MedicalThresholdManager, po prostu kontynuuj
            }
        }

        // Oblicz procent pokrycia
        if ($stats['total_parameters'] > 0) {
            $stats['coverage_percent'] = (int) round(($stats['warnings_enabled'] / $stats['total_parameters']) * 100);
        }

        return $stats;
    }

    /**
     * Sprawdza czy szablon ma kompletną konfigurację ostrzeżeń
     */
    public function hasCompleteWarningsSetup() {
        $stats = $this->getWarningsStatistics();
        return $stats['coverage_percent'] >= 80; // 80% parametrów ma ostrzeżenia
    }

    /**
     * Pobiera parametry bez skonfigurowanych ostrzeżeń
     */
    public function getParametersWithoutWarnings() {
        $parametersWithoutWarnings = [];

        // Załaduj parametry z normami
        $parameters = $this->getParameters()->with('norms')->all();

        foreach ($parameters as $parameter) {
            $hasWarnings = false;
            $norms = $parameter->norms ?? [];
            
            foreach ($norms as $norm) {
                if ($norm->warning_enabled) {
                    $hasWarnings = true;
                    break;
                }
            }

            if (!$hasWarnings) {
                $parametersWithoutWarnings[] = $parameter;
            }
        }

        return $parametersWithoutWarnings;
    }

    /**
     * Klonuje konfigurację ostrzeżeń z innego szablonu
     */
    public function cloneWarningsFromTemplate($sourceTemplateId) {
        $sourceTemplate = self::findOne($sourceTemplateId);
        if (!$sourceTemplate) {
            return false;
        }

        $success = true;

        // Załaduj parametry z normami dla obu szablonów
        $targetParameters = $this->getParameters()->with('norms')->all();
        $sourceParameters = $sourceTemplate->getParameters()->with('norms')->all();

        foreach ($targetParameters as $targetParameter) {
            // Znajdź odpowiadający parametr w źródłowym szablonie
            $sourceParameter = null;
            foreach ($sourceParameters as $param) {
                if ($param->name === $targetParameter->name) {
                    $sourceParameter = $param;
                    break;
                }
            }

            if ($sourceParameter && $sourceParameter->primaryNorm) {
                $sourceNorm = $sourceParameter->primaryNorm;
                $targetNorm = $targetParameter->primaryNorm;

                if ($targetNorm && $sourceNorm->warning_enabled) {
                    $targetNorm->warning_enabled = $sourceNorm->warning_enabled;
                    $targetNorm->warning_margin_percent = $sourceNorm->warning_margin_percent;
                    $targetNorm->warning_margin_absolute = $sourceNorm->warning_margin_absolute;
                    $targetNorm->caution_margin_percent = $sourceNorm->caution_margin_percent;
                    $targetNorm->caution_margin_absolute = $sourceNorm->caution_margin_absolute;
                    $targetNorm->optimal_min_value = $sourceNorm->optimal_min_value;
                    $targetNorm->optimal_max_value = $sourceNorm->optimal_max_value;

                    if (!$targetNorm->save()) {
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }
}