<?php
use yii\helpers\Html;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->title = 'Porównanie wyników: ' . $template->name;
$this->params['breadcrumbs'][] = ['label' => 'Wyniki badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Rejestracja Chart.js i pluginu Annotation
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', [
    'position' => \yii\web\View::POS_HEAD,
    'depends' => [\yii\web\JqueryAsset::class]
]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/1.4.0/chartjs-plugin-annotation.min.js', [
    'position' => \yii\web\View::POS_HEAD,
    'depends' => [\yii\web\JqueryAsset::class]
]);

// Dodaj CSRF meta tags
$this->registerMetaTag([
    'name' => 'csrf-param',
    'content' => Yii::$app->request->csrfParam,
]);
$this->registerMetaTag([
    'name' => 'csrf-token',
    'content' => Yii::$app->request->csrfToken,
]);
?>

<div class="test-result-compare">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-chart-line me-2"></i>
            <?= Html::encode($this->title) ?>
        </h1>
        <div class="btn-toolbar">
            <?= Html::button('<i class="fas fa-print"></i> Wydrukuj', [
                'class' => 'btn btn-outline-secondary',
                'id' => 'print-comparison',
                'title' => 'Wydrukuj porównanie'
            ]) ?>
        </div>
    </div>

    <?= Html::beginForm('', 'post', ['id' => 'compare-form']) ?>
    
    <div class="row">
        <!-- Dostępne wyniki -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>
                        Dostępne wyniki
                    </h5>
                    <small class="text-muted"><?= count($results) ?> wyników</small>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3 border-bottom pb-2">
                        <?= Html::checkbox('select_all', false, [
                            'class' => 'form-check-input select-all-results', 
                            'id' => 'select-all'
                        ]) ?>
                        <?= Html::label('Zaznacz wszystkie', 'select-all', [
                            'class' => 'form-check-label fw-bold'
                        ]) ?>
                    </div>
                    
                    <div class="results-list" style="max-height: 400px; overflow-y: auto; padding: 1rem;">
                        <?php foreach ($results as $result): ?>
                            <?php 
                            $status = $result->getDetailedStatus();
                            $isSelected = in_array($result->id, array_column($selectedResults, 'id'));
                            ?>
                            <div class="form-check mb-2 p-2 border rounded <?= $isSelected ? 'bg-light' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <?= Html::checkbox('selected_results[]', $isSelected, [
                                        'value' => $result->id,
                                        'class' => 'form-check-input result-checkbox me-2',
                                        'id' => 'result-' . $result->id
                                    ]) ?>
                                    
                                    <div class="flex-grow-1">
                                        <?= Html::label(
                                            '<strong>' . Yii::$app->formatter->asDate($result->test_date) . '</strong>',
                                            'result-' . $result->id,
                                            ['class' => 'form-check-label mb-1 d-block']
                                        ) ?>
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- Status badge -->
                                            <span class="badge <?= $status['badge_class'] ?> badge-sm">
                                                <i class="<?= $status['icon'] ?> me-1"></i>
                                                <?= $status['message'] ?>
                                                <?php if ($status['warning_count'] > 0 && $status['status'] !== 'abnormal'): ?>
                                                    (<?= $status['warning_count'] ?>)
                                                <?php endif; ?>
                                            </span>
                                            
                                            <!-- Liczba parametrów -->
                                            <small class="text-muted">
                                                <?= count($result->resultValues) ?> parametrów
                                            </small>
                                        </div>
                                        
                                        <?php if ($result->comment): ?>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-comment me-1"></i>
                                                <?= Html::encode(mb_substr($result->comment, 0, 50)) ?>
                                                <?= mb_strlen($result->comment) > 50 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Parametry do porównania -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Parametry do porównania
                    </h5>
                    <small class="text-muted"><?= count($template->parameters) ?> parametrów</small>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3 border-bottom pb-2">
                        <?= Html::checkbox('select_all_params', false, [
                            'class' => 'form-check-input select-all-parameters', 
                            'id' => 'select-all-params'
                        ]) ?>
                        <?= Html::label('Zaznacz wszystkie parametry', 'select-all-params', [
                            'class' => 'form-check-label fw-bold'
                        ]) ?>
                    </div>
                    
                    <div class="parameters-list" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($template->parameters as $parameter): ?>
                            <div class="form-check mb-2 p-2 border rounded">
                                <?= Html::checkbox('selected_parameters[]', false, [
                                    'value' => $parameter->id,
                                    'class' => 'form-check-input parameter-checkbox me-2',
                                    'id' => 'param-' . $parameter->id
                                ]) ?>
                                
                                <div class="flex-grow-1">
                                    <?= Html::label(
                                        '<strong>' . Html::encode($parameter->name) . '</strong>',
                                        'param-' . $parameter->id,
                                        ['class' => 'form-check-label d-block']
                                    ) ?>
                                    
                                    <?php if ($parameter->unit): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-ruler me-1"></i>
                                            <?= Html::encode($parameter->unit) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Przyciski akcji -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex gap-2 justify-content-center">
                <?= Html::submitButton('<i class="fas fa-table me-2"></i>Porównaj wyniki', [
                    'class' => 'btn btn-primary btn-lg'
                ]) ?>
                <?= Html::button('<i class="fas fa-chart-line me-2"></i>Generuj wykres', [
                    'class' => 'btn btn-success btn-lg', 
                    'id' => 'generate-chart'
                ]) ?>
            </div>
        </div>
    </div>
    
    <?= Html::endForm() ?>
<!-- Kontener na wykres -->
    <div class="row mt-4" id="chart-container" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Wykres porównawczy
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" id="download-chart">
                        <i class="fas fa-download me-1"></i>
                        Pobierz wykres
                    </button>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 400px;">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela porównawcza (wersja na ekranie) -->
    <?php if (!empty($selectedResults)): ?>
        <div class="row mt-4 d-print-none" id="comparison-table">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>
                            Tabela porównawcza
                        </h5>
                        <small class="text-muted">
                            <?= count($selectedResults) ?> wyników × <?= count($template->parameters) ?> parametrów
                        </small>
                    </div>
                    <div class="card-body p-0">
                        <!-- Informacje dla wydruku (ukryte na ekranie) -->
                        <div class="d-none d-print-block p-3 border-bottom">
                            <h4 class="mb-3 text-center"><?= Html::encode($template->name) ?></h4>
                            <h5 class="text-center">Porównanie wyników badań</h5>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-hover comparison-table mb-0">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th class="text-center" style="min-width: 200px;">
                                            <i class="fas fa-cog me-1"></i>
                                            Parametr
                                        </th>
                                        <?php foreach ($selectedResults as $result): ?>
                                            <th class="text-center print-date-header" style="min-width: 140px;">
                                                <div class="d-flex flex-column align-items-center">
                                                    <strong class="mb-1 date-header"><?= Yii::$app->formatter->asDate($result->test_date) ?></strong>
                                                    <?php $status = $result->getDetailedStatus(); ?>
                                                    <span class="badge <?= $status['badge_class'] ?> badge-sm d-print-none">
                                                        <i class="<?= $status['icon'] ?>"></i>
                                                        <?= $status['message'] ?>
                                                    </span>
                                                    <!-- Data dla wydruku -->
                                                    <span class="d-none d-print-inline print-date"><?= Yii::$app->formatter->asDate($result->test_date) ?></span>
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($template->parameters as $parameter): ?>
                                        <tr>
                                            <td class="fw-bold bg-light">
                                                <div class="d-flex flex-column">
                                                    <span><?= Html::encode($parameter->name) ?></span>
                                                    <?php if ($parameter->unit): ?>
                                                        <small class="text-muted">
                                                            (<?= Html::encode($parameter->unit) ?>)
                                                        </small>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Informacje o normie bezpośrednio pod parametrem -->
                                                    <?php 
                                                    $primaryNorm = null;
                                                    if (isset($parameter->norms)) {
                                                        foreach ($parameter->norms as $norm) {
                                                            if ($norm->is_primary) {
                                                                $primaryNorm = $norm;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <?php if ($primaryNorm): ?>
                                                        <small class="text-primary fw-bold norm-info mt-1">
                                                            <i class="fas fa-ruler me-1"></i>
                                                            <?php if ($primaryNorm->type === 'range'): ?>
                                                                <?= $primaryNorm->min_value ?>-<?= $primaryNorm->max_value ?>
                                                            <?php elseif ($primaryNorm->type === 'single_threshold'): ?>
                                                                <?= $primaryNorm->threshold_direction === 'below' ? '≤' : '≥' ?><?= $primaryNorm->threshold_value ?>
                                                            <?php endif; ?>
                                                            <?= $parameter->unit ? ' ' . $parameter->unit : '' ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php foreach ($selectedResults as $result): ?>
                                                <td class="text-center">
                                                    <?php
                                                    $value = null;
                                                    $resultValue = null;
                                                    
                                                    foreach ($result->resultValues as $rv) {
                                                        if ($rv->parameter_id == $parameter->id) {
                                                            $resultValue = $rv;
                                                            $value = $rv->value;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    if ($resultValue): ?>
                                                        <div class="d-flex flex-column align-items-center">
                                                            <strong class="result-value 
                                                                <?php if ($resultValue->is_abnormal): ?>
                                                                    text-danger
                                                                <?php elseif ($resultValue->warning_level): ?>
                                                                    <?php switch ($resultValue->warning_level): 
                                                                        case \app\models\ParameterNorm::WARNING_LEVEL_WARNING: ?>
                                                                            text-warning
                                                                        <?php break; ?>
                                                                        <?php case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION: ?>
                                                                            text-info
                                                                        <?php break; ?>
                                                                        <?php case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL: ?>
                                                                            text-danger
                                                                        <?php break; ?>
                                                                        <?php default: ?>
                                                                            text-success
                                                                    <?php endswitch; ?>
                                                                <?php else: ?>
                                                                    text-success
                                                                <?php endif; ?>">
                                                                <?= Html::encode($value) ?>
                                                            </strong>
                                                            
                                                            <?php if ($resultValue->is_abnormal || $resultValue->warning_level): ?>
                                                                <small class="status-indicator d-print-none">
                                                                    <?php if ($resultValue->is_abnormal): ?>
                                                                        <i class="fas fa-exclamation-triangle text-danger" 
                                                                           title="Nieprawidłowe"></i>
                                                                    <?php elseif ($resultValue->warning_level == \app\models\ParameterNorm::WARNING_LEVEL_WARNING): ?>
                                                                        <i class="fas fa-exclamation-circle text-warning" 
                                                                           title="Ostrzeżenie"></i>
                                                                    <?php elseif ($resultValue->warning_level == \app\models\ParameterNorm::WARNING_LEVEL_CAUTION): ?>
                                                                        <i class="fas fa-info-circle text-info" 
                                                                           title="Uwaga"></i>
                                                                    <?php elseif ($resultValue->warning_level == \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL): ?>
                                                                        <i class="fas fa-exclamation-triangle text-danger" 
                                                                           title="Krytyczne"></i>
                                                                    <?php endif; ?>
                                                                </small>
                                                                
                                                                <!-- Status dla wydruku -->
                                                                <small class="d-none d-print-inline print-status">
                                                                    <?php if ($resultValue->is_abnormal): ?>
                                                                        ⚠
                                                                    <?php elseif ($resultValue->warning_level): ?>
                                                                        !
                                                                    <?php endif; ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="fas fa-minus"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wersja tylko dla wydruku (ukryta na ekranie) -->
        <div class="d-none d-print-block print-version mt-4">
            <!-- Nagłówek wydruku -->
            <div class="print-header">
                <h2 class="text-center mb-3"><?= Html::encode($template->name) ?></h2>
                <h4 class="text-center mb-4">Porównanie wyników badań</h4>
                
                <!-- Informacje o normach -->
                <div class="norms-info mb-4">
                    <h5>Normy referencyjne:</h5>
                    <?php foreach ($template->parameters as $parameter): ?>
                        <?php 
                        $primaryNorm = null;
                        if (isset($parameter->norms)) {
                            foreach ($parameter->norms as $norm) {
                                if ($norm->is_primary) {
                                    $primaryNorm = $norm;
                                    break;
                                }
                            }
                        }
                        ?>
                        <?php if ($primaryNorm): ?>
                            <div class="norm-item">
                                <strong><?= Html::encode($parameter->name) ?>:</strong>
                                <?php if ($primaryNorm->type === 'range'): ?>
                                    <?= $primaryNorm->min_value ?> - <?= $primaryNorm->max_value ?>
                                <?php elseif ($primaryNorm->type === 'single_threshold'): ?>
                                    <?= $primaryNorm->threshold_direction === 'below' ? '≤ ' : '≥ ' ?><?= $primaryNorm->threshold_value ?>
                                <?php endif; ?>
                                <?= $parameter->unit ? ' ' . $parameter->unit : '' ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabela dla każdego parametru -->
            <?php foreach ($template->parameters as $parameter): ?>
                <div class="parameter-section mb-4">
                    <h4 class="parameter-title">
                        <?= Html::encode($parameter->name) ?>
                        <?php if ($parameter->unit): ?>
                            (<?= Html::encode($parameter->unit) ?>)
                        <?php endif; ?>
                    </h4>
                    
                    <table class="print-table">
                        <thead>
                            <tr>
                                <th class="text-left">Data badania</th>
                                <th class="text-right">Wartość</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedResults as $result): ?>
                                <?php
                                $value = null;
                                $resultValue = null;
                                
                                foreach ($result->resultValues as $rv) {
                                    if ($rv->parameter_id == $parameter->id) {
                                        $resultValue = $rv;
                                        $value = $rv->value;
                                        break;
                                    }
                                }
                                ?>
                                <?php if ($resultValue): ?>
                                    <tr class="<?= $resultValue->is_abnormal ? 'abnormal-row' : '' ?>">
                                        <td class="text-left"><?= Yii::$app->formatter->asDate($result->test_date) ?></td>
                                        <td class="text-right value-cell">
                                            <strong><?= Html::encode($value) ?></strong>
                                        </td>
                                        <td class="text-center status-cell">
                                            <?php if ($resultValue->is_abnormal): ?>
                                                ⚠ NIEPRAWIDŁOWE
                                            <?php elseif ($resultValue->warning_level): ?>
                                                <?php switch ($resultValue->warning_level): 
                                                    case \app\models\ParameterNorm::WARNING_LEVEL_WARNING: ?>
                                                        ! OSTRZEŻENIE
                                                    <?php break; ?>
                                                    <?php case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION: ?>
                                                        ! UWAGA
                                                    <?php break; ?>
                                                    <?php case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL: ?>
                                                        ⚠ KRYTYCZNE
                                                    <?php break; ?>
                                                    <?php default: ?>
                                                        ✓ PRAWIDŁOWE
                                                <?php endswitch; ?>
                                            <?php else: ?>
                                                ✓ PRAWIDŁOWE
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            
            <!-- Stopka -->
            <div class="print-footer mt-4">
                <hr>
                <div class="footer-info">
                    <strong>Data wydruku:</strong> <?= date('d.m.Y H:i') ?><br>
                    <strong>System:</strong> MedArchive
                </div>
            </div>
        </div>
    <?php endif; ?>
    
</div>

<!-- Style CSS -->
<style>
.results-list .form-check:hover,
.parameters-list .form-check:hover {
    background-color: #f8f9fa !important;
}

.comparison-table th {
    vertical-align: middle;
}

.result-value {
    font-size: 1.1em;
}

.status-indicator {
    font-size: 0.8em;
}

.badge-sm {
    font-size: 0.75em;
}

@media print {
    .btn-toolbar,
    .card-header button,
    #compare-form {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
}
</style>

<!-- JavaScript dla funkcjonalności -->
<?php
$js = <<<JS
// Czekaj na załadowanie wszystkich skryptów
window.addEventListener('load', function() {
    // Sprawdź czy jQuery jest dostępne
    if (typeof $ === 'undefined') {
        console.error('jQuery nie jest załadowane');
        // Fallback - użyj vanilla JS
        initializeWithoutJQuery();
        return;
    }
    
    initializeWithJQuery();
});

function initializeWithJQuery() {
    // Zaznacz wszystkie wyniki
    $('#select-all').change(function() {
        $('.result-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Zaznacz wszystkie parametry
    $('#select-all-params').change(function() {
        $('.parameter-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Sprawdź czy wszystkie są zaznaczone (dla wyników)
    $('.result-checkbox').change(function() {
        const allChecked = $('.result-checkbox').length === $('.result-checkbox:checked').length;
        $('#select-all').prop('checked', allChecked);
    });
    
    // Sprawdź czy wszystkie są zaznaczone (dla parametrów)
    $('.parameter-checkbox').change(function() {
        const allChecked = $('.parameter-checkbox').length === $('.parameter-checkbox:checked').length;
        $('#select-all-params').prop('checked', allChecked);
    });
    
    // Generowanie wykresu
    $('#generate-chart').click(function(e) {
        e.preventDefault();
        
        var selectedResults = [];
        var selectedParameters = [];
        
        $('.result-checkbox:checked').each(function() {
            selectedResults.push($(this).val());
        });
        
        $('.parameter-checkbox:checked').each(function() {
            selectedParameters.push($(this).val());
        });
        
        if (selectedResults.length === 0 || selectedParameters.length === 0) {
            alert('Wybierz wyniki i parametry do porównania');
            return;
        }
        
        generateComparisonChart(selectedResults, selectedParameters);
    });
    
    // Pobieranie wykresu
    $('#download-chart').click(function() {
        if (!window.comparisonChart) {
            alert('Najpierw wygeneruj wykres');
            return;
        }
        
        try {
            var link = document.createElement('a');
            link.download = 'porownanie-wynikow-' + new Date().toISOString().slice(0, 10) + '.png';
            link.href = window.comparisonChart.toBase64Image('image/png', 1.0);
            link.click();
            alert('Wykres został pobrany');
        } catch (error) {
            console.error('Download error:', error);
            alert('Błąd podczas pobierania wykresu');
        }
    });
    
    // Drukowanie
    $('#print-comparison').click(function() {
        var chartVisible = $('#chart-container').is(':visible');
        if (chartVisible) {
            $('#chart-container').hide();
        }
        
        window.print();
        
        if (chartVisible) {
            $('#chart-container').show();
        }
    });
}

function initializeWithoutJQuery() {
    // Fallback dla vanilla JavaScript
    console.log('Używam vanilla JavaScript jako fallback');
    
    var selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.result-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }
    
    var selectAllParams = document.getElementById('select-all-params');
    if (selectAllParams) {
        selectAllParams.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.parameter-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAllParams.checked;
            });
        });
    }
    
    var generateBtn = document.getElementById('generate-chart');
    if (generateBtn) {
        generateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            var selectedResults = [];
            var selectedParameters = [];
            
            document.querySelectorAll('.result-checkbox:checked').forEach(function(cb) {
                selectedResults.push(cb.value);
            });
            
            document.querySelectorAll('.parameter-checkbox:checked').forEach(function(cb) {
                selectedParameters.push(cb.value);
            });
            
            if (selectedResults.length === 0 || selectedParameters.length === 0) {
                alert('Wybierz wyniki i parametry do porównania');
                return;
            }
            
            generateComparisonChart(selectedResults, selectedParameters);
        });
    }
}

// Funkcja generująca wykres (bez alertów)
function generateComparisonChart(resultIds, parameterIds) {
    // Pokaż loading
    var btn = document.getElementById('generate-chart');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Generowanie...';
    }
    
    // Przygotuj dane do wysłania
    var formData = new FormData();
    
    // Dodaj CSRF token
    var csrfParam = document.querySelector('meta[name="csrf-param"]');
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    
    if (csrfParam && csrfToken) {
        formData.append(csrfParam.content, csrfToken.content);
    }
    
    // Dodaj każdy resultId jako osobny element tablicy
    resultIds.forEach(function(id, index) {
        formData.append('resultIds[' + index + ']', id);
    });
    
    // Dodaj każdy parameterId jako osobny element tablicy
    parameterIds.forEach(function(id, index) {
        formData.append('parameterIds[' + index + ']', id);
    });
    
    // Użyj fetch API
    fetch('/test-results/get-chart-data', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            createChart(data.data);
        } else {
            console.error('Błąd podczas generowania wykresu:', data.error);
        }
    })
    .catch(function(error) {
        console.error('Fetch error:', error);
    })
    .finally(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-chart-line me-2"></i>Generuj wykres';
        }
    });
}

function createChart(data) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js nie jest załadowane');
        alert('Błąd: Chart.js nie jest dostępny');
        return;
    }
    
    var ctx = document.getElementById('comparisonChart');
    if (!ctx) {
        console.error('Element canvas nie został znaleziony');
        alert('Błąd: Nie można znaleźć elementu wykresu');
        return;
    }

    if (window.comparisonChart && typeof window.comparisonChart.destroy === 'function') {
        try {
            window.comparisonChart.destroy();
        } catch (e) {
            console.warn('Błąd podczas usuwania wykresu:', e);
        }
    }

    var colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6B6B', '#4ECDC4'
    ];

    var datasets = [];

    // Dodaj serie danych dla parametrów
    data.parameters.forEach(function(parameter, index) {
        var color = colors[index % colors.length];
        
        // Główna linia z danymi
        datasets.push({
            label: parameter.name + (parameter.unit ? ' (' + parameter.unit + ')' : ''),
            data: parameter.values,
            borderColor: color,
            backgroundColor: color + '20',
            fill: false,
            tension: 0.2,
            pointRadius: 6,
            pointBackgroundColor: color,
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        });
        
        // Dodaj linie norm jako dodatkowe serie danych
        if (parameter.norms && parameter.norms.length > 0) {
            parameter.norms.forEach(function(norm, normIndex) {
                var normColor = color.replace(/FF/g, '80'); // Przezroczystość
                
                if (norm.type === 'range') {
                    // Linia minimalna normy
                    datasets.push({
                        label: parameter.name + ' - norma min',
                        data: new Array(data.dates.length).fill(norm.min_value),
                        borderColor: normColor,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0,
                        fill: false,
                        tension: 0
                    });
                    
                    // Linia maksymalna normy
                    datasets.push({
                        label: parameter.name + ' - norma max',
                        data: new Array(data.dates.length).fill(norm.max_value),
                        borderColor: normColor,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0,
                        fill: '+1', // Wypełnij obszar między min a max
                        backgroundColor: normColor + '15',
                        tension: 0
                    });
                    
                } else if (norm.type === 'single_threshold') {
                    // Pojedyncza linia progu
                    datasets.push({
                        label: parameter.name + ' - próg ' + (norm.threshold_direction === 'below' ? '≤' : '≥') + norm.threshold_value,
                        data: new Array(data.dates.length).fill(norm.threshold_value),
                        borderColor: normColor,
                        backgroundColor: 'transparent',
                        borderWidth: 3,
                        borderDash: [10, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0,
                        fill: false,
                        tension: 0
                    });
                }
            });
        }
    });

    try {
        window.comparisonChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: '#e9ecef'
                        }
                    },
                    x: {
                        grid: {
                            color: '#e9ecef'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            filter: function(legendItem, chartData) {
                                // Ukryj normy z legendy (opcjonalnie)
                                return !legendItem.text.includes('norma') && !legendItem.text.includes('próg');
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Porównanie wyników badań w czasie (z normami)'
                    },
                    tooltip: {
                        filter: function(tooltipItem) {
                            // Ukryj normy z tooltip (opcjonalnie)
                            return !tooltipItem.dataset.label.includes('norma') && !tooltipItem.dataset.label.includes('próg');
                        }
                    }
                }
            }
        });

        var container = document.getElementById('chart-container');
        if (container) {
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
    } catch (error) {
        console.error('Błąd tworzenia wykresu:', error);
        alert('Błąd podczas tworzenia wykresu: ' + error.message);
    }
}

// Funkcja pokazywania alertów
function showAlert(message, type) {
    // Prosta implementacja alertu
    if (type === 'success') {
        console.log('✅ ' + message);
    } else {
        console.error('❌ ' + message);
    }
    
    // Możesz rozszerzyć to o lepsze alerty później
    alert(message);
}
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>