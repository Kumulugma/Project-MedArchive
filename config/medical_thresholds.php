<?php

return [
    'default_margins' => [
        // Biochemia podstawowa
        'glucose' => [
            'warning_percent' => 8,
            'caution_percent' => 4,
            'description' => 'Glukoza - wrażliwy parametr, małe marginesy'
        ],
        'cholesterol' => [
            'warning_percent' => 12,
            'caution_percent' => 6,
            'description' => 'Cholesterol - umiarkowane marginesy'
        ],
        'triglycerides' => [
            'warning_percent' => 15,
            'caution_percent' => 8,
            'description' => 'Triglicerydy - większe marginesy ze względu na zmienność'
        ],
        
        // Funkcje wątroby
        'alt_ast' => [
            'warning_percent' => 20,
            'caution_percent' => 10,
            'description' => 'Transaminazy - duża zmienność, większe marginesy'
        ],
        'bilirubin' => [
            'warning_percent' => 15,
            'caution_percent' => 8,
            'description' => 'Bilirubina - umiarkowane marginesy'
        ],
        
        // Funkcje nerek
        'creatinine' => [
            'warning_percent' => 12,
            'caution_percent' => 6,
            'description' => 'Kreatynina - ważny marker, średnie marginesy'
        ],
        'urea' => [
            'warning_percent' => 18,
            'caution_percent' => 9,
            'description' => 'Mocznik - większa zmienność'
        ],
        
        // Elektrolity
        'sodium' => [
            'warning_percent' => 3,
            'caution_percent' => 1.5,
            'description' => 'Sód - wąski zakres fizjologiczny'
        ],
        'potassium' => [
            'warning_percent' => 5,
            'caution_percent' => 2.5,
            'description' => 'Potas - krytyczny elektrolit'
        ],
        'chloride' => [
            'warning_percent' => 4,
            'caution_percent' => 2,
            'description' => 'Chlorki - wąski zakres'
        ],
        
        // Tarczyca
        'tsh' => [
            'warning_percent' => 10,
            'caution_percent' => 5,
            'description' => 'TSH - kluczowy marker tarczycy'
        ],
        'ft4' => [
            'warning_percent' => 8,
            'caution_percent' => 4,
            'description' => 'fT4 - precyzyjny marker'
        ],
        'ft3' => [
            'warning_percent' => 10,
            'caution_percent' => 5,
            'description' => 'fT3 - większa zmienność'
        ],
        
        // Morfologia
        'hemoglobin' => [
            'warning_percent' => 8,
            'caution_percent' => 4,
            'description' => 'Hemoglobina - ważny marker'
        ],
        'hematocrit' => [
            'warning_percent' => 8,
            'caution_percent' => 4,
            'description' => 'Hematokryt - skorelowany z Hb'
        ],
        'wbc' => [
            'warning_percent' => 15,
            'caution_percent' => 8,
            'description' => 'Leukocyty - duża zmienność'
        ],
        'platelets' => [
            'warning_percent' => 12,
            'caution_percent' => 6,
            'description' => 'Płytki - umiarkowana zmienność'
        ],
        
        // Białka i markery zapalne
        'protein' => [
            'warning_percent' => 10,
            'caution_percent' => 5,
            'description' => 'Białko całkowite'
        ],
        'albumin' => [
            'warning_percent' => 8,
            'caution_percent' => 4,
            'description' => 'Albumina - ważny marker odżywienia'
        ],
        'crp' => [
            'warning_percent' => 25,
            'caution_percent' => 15,
            'description' => 'CRP - bardzo zmienny marker'
        ],
        
        // Kardiologia
        'troponin' => [
            'warning_percent' => 5,
            'caution_percent' => 2,
            'description' => 'Troponiny - krytyczny marker'
        ],
        'bnp' => [
            'warning_percent' => 20,
            'caution_percent' => 10,
            'description' => 'BNP - duża zmienność'
        ],
        
        // Domyślne wartości
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
            'description' => 'Parametry krytyczne - małe marginesy'
        ],
        'stable' => [
            'parameters' => ['cholesterol', 'protein', 'albumin'],
            'default_warning' => 10,
            'default_caution' => 5,
            'description' => 'Parametry stabilne - standardowe marginesy'
        ],
        'variable' => [
            'parameters' => ['triglycerides', 'wbc', 'crp', 'alt_ast'],
            'default_warning' => 18,
            'default_caution' => 9,
            'description' => 'Parametry zmienne - większe marginesy'
        ]
    ],
    
    'age_adjustments' => [
        'pediatric' => [
            'age_range' => [0, 18],
            'warning_multiplier' => 0.8,
            'caution_multiplier' => 0.6,
            'description' => 'Dzieci - mniejsze marginesy'
        ],
        'adult' => [
            'age_range' => [18, 65],
            'warning_multiplier' => 1.0,
            'caution_multiplier' => 1.0,
            'description' => 'Dorośli - standardowe marginesy'
        ],
        'elderly' => [
            'age_range' => [65, 120],
            'warning_multiplier' => 1.2,
            'caution_multiplier' => 1.4,
            'description' => 'Seniorzy - większe marginesy'
        ]
    ],
    
    'condition_adjustments' => [
        'diabetes' => [
            'parameters' => ['glucose', 'hba1c'],
            'warning_multiplier' => 0.6,
            'caution_multiplier' => 0.4,
            'description' => 'Cukrzyca - ścisła kontrola'
        ],
        'hypertension' => [
            'parameters' => ['sodium', 'potassium'],
            'warning_multiplier' => 0.7,
            'caution_multiplier' => 0.5,
            'description' => 'Nadciśnienie - ściślejsza kontrola elektrolitów'
        ],
        'kidney_disease' => [
            'parameters' => ['creatinine', 'urea', 'potassium'],
            'warning_multiplier' => 0.5,
            'caution_multiplier' => 0.3,
            'description' => 'Choroby nerek - bardzo ścisła kontrola'
        ],
        'liver_disease' => [
            'parameters' => ['alt_ast', 'bilirubin', 'albumin'],
            'warning_multiplier' => 0.8,
            'caution_multiplier' => 0.6,
            'description' => 'Choroby wątroby - ściślejsza kontrola'
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
            'education' => 'Edukacja pacjenta o czynnikach ryzyka'
        ],
        'optimal_zone' => [
            'maintenance' => 'Kontynuuj obecny tryb życia',
            'routine' => 'Rutynowe kontrole zgodnie z wiekiem',
            'prevention' => 'Profilaktyka pierwotna'
        ]
    ]
];
