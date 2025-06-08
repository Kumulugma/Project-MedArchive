<?php
// components/MedicalThresholdManager.php

namespace app\components;

use Yii;

/**
 * Klasa zarządzająca progami ostrzeżeń dla parametrów medycznych
 * Obsługuje automatyczne ustawianie marginesów na podstawie typu badania,
 * wieku pacjenta i współistniejących schorzeń
 */
class MedicalThresholdManager
{
    private $config;
    
    public function __construct()
    {
        $this->config = $this->getDefaultConfig();
    }
    
    /**
     * Pobiera marginesy dla konkretnego parametru
     * 
     * @param string $parameterName Nazwa parametru
     * @param int|null $patientAge Wiek pacjenta
     * @param array $conditions Lista schorzeń pacjenta
     * @return array Marginesy ostrzeżeń
     */
    public function getParameterMargins($parameterName, $patientAge = null, $conditions = [])
    {
        $parameterKey = $this->normalizeParameterName($parameterName);
        
        // Pobierz podstawowe marginesy
        $margins = $this->config['default_margins'][$parameterKey] 
                  ?? $this->config['default_margins']['default'];
        
        // Zastosuj korekty wiekowe
        if ($patientAge !== null) {
            $ageAdjustment = $this->getAgeAdjustment($patientAge);
            $margins['warning_percent'] *= $ageAdjustment['warning_multiplier'];
            $margins['caution_percent'] *= $ageAdjustment['caution_multiplier'];
        }
        
        // Zastosuj korekty dla schorzeń
        foreach ($conditions as $condition) {
            if (isset($this->config['condition_adjustments'][$condition])) {
                $conditionAdjustment = $this->config['condition_adjustments'][$condition];
                if (in_array($parameterKey, $conditionAdjustment['parameters'])) {
                    $margins['warning_percent'] *= $conditionAdjustment['warning_multiplier'];
                    $margins['caution_percent'] *= $conditionAdjustment['caution_multiplier'];
                }
            }
        }
        
        return $margins;
    }
    
    /**
     * Normalizuje nazwę parametru do klucza konfiguracji
     */
    private function normalizeParameterName($name)
    {
        $mappings = [
            // Polskie nazwy -> klucze angielskie
            'glukoza' => 'glucose',
            'glukoza na czczo' => 'glucose',
            'cholesterol całkowity' => 'cholesterol',
            'cholesterol' => 'cholesterol',
            'triglicerydy' => 'triglycerides',
            'ALT' => 'alt_ast',
            'AST' => 'alt_ast',
            'ALAT' => 'alt_ast',
            'ASPAT' => 'alt_ast',
            'kreatynina' => 'creatinine',
            'mocznik' => 'urea',
            'sód' => 'sodium',
            'potas' => 'potassium',
            'chlorki' => 'chloride',
            'hemoglobina' => 'hemoglobin',
            'hematokryt' => 'hematocrit',
            'leukocyty' => 'wbc',
            'płytki' => 'platelets',
            'TSH' => 'tsh',
            'fT4' => 'ft4',
            'fT3' => 'ft3',
            'białko całkowite' => 'protein',
            'albumina' => 'albumin',
            'CRP' => 'crp',
            'troponina' => 'troponin',
            'BNP' => 'bnp',
            'bilirubina' => 'bilirubin'
        ];
        
        $normalized = strtolower(trim($name));
        return $mappings[$normalized] ?? $this->createKeyFromName($normalized);
    }
    
    /**
     * Tworzy klucz z nazwy parametru jeśli nie ma mapowania
     */
    private function createKeyFromName($name)
    {
        return str_replace([' ', '-', '(', ')', '[', ']'], '_', strtolower($name));
    }
    
    /**
     * Pobiera korekty wiekowe
     */
    private function getAgeAdjustment($age)
    {
        foreach ($this->config['age_adjustments'] as $group => $adjustment) {
            if ($age >= $adjustment['age_range'][0] && $age < $adjustment['age_range'][1]) {
                return $adjustment;
            }
        }
        
        return $this->config['age_adjustments']['adult']; // domyślnie
    }
    
    /**
     * Pobiera rekomendacje dla strefy
     */
    public function getZoneRecommendations($zone)
    {
        return $this->config['recommendations'][$zone] ?? [];
    }
    
    /**
     * Pobiera listę dostępnych predefiniowanych ustawień
     */
    public function getPresetOptions()
    {
        return [
            'conservative' => [
                'name' => 'Konserwatywne',
                'warning' => 15,
                'caution' => 8,
                'description' => 'Wąskie marginesy - wcześnie wykrywa problemy'
            ],
            'standard' => [
                'name' => 'Standardowe',
                'warning' => 10,
                'caution' => 5,
                'description' => 'Zalecane dla większości przypadków'
            ],
            'liberal' => [
                'name' => 'Liberalne',
                'warning' => 5,
                'caution' => 3,
                'description' => 'Szerokie marginesy - mniej fałszywych alarmów'
            ]
        ];
    }
    
    /**
     * Określa kategorię parametru na podstawie nazwy
     */
    public function getParameterCategory($parameterName)
    {
        $parameterKey = $this->normalizeParameterName($parameterName);
        
        foreach ($this->config['parameter_categories'] as $category => $data) {
            if (in_array($parameterKey, $data['parameters'])) {
                return $category;
            }
        }
        
        return 'stable'; // domyślna kategoria
    }
    
    /**
     * Generuje rekomendację na podstawie poziomu ostrzeżenia
     */
    public function generateRecommendation($warningLevel, $parameterName = null)
    {
        $recommendations = [
            'none' => 'Wartość optymalna - kontynuuj obecny tryb życia.',
            'caution' => 'Zalecana kontrola za 3-6 miesięcy i obserwacja trendów.',
            'warning' => 'Rozważ kontrolę za 1-3 miesiące - wartość bliska granicy normy.',
            'critical' => 'Skonsultuj wynik z lekarzem - wartość poza normą.'
        ];
        
        $baseRecommendation = $recommendations[$warningLevel] ?? $recommendations['none'];
        
        // Dodaj specyficzne rekomendacje dla niektórych parametrów
        if ($parameterName && $warningLevel !== 'none') {
            $specificAdvice = $this->getParameterSpecificAdvice($parameterName, $warningLevel);
            if ($specificAdvice) {
                $baseRecommendation .= ' ' . $specificAdvice;
            }
        }
        
        return $baseRecommendation;
    }
    
    /**
     * Pobiera specyficzne porady dla konkretnych parametrów
     */
    private function getParameterSpecificAdvice($parameterName, $warningLevel)
    {
        $parameterKey = $this->normalizeParameterName($parameterName);
        
        $advice = [
            'glucose' => [
                'caution' => 'Monitoruj dietę i aktywność fizyczną.',
                'warning' => 'Rozważ badanie HbA1c i konsultację diabetologiczną.'
            ],
            'cholesterol' => [
                'caution' => 'Zwróć uwagę na dietę niskochodesterolową.',
                'warning' => 'Rozważ konsultację kardiologiczną i ocenę ryzyka sercowo-naczyniowego.'
            ],
            'alt_ast' => [
                'caution' => 'Unikaj alkoholu i sprawdź leki hepatotoksyczne.',
                'warning' => 'Zalecana konsultacja gastroenterologiczna.'
            ],
            'creatinine' => [
                'caution' => 'Monitoruj funkcje nerek i unikaj leków nefrotoksycznych.',
                'warning' => 'Zalecana konsultacja nefrologiczna i ocena GFR.'
            ]
        ];
        
        return $advice[$parameterKey][$warningLevel] ?? null;
    }
    
    /**
     * Konfiguracja domyślna - można przenieść do osobnego pliku config
     */
    private function getDefaultConfig()
    {
        return [
            'default_margins' => [
                // Biochemia podstawowa
                'glucose' => [
                    'warning_percent' => 8,
                    'caution_percent' => 4,
                    'description' => 'Glukoza - wrażliwy parametr'
                ],
                'cholesterol' => [
                    'warning_percent' => 12,
                    'caution_percent' => 6,
                    'description' => 'Cholesterol - umiarkowane marginesy'
                ],
                'triglycerides' => [
                    'warning_percent' => 15,
                    'caution_percent' => 8,
                    'description' => 'Triglicerydy - większe marginesy'
                ],
                
                // Funkcje wątroby
                'alt_ast' => [
                    'warning_percent' => 20,
                    'caution_percent' => 10,
                    'description' => 'Transaminazy - duża zmienność'
                ],
                'bilirubin' => [
                    'warning_percent' => 15,
                    'caution_percent' => 8,
                    'description' => 'Bilirubina'
                ],
                
                // Funkcje nerek
                'creatinine' => [
                    'warning_percent' => 12,
                    'caution_percent' => 6,
                    'description' => 'Kreatynina - ważny marker'
                ],
                'urea' => [
                    'warning_percent' => 18,
                    'caution_percent' => 9,
                    'description' => 'Mocznik'
                ],
                
                // Elektrolity
                'sodium' => [
                    'warning_percent' => 3,
                    'caution_percent' => 1.5,
                    'description' => 'Sód - wąski zakres'
                ],
                'potassium' => [
                    'warning_percent' => 5,
                    'caution_percent' => 2.5,
                    'description' => 'Potas - krytyczny'
                ],
                'chloride' => [
                    'warning_percent' => 4,
                    'caution_percent' => 2,
                    'description' => 'Chlorki'
                ],
                
                // Tarczyca
                'tsh' => [
                    'warning_percent' => 10,
                    'caution_percent' => 5,
                    'description' => 'TSH'
                ],
                'ft4' => [
                    'warning_percent' => 8,
                    'caution_percent' => 4,
                    'description' => 'fT4'
                ],
                'ft3' => [
                    'warning_percent' => 10,
                    'caution_percent' => 5,
                    'description' => 'fT3'
                ],
                
                // Morfologia
                'hemoglobin' => [
                    'warning_percent' => 8,
                    'caution_percent' => 4,
                    'description' => 'Hemoglobina'
                ],
                'hematocrit' => [
                    'warning_percent' => 8,
                    'caution_percent' => 4,
                    'description' => 'Hematokryt'
                ],
                'wbc' => [
                    'warning_percent' => 15,
                    'caution_percent' => 8,
                    'description' => 'Leukocyty'
                ],
                'platelets' => [
                    'warning_percent' => 12,
                    'caution_percent' => 6,
                    'description' => 'Płytki'
                ],
                
                // Domyślne
                'default' => [
                    'warning_percent' => 10,
                    'caution_percent' => 5,
                    'description' => 'Standardowe marginesy'
                ]
            ],
            
            'parameter_categories' => [
                'critical' => [
                    'parameters' => ['sodium', 'potassium', 'glucose', 'troponin'],
                    'default_warning' => 5,
                    'default_caution' => 2.5,
                ],
                'stable' => [
                    'parameters' => ['cholesterol', 'protein', 'albumin'],
                    'default_warning' => 10,
                    'default_caution' => 5,
                ],
                'variable' => [
                    'parameters' => ['triglycerides', 'wbc', 'crp', 'alt_ast'],
                    'default_warning' => 18,
                    'default_caution' => 9,
                ]
            ],
            
            'age_adjustments' => [
                'pediatric' => [
                    'age_range' => [0, 18],
                    'warning_multiplier' => 0.8,
                    'caution_multiplier' => 0.6,
                ],
                'adult' => [
                    'age_range' => [18, 65],
                    'warning_multiplier' => 1.0,
                    'caution_multiplier' => 1.0,
                ],
                'elderly' => [
                    'age_range' => [65, 120],
                    'warning_multiplier' => 1.2,
                    'caution_multiplier' => 1.4,
                ]
            ],
            
            'condition_adjustments' => [
                'diabetes' => [
                    'parameters' => ['glucose'],
                    'warning_multiplier' => 0.6,
                    'caution_multiplier' => 0.4,
                ],
                'hypertension' => [
                    'parameters' => ['sodium', 'potassium'],
                    'warning_multiplier' => 0.7,
                    'caution_multiplier' => 0.5,
                ],
                'kidney_disease' => [
                    'parameters' => ['creatinine', 'urea', 'potassium'],
                    'warning_multiplier' => 0.5,
                    'caution_multiplier' => 0.3,
                ],
                'liver_disease' => [
                    'parameters' => ['alt_ast', 'bilirubin', 'albumin'],
                    'warning_multiplier' => 0.8,
                    'caution_multiplier' => 0.6,
                ]
            ],
            
            'recommendations' => [
                'warning_zone' => [
                    'short_term' => 'Kontrola za 1-3 miesiące',
                    'monitoring' => 'Obserwacja trendów',
                    'lifestyle' => 'Rozważ modyfikację stylu życia',
                    'consultation' => 'Rozważ konsultację specjalistyczną'
                ],
                'caution_zone' => [
                    'short_term' => 'Kontrola za 3-6 miesięcy',
                    'monitoring' => 'Regularne monitorowanie',
                    'lifestyle' => 'Kontynuuj zdrowy tryb życia',
                    'education' => 'Edukacja o czynnikach ryzyka'
                ],
                'optimal_zone' => [
                    'maintenance' => 'Kontynuuj obecny tryb życia',
                    'routine' => 'Rutynowe kontrole',
                    'prevention' => 'Profilaktyka pierwotna'
                ]
            ]
        ];
    }
}