<?php
// views/test-result/_enhanced_result_display.php

use yii\helpers\Html;

?>

<div class="enhanced-result-display">
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Parametr</th>
                    <th>Wartość</th>
                    <th>Norma</th>
                    <th>Status</th>
                    <th>Rekomendacja</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model->resultValues as $value): ?>
                    <tr class="result-row <?= $value->getDisplayClass() ?>">
                        <td>
                            <strong><?= Html::encode($value->parameter->name) ?></strong>
                            <?php if ($value->parameter->unit): ?>
                                <small class="text-muted d-block">(<?= Html::encode($value->parameter->unit) ?>)</small>
                            <?php endif; ?>
                        </td>
                        
                        <td class="value-cell">
                            <div class="d-flex align-items-center">
                                <i class="<?= $value->getWarningIcon() ?> me-2"></i>
                                <strong class="<?= $value->getDisplayClass() ?>">
                                    <?= Html::encode($value->value) ?>
                                </strong>
                                <?php if ($value->parameter->unit): ?>
                                    <small class="text-muted ms-1"><?= Html::encode($value->parameter->unit) ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($value->distance_from_boundary !== null): ?>
                                <small class="text-muted d-block">
                                    Odległość od granicy: <?= number_format($value->distance_from_boundary, 2) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        
                        <td class="norm-cell">
                            <?php if ($value->norm): ?>
                                <div class="norm-info">
                                    <strong><?= Html::encode($value->norm->name) ?></strong>
                                    <div class="norm-details mt-1">
                                        <?php
                                        switch($value->norm->type) {
                                            case 'range':
                                                echo '<span class="badge bg-info mb-1">Zakres</span><br>';
                                                echo '<small>' . Html::encode($value->norm->min_value) . ' - ' . Html::encode($value->norm->max_value) . '</small>';
                                                
                                                // Pokaż strefy ostrzeżeń jeśli włączone
                                                if ($value->norm->warning_enabled) {
                                                    echo $this->render('_warning_zones', [
                                                        'norm' => $value->norm, 
                                                        'value' => $value->normalized_value
                                                    ]);
                                                }
                                                break;
                                                
                                            case 'single_threshold':
                                                echo '<span class="badge bg-warning mb-1">Próg</span><br>';
                                                echo '<small>' . ($value->norm->threshold_direction === 'above' ? '≤ ' : '≥ ') . Html::encode($value->norm->threshold_value) . '</small>';
                                                break;
                                                
                                            case 'positive_negative':
                                                echo '<span class="badge bg-secondary">Dodatni/Ujemny</span>';
                                                break;
                                                
                                            case 'multiple_thresholds':
                                                echo '<span class="badge bg-primary mb-1">Wielokrotne progi</span>';
                                                if ($value->norm->thresholds_config) {
                                                    $thresholds = json_decode($value->norm->thresholds_config, true);
                                                    if ($thresholds) {
                                                        echo '<ul class="list-unstyled mt-1 mb-0" style="font-size: 0.8em;">';
                                                        foreach ($thresholds as $threshold) {
                                                            $class = $threshold['is_normal'] ? 'text-success' : 'text-danger';
                                                            echo '<li class="' . $class . '">';
                                                            echo Html::encode($threshold['value']);
                                                            if (!empty($threshold['label'])) {
                                                                echo ' - ' . Html::encode($threshold['label']);
                                                            }
                                                            echo '</li>';
                                                        }
                                                        echo '</ul>';
                                                    }
                                                }
                                                break;
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Brak normy</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="status-cell">
                            <div class="d-flex flex-column align-items-start">
                                <?= $value->getWarningBadge() ?>
                                
                                <?php if ($value->warning_message): ?>
                                    <small class="text-muted mt-1" title="<?= Html::encode($value->warning_message) ?>">
                                        <?= Html::encode($value->warning_message) ?>
                                    </small>
                                <?php endif; ?>
                                
                                <?php if ($value->is_borderline): ?>
                                    <span class="badge bg-light text-dark mt-1">
                                        <i class="fas fa-eye"></i> Wartość graniczna
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td class="recommendation-cell">
                            <?php if ($value->recommendation): ?>
                                <div class="recommendation-box p-2 rounded" style="background-color: rgba(0,123,255,0.1);">
                                    <small>
                                        <i class="fas fa-lightbulb text-warning"></i>
                                        <?= Html::encode($value->recommendation) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($value->requiresMedicalAttention()): ?>
                                <div class="alert alert-warning p-1 mt-1 mb-0">
                                    <small>
                                        <i class="fas fa-user-md"></i> 
                                        Zalecana konsultacja lekarska
                                    </small>
                                </div>
                            <?php elseif ($value->requiresMonitoring()): ?>
                                <div class="alert alert-info p-1 mt-1 mb-0">
                                    <small>
                                        <i class="fas fa-chart-line"></i> 
                                        Zalecane monitorowanie
                                    </small>
                                </div>
                            <?php endif; ?>
                        </td>
                        
                        <td class="actions-cell">
                            <div class="btn-group-vertical" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="showTrend(<?= $value->parameter_id ?>)" 
                                        title="Pokaż trend">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                
                                <?php if ($value->requiresMonitoring() || $value->is_borderline): ?>
                                    <button type="button" class="btn btn-outline-warning btn-sm" 
                                            onclick="scheduleFollowUp(<?= $value->id ?>)" 
                                            title="Zaplanuj kontrolę">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($value->requiresMedicalAttention()): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            onclick="flagForConsultation(<?= $value->id ?>)" 
                                            title="Oznacz do konsultacji">
                                        <i class="fas fa-flag"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Podsumowanie ostrzeżeń -->
    <?php
    $warnings = array_filter($model->resultValues, function($v) { 
        return $v->warning_level !== 'none'; 
    });
    $criticalCount = count(array_filter($warnings, function($v) { 
        return !$v->is_abnormal && $v->warning_level === 'warning'; 
    }));
    $cautionCount = count(array_filter($warnings, function($v) { 
        return $v->warning_level === 'caution'; 
    }));
    $abnormalCount = count(array_filter($model->resultValues, function($v) { 
        return $v->is_abnormal; 
    }));
    ?>
    
    <?php if (!empty($warnings) || $abnormalCount > 0): ?>
        <div class="alert alert-info mt-3">
            <h6><i class="fas fa-info-circle"></i> Podsumowanie wyników</h6>
            <div class="row">
                <?php if ($abnormalCount > 0): ?>
                    <div class="col-md-3">
                        <div class="alert alert-danger mb-2 p-2">
                            <strong><?= $abnormalCount ?></strong> nieprawidłowych
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($criticalCount > 0): ?>
                    <div class="col-md-3">
                        <div class="alert alert-warning mb-2 p-2">
                            <strong><?= $criticalCount ?></strong> wymaga uwagi
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($cautionCount > 0): ?>
                    <div class="col-md-3">
                        <div class="alert alert-info mb-2 p-2">
                            <strong><?= $cautionCount ?></strong> do obserwacji
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-3">
                    <div class="alert alert-success mb-2 p-2">
                        <strong><?= count($model->resultValues) - count($warnings) - $abnormalCount ?></strong> optymalnych
                    </div>
                </div>
            </div>
            
            <?php if ($abnormalCount > 0 || $criticalCount > 0): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-exclamation-triangle"></i>
                        Zalecana konsultacja z lekarzem w sprawie nieprawidłowych lub granicznych wyników.
                    </small>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Szczegółowe rekomendacje -->
    <?php
    $allRecommendations = array_filter($model->resultValues, function($v) {
        return !empty($v->recommendation) && ($v->requiresMedicalAttention() || $v->requiresMonitoring());
    });
    ?>
    
    <?php if (!empty($allRecommendations)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clipboard-list"></i> Szczegółowe rekomendacje
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($allRecommendations as $value): ?>
                        <div class="col-md-6 mb-2">
                            <div class="recommendation-item p-2 border rounded">
                                <strong class="<?= $value->getDisplayClass() ?>">
                                    <?= Html::encode($value->parameter->name) ?>:
                                </strong>
                                <small class="d-block text-muted">
                                    <?= Html::encode($value->recommendation) ?>
                                </small>
                                
                                <?php if ($value->requiresMedicalAttention()): ?>
                                    <span class="badge bg-warning mt-1">Priorytet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal dla szczegółów trendu -->
<div class="modal fade" id="trendModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trend parametru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="trendModalBody">
                <!-- Tutaj będzie wykres trendu -->
            </div>
        </div>
    </div>
</div>

<!-- Modal dla planowania kontroli -->
<div class="modal fade" id="followUpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Planowanie kontroli</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="followUpForm">
                    <input type="hidden" id="followUpResultValueId" name="resultValueId">
                    
                    <div class="mb-3">
                        <label for="followUpDate" class="form-label">Data kontroli</label>
                        <input type="date" class="form-control" id="followUpDate" name="followUpDate" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="followUpNote" class="form-label">Notatka</label>
                        <textarea class="form-control" id="followUpNote" name="note" rows="3" 
                                  placeholder="Opcjonalna notatka dotycząca kontroli..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="followUpReminder" name="reminder">
                            <label class="form-check-label" for="followUpReminder">
                                Wyślij przypomnienie
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary" onclick="saveFollowUp()">Zaplanuj kontrolę</button>
            </div>
        </div>
    </div>
</div>

<script>
function showTrend(parameterId) {
    // Implementacja pokazywania trendu
    $('#trendModal').modal('show');
    $('#trendModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Ładowanie trendu...</div>');
    
    // AJAX call do pobrania danych trendu
    $.get('/test-result/parameter-trend', {parameterId: parameterId})
        .done(function(data) {
            $('#trendModalBody').html(data);
        })
        .fail(function() {
            $('#trendModalBody').html('<div class="alert alert-danger">Błąd ładowania danych trendu</div>');
        });
}

function scheduleFollowUp(resultValueId) {
    // Ustaw domyślną datę na za miesiąc
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const dateString = nextMonth.toISOString().split('T')[0];
    
    $('#followUpResultValueId').val(resultValueId);
    $('#followUpDate').val(dateString);
    $('#followUpModal').modal('show');
}

function saveFollowUp() {
    const formData = {
        resultValueId: $('#followUpResultValueId').val(),
        followUpDate: $('#followUpDate').val(),
        note: $('#followUpNote').val(),
        reminder: $('#followUpReminder').is(':checked')
    };
    
    $.post('/test-result/schedule-follow-up', formData)
        .done(function(response) {
            if (response.success) {
                $('#followUpModal').modal('hide');
                showNotification('Kontrola została zaplanowana', 'success');
                // Opcjonalnie odśwież stronę lub zaktualizuj UI
            } else {
                showNotification('Błąd planowania kontroli: ' + response.message, 'error');
            }
        })
        .fail(function() {
            showNotification('Błąd komunikacji z serwerem', 'error');
        });
}

function flagForConsultation(resultValueId) {
    if (confirm('Czy chcesz oznaczyć ten wynik do konsultacji z lekarzem?')) {
        $.post('/test-result/flag-consultation', {resultValueId: resultValueId})
            .done(function(response) {
                if (response.success) {
                    showNotification('Wynik został oznaczony do konsultacji', 'success');
                    location.reload();
                } else {
                    showNotification('Błąd oznaczania: ' + response.message, 'error');
                }
            })
            .fail(function() {
                showNotification('Błąd komunikacji z serwerem', 'error');
            });
    }
}

function showNotification(message, type) {
    // Prosta implementacja powiadomień
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(notification);
    
    // Automatyczne ukrycie po 5 sekundach
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Inicjalizacja tooltipów Bootstrap
$(document).ready(function() {
    $('[title]').tooltip();
});
</script>

<style>
.result-row.text-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

.result-row.text-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

.result-row.text-info {
    background-color: rgba(13, 202, 240, 0.1);
}

.value-cell strong {
    font-size: 1.1em;
}

.norm-details {
    font-size: 0.85em;
    background-color: rgba(248, 249, 250, 0.8);
    padding: 0.25rem;
    border-radius: 0.25rem;
    border-left: 3px solid var(--bs-primary);
}

.recommendation-box {
    max-width: 200px;
    font-size: 0.8em;
}

.recommendation-item {
    background-color: rgba(248, 249, 250, 0.5);
}

.actions-cell .btn {
    margin-bottom: 2px;
    font-size: 0.75em;
}

.badge {
    font-size: 0.7em;
}

.warning-zones {
    margin-top: 0.5rem;
}

.zones-bar {
    height: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    position: relative;
}

.value-indicator {
    position: absolute;
    top: -2px;
    width: 3px;
    height: 19px;
    background: #000;
    border-radius: 1px;
    transform: translateX(-50%);
}

.zones-legend {
    font-size: 0.7em;
    margin-top: 0.25rem;
}

.zones-description {
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85em;
    }
    
    .recommendation-cell,
    .actions-cell {
        display: none;
    }
    
    .norm-cell .norm-details {
        display: none;
    }
    
    .status-cell .badge {
        font-size: 0.6em;
    }
}

@media (max-width: 576px) {
    .norm-cell {
        display: none;
    }
    
    .value-cell small {
        display: none;
    }
}

/* Animacje dla lepszego UX */
.result-row {
    transition: background-color 0.3s ease;
}

.result-row:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.btn {
    transition: all 0.2s ease;
}

.alert {
    animation: slideInRight 0.5s ease;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>