<?php
// views/test-result/_warning_zones.php

use yii\helpers\Html;

/** @var ParameterNorm $norm */
/** @var float $value */

if (!$norm->warning_enabled) {
    return;
}

$min = $norm->min_value;
$max = $norm->max_value;
$range = $max - $min;

// Oblicz marginesy
$warningMargin = $norm->getEffectiveWarningMargin($range);
$cautionMargin = $norm->getEffectiveCautionMargin($range);

// Oblicz strefy
$warningZoneMin = $min + $warningMargin;
$warningZoneMax = $max - $warningMargin;
$cautionZoneMin = $min + $cautionMargin;
$cautionZoneMax = $max - $cautionMargin;

// Określ w której strefie jest wartość
$currentZone = 'optimal';
$zoneClass = 'text-success';

if ($value < $min || $value > $max) {
    $currentZone = 'abnormal';
    $zoneClass = 'text-danger';
} elseif ($value <= $warningZoneMin || $value >= $warningZoneMax) {
    $currentZone = 'warning';
    $zoneClass = 'text-warning';
} elseif ($value <= $cautionZoneMin || $value >= $cautionZoneMax) {
    $currentZone = 'caution';
    $zoneClass = 'text-info';
}
?>

<div class="warning-zones mt-2">
    <div class="zones-bar" style="position: relative; height: 20px; background: linear-gradient(to right, 
        #dc3545 0%, #dc3545 <?= ($warningMargin / $range) * 100 ?>%, 
        #fd7e14 <?= ($warningMargin / $range) * 100 ?>%, #fd7e14 <?= ($cautionMargin / $range) * 100 ?>%, 
        #28a745 <?= ($cautionMargin / $range) * 100 ?>%, #28a745 <?= 100 - ($cautionMargin / $range) * 100 ?>%, 
        #fd7e14 <?= 100 - ($cautionMargin / $range) * 100 ?>%, #fd7e14 <?= 100 - ($warningMargin / $range) * 100 ?>%, 
        #dc3545 <?= 100 - ($warningMargin / $range) * 100 ?>%, #dc3545 100%); 
        border-radius: 10px; border: 1px solid #ddd;">
        
        <!-- Wskaźnik aktualnej wartości -->
        <?php 
        $position = (($value - $min) / $range) * 100;
        $position = max(0, min(100, $position)); // Ogranicz do 0-100%
        ?>
        <div class="value-indicator" style="position: absolute; top: -2px; left: <?= $position ?>%; transform: translateX(-50%); 
             width: 4px; height: 24px; background: #000; border-radius: 2px;"></div>
    </div>
    
    <div class="zones-legend mt-1" style="font-size: 0.7em;">
        <div class="d-flex justify-content-between">
            <span class="text-danger"><?= number_format($min, 2) ?></span>
            <span class="<?= $zoneClass ?>">
                <strong><?= number_format($value, 2) ?></strong>
                (<?= $this->getZoneLabel($currentZone) ?>)
            </span>
            <span class="text-danger"><?= number_format($max, 2) ?></span>
        </div>
    </div>
    
    <div class="zones-description mt-1">
        <small class="text-muted">
            <?php
            echo match($currentZone) {
                'abnormal' => '<i class="fas fa-times-circle text-danger"></i> Poza normą',
                'warning' => '<i class="fas fa-exclamation-triangle text-warning"></i> Strefa ostrzeżenia',
                'caution' => '<i class="fas fa-eye text-info"></i> Strefa uwagi',
                'optimal' => '<i class="fas fa-check-circle text-success"></i> Strefa optymalna',
                default => ''
            };
            ?>
        </small>
    </div>
</div>

<?php
// Helper function dla etykiet stref
if (!function_exists('getZoneLabel')) {
    function getZoneLabel($zone) {
        return match($zone) {
            'abnormal' => 'Nieprawidłowe',
            'warning' => 'Ostrzeżenie',
            'caution' => 'Uwaga',
            'optimal' => 'Optymalne',
            default => 'Nieznane'
        };
    }
}
?>