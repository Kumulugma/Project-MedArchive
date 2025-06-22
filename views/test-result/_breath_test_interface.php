<?php
use yii\helpers\Html;

// Parsuj istniejące wartości jeśli są
$timePoints = [0, 30, 60, 120, 180];
$existingValues = [];

if ($existingValue) {
    $parts = explode(';', $existingValue);
    foreach ($timePoints as $index => $time) {
        $existingValues[$time] = $parts[$index] ?? '';
    }
}

$config = json_decode($norm->thresholds_config, true);
$maxIncrease = $config['max_increase'] ?? 12;
?>

<div class="breath-test-interface border rounded p-3 mt-2" style="background-color: #f8f9fa;">
    <h6 class="text-primary mb-3">
        <i class="fas fa-lungs"></i> <?= Html::encode($parameter->name) ?>
        <small class="text-muted">(Test oddechowy - pomiary w czasie)</small>
    </h6>
    
    <div class="alert alert-info py-2 mb-3">
        <small><strong>Interpretacja:</strong> Wzrost wodoru > <?= $maxIncrease ?> ppm względem baseline wskazuje na nietolerancję laktozy</small>
    </div>
    
    <div class="row">
        <?php foreach ($timePoints as $time): ?>
            <div class="col-md-2 col-6 mb-3">
                <label class="form-label small fw-bold">
                    <?= $time ?> min
                    <?php if ($time === 0): ?>
                        <span class="badge bg-info ms-1">Baseline</span>
                    <?php endif; ?>
                </label>
                <div class="input-group input-group-sm">
                    <?= Html::textInput("breath_test_{$parameter->id}[{$time}]", $existingValues[$time] ?? '', [
                        'class' => 'form-control breath-test-value',
                        'placeholder' => '0.0',
                        'data-time-point' => $time,
                        'data-parameter-id' => $parameter->id,
                        'style' => 'text-align: center;'
                    ]) ?>
                    <span class="input-group-text">ppm</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Ukryte pole z połączonymi wartościami -->
    <?= Html::hiddenInput("parameter_{$parameter->id}", $existingValue, [
        'id' => "combined_values_{$parameter->id}",
        'class' => 'combined-breath-values'
    ]) ?>
    
    <!-- Podgląd wyniku w czasie rzeczywistym -->
    <div class="mt-3">
        <div class="card">
            <div class="card-header py-2">
                <h6 class="mb-0">
                    <i class="fas fa-chart-line"></i> Podgląd interpretacji
                </h6>
            </div>
            <div class="card-body">
                <div id="breath_test_preview_{$parameter->id}" class="small">
                    <span class="text-muted">Wprowadź wartości aby zobaczyć interpretację...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Aktualizuj połączoną wartość gdy użytkownik wprowadza dane
    $('.breath-test-value[data-parameter-id="<?= $parameter->id ?>"]').on('input', function() {
        updateBreathTestValue(<?= $parameter->id ?>);
        updateBreathTestPreview(<?= $parameter->id ?>, <?= $maxIncrease ?>);
    });
    
    // Pierwsza aktualizacja jeśli są istniejące wartości
    updateBreathTestValue(<?= $parameter->id ?>);
    updateBreathTestPreview(<?= $parameter->id ?>, <?= $maxIncrease ?>);
});

function updateBreathTestValue(parameterId) {
    var values = [];
    var timePoints = [0, 30, 60, 120, 180];
    
    timePoints.forEach(function(time) {
        var input = $('input[name="breath_test_' + parameterId + '[' + time + ']"]');
        var value = input.val().trim();
        values.push(value || '0');
    });
    
    // Połącz wartości średnikiem
    var combinedValue = values.join(';');
    $('#combined_values_' + parameterId).val(combinedValue);
}

function updateBreathTestPreview(parameterId, maxIncrease) {
    var baseline = parseFloat($('input[name="breath_test_' + parameterId + '[0]"]').val()) || 0;
    var timePoints = [30, 60, 120, 180];
    var results = [];
    var hasAbnormal = false;
    
    if (baseline === 0) {
        $('#breath_test_preview_' + parameterId).html('<span class="text-muted">Wprowadź wartość baseline (0 min) aby zobaczyć interpretację...</span>');
        return;
    }
    
    timePoints.forEach(function(time) {
        var currentValue = parseFloat($('input[name="breath_test_' + parameterId + '[' + time + ']"]').val()) || 0;
        var increase = currentValue - baseline;
        var isNormal = increase <= maxIncrease;
        
        if (!isNormal && currentValue > 0) {
            hasAbnormal = true;
        }
        
        results.push({
            time: time,
            value: currentValue,
            increase: increase,
            isNormal: isNormal
        });
    });
    
    var html = '<div class="row">';
    html += '<div class="col-12 mb-2"><strong>Baseline:</strong> ' + baseline.toFixed(1) + ' ppm</div>';
    
    results.forEach(function(result) {
        if (result.value > 0) {
            var badgeClass = result.isNormal ? 'bg-success' : 'bg-danger';
            var increaseText = result.increase > 0 ? '+' + result.increase.toFixed(1) : result.increase.toFixed(1);
            
            html += '<div class="col-6 col-md-3 mb-2">';
            html += '<div class="d-flex flex-column">';
            html += '<small><strong>' + result.time + ' min:</strong> ' + result.value.toFixed(1) + ' ppm</small>';
            html += '<span class="badge ' + badgeClass + '">' + increaseText + ' ppm</span>';
            html += '</div>';
            html += '</div>';
        }
    });
    html += '</div>';
    
    var interpretation;
    if (hasAbnormal) {
        interpretation = '<div class="alert alert-danger py-2 mt-2 mb-0">';
        interpretation += '<small><i class="fas fa-exclamation-triangle"></i> ';
        interpretation += '<strong>Nietolerancja laktozy potwierdzona</strong> - wzrost H₂ > ' + maxIncrease + ' ppm</small>';
        interpretation += '</div>';
    } else {
        interpretation = '<div class="alert alert-success py-2 mt-2 mb-0">';
        interpretation += '<small><i class="fas fa-check-circle"></i> ';
        interpretation += '<strong>Brak nietolerancji laktozy</strong> - wszystkie wzrosty ≤ ' + maxIncrease + ' ppm</small>';
        interpretation += '</div>';
    }
    
    $('#breath_test_preview_' + parameterId).html(html + interpretation);
}
</script>