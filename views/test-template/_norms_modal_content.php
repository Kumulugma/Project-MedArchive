<?php
use yii\helpers\Html;

/* @var $template app\models\TestTemplate */
/* @var $parameter app\models\TestParameter */
/* @var $norms app\models\ParameterNorm[] */
?>

<div class="norms-content">
    <!-- Header z informacją o parametrze -->
    <div class="parameter-info mb-4 p-3 rounded">
        <h6 class="mb-2">
            <i class="fas fa-chart-line text-primary"></i> 
            <strong><?= Html::encode($parameter->name) ?></strong>
        </h6>
        <?php if ($parameter->unit): ?>
            <div class="mb-2">
                <span class="badge bg-light text-dark border">
                    <i class="fas fa-ruler"></i> <?= Html::encode($parameter->unit) ?>
                </span>
            </div>
        <?php endif; ?>
        <?php if (isset($parameter->description) && $parameter->description): ?>
            <p class="small mb-0 text-muted mt-2">
                <i class="fas fa-info-circle"></i>
                <?= Html::encode($parameter->description) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Lista norm -->
    <?php if (!empty($norms)): ?>
        <div class="norms-list">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="fas fa-list-alt text-primary"></i> 
                    Skonfigurowane normy
                </h6>
                <span class="badge bg-primary"><?= count($norms) ?></span>
            </div>
            
            <?php foreach ($norms as $norm): ?>
                <div class="norm-item mb-3 p-3 border rounded">
                    <!-- Header normy -->
                    <div class="norm-header mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="norm-info flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas fa-bookmark text-primary"></i>
                                    <?= Html::encode($norm->name) ?>
                                    <?php if ($norm->is_primary): ?>
                                        <span class="badge bg-primary ms-2">
                                            <i class="fas fa-star"></i> Podstawowa
                                        </span>
                                    <?php endif; ?>
                                </h6>
                                <div class="norm-details mt-2">
                                    <span class="badge bg-light text-dark border me-1">
                                        <?= ucfirst($norm->getTypeName()) ?>
                                    </span>
                                    <small class="text-muted">
                                        <?php if ($norm->type === 'range'): ?>
                                            <i class="fas fa-arrows-alt-h"></i> <?= $norm->min_value ?> - <?= $norm->max_value ?>
                                        <?php elseif ($norm->type === 'single_threshold'): ?>
                                            <i class="fas fa-arrow-<?= $norm->threshold_direction === 'above' ? 'up' : 'down' ?>"></i>
                                            <?= $norm->threshold_direction === 'above' ? '≥' : '≤' ?> <?= $norm->threshold_value ?>
                                        <?php elseif ($norm->type === 'positive_negative'): ?>
                                            <i class="fas fa-check-circle text-success"></i> Negatywny = Normalny
                                        <?php elseif ($norm->type === 'multiple_thresholds'): ?>
                                            <i class="fas fa-layer-group"></i> Wiele progów
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status ostrzeżeń -->
                    <div class="warnings-status mb-3 rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-bell text-muted me-2"></i>
                                <span class="small fw-semibold text-muted">Ostrzeżenia:</span>
                            </div>
                            <div class="text-end">
                                <?php if ($norm->warning_enabled): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-bell"></i> Włączone
                                    </span>
                                    <?php if ($norm->warning_margin_percent || $norm->caution_margin_percent): ?>
                                        <div class="mt-1">
                                            <?php if ($norm->warning_margin_percent): ?>
                                                <small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> <?= $norm->warning_margin_percent ?>%
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($norm->caution_margin_percent): ?>
                                                <small class="text-info ms-2">
                                                    <i class="fas fa-info-circle"></i> <?= $norm->caution_margin_percent ?>%
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-bell-slash"></i> Wyłączone
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Przyciski akcji -->
                    <div class="actions-row">
                        <div class="btn-group w-100" role="group">
                            <!-- Edytuj normę -->
                            <?= Html::a(
                                '<i class="fas fa-edit"></i> Edytuj', 
                                ['update-norm', 'id' => $template->id, 'parameterId' => $parameter->id, 'normId' => $norm->id], 
                                ['class' => 'btn btn-outline-primary btn-sm']
                            ) ?>
                            
                            <!-- Zarządzanie ostrzeżeniami -->
                            <?php if (!$norm->warning_enabled): ?>
                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                        data-norm-id="<?= $norm->id ?>"
                                        data-parameter-id="<?= $parameter->id ?>"
                                        onclick="enableWarningsFromSidebar(this.dataset.normId, this.dataset.parameterId)">
                                    <i class="fas fa-bell"></i> Włącz ostrzeżenia
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        data-norm-id="<?= $norm->id ?>"
                                        data-parameter-id="<?= $parameter->id ?>"
                                        onclick="disableWarningsFromSidebar(this.dataset.normId, this.dataset.parameterId)">
                                    <i class="fas fa-bell-slash"></i> Wyłącz ostrzeżenia
                                </button>
                                
                                <!-- Szybka konfiguracja ostrzeżeń -->
                                <?= Html::a(
                                    '<i class="fas fa-cog"></i> Konfiguruj', 
                                    ['update-norm', 'id' => $template->id, 'parameterId' => $parameter->id, 'normId' => $norm->id, 'focus' => 'warnings'], 
                                    ['class' => 'btn btn-outline-info btn-sm']
                                ) ?>
                            <?php endif; ?>
                            
                            <!-- Usuń normę -->
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    data-norm-id="<?= $norm->id ?>"
                                    data-parameter-id="<?= $parameter->id ?>"
                                    data-norm-name="<?= Html::encode($norm->name) ?>"
                                    onclick="deleteNormFromSidebar(this.dataset.normId, this.dataset.parameterId, this.dataset.normName)">
                                <i class="fas fa-trash-alt"></i> Usuń
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Brak norm -->
        <div class="no-norms-message">
            <div class="text-center">
                <i class="fas fa-chart-bar fa-3x mb-3"></i>
                <h6 class="text-muted mb-2">Brak skonfigurowanych norm</h6>
                <p class="small text-muted mb-3">
                    Dodaj pierwszą normę aby móc analizować wyniki badań dla tego parametru.
                </p>
                <div class="mt-3">
                    <?= Html::a(
                        '<i class="fas fa-plus-circle"></i> Dodaj pierwszą normę', 
                        ['add-norm', 'id' => $template->id, 'parameterId' => $parameter->id], 
                        ['class' => 'btn btn-outline-success btn-sm']
                    ) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Sekcja dodawania nowej normy -->
    <?php if (!empty($norms)): ?>
        <div class="add-norm-section mt-4">
            <div class="d-flex align-items-center justify-content-center">
                <i class="fas fa-plus-circle text-success me-2"></i>
                <?= Html::a(
                    'Dodaj kolejną normę', 
                    ['add-norm', 'id' => $template->id, 'parameterId' => $parameter->id], 
                    ['class' => 'btn btn-outline-success btn-sm']
                ) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Informacja pomocnicza -->
<div class="modal-info mt-4">
    <div class="d-flex align-items-start">
        <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
        <div class="small">
            <strong>Wskazówka:</strong> Możesz mieć wiele norm dla jednego parametru. 
            Oznacz jedną z nich jako "podstawową" podczas edycji. System ostrzeżeń pozwoli na wcześniejsze 
            wykrycie wartości granicznych, zanim przekroczą normę.
            
            <div class="mt-2">
                <strong>Typy ostrzeżeń:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    <li><span class="text-info"><i class="fas fa-info-circle"></i> Uwaga</span> - wartość bliska granicy</li>
                    <li><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Ostrzeżenie</span> - wartość bardzo bliska granicy</li>
                    <li><span class="text-danger"><i class="fas fa-times-circle"></i> Krytyczna</span> - wartość poza normą</li>
                </ul>
            </div>
        </div>
    </div>
</div>