<?php
use yii\helpers\Html;
?>

<div class="parameter-info mb-3">
    <h6><i class="fas fa-flask"></i> Parametr: <strong><?= Html::encode($parameter->name) ?></strong></h6>
    <?php if ($parameter->unit): ?>
        <small class="text-muted">Jednostka: <?= Html::encode($parameter->unit) ?></small>
    <?php endif; ?>
</div>

<?php if (empty($norms)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Ten parametr nie ma jeszcze skonfigurowanych norm.
    </div>
    
    <div class="text-center">
        <?= Html::a('<i class="fas fa-plus"></i> Dodaj pierwszą normę', 
            ['add-norm', 'id' => $template->id, 'parameterId' => $parameter->id], 
            ['class' => 'btn btn-success']) ?>
    </div>
<?php else: ?>
    <div class="norms-list">
        <?php foreach ($norms as $index => $norm): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">
                            <?= Html::encode($norm->name) ?>
                            <?php if ($norm->is_primary): ?>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="fas fa-star"></i> Podstawowa
                                </span>
                            <?php endif; ?>
                        </div>
                    <div>
                        <span class="badge bg-info"><?= ucfirst($norm->type) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Zakres normy:</strong>
                            <div class="norm-range mt-1">
                                <?php if ($norm->type === 'range'): ?>
                                    <span class="badge bg-light text-dark">
                                        <?= $norm->min_value ?> - <?= $norm->max_value ?>
                                        <?php if ($parameter->unit): ?>
                                            <?= Html::encode($parameter->unit) ?>
                                        <?php endif; ?>
                                    </span>
                                <?php elseif ($norm->type === 'single_threshold'): ?>
                                    <span class="badge bg-light text-dark">
                                        <?= $norm->threshold_direction === 'above' ? '≤' : '≥' ?>
                                        <?= $norm->threshold_value ?>
                                        <?php if ($parameter->unit): ?>
                                            <?= Html::encode($parameter->unit) ?>
                                        <?php endif; ?>
                                    </span>
                                <?php elseif ($norm->type === 'positive_negative'): ?>
                                    <span class="badge bg-light text-dark">Pozytywny/Negatywny</span>
                                <?php elseif ($norm->type === 'multiple_thresholds'): ?>
                                    <span class="badge bg-light text-dark">Wielokrotne progi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Ostrzeżenia:</strong>
                            <div class="warnings-status mt-1">
                                <?php if ($norm->warning_enabled): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-bell"></i> Włączone
                                    </span>
                                    <?php if ($norm->warning_margin_percent): ?>
                                        <small class="text-muted d-block">
                                            Ostrzeżenie: <?= $norm->warning_margin_percent ?>%
                                            <?php if ($norm->caution_margin_percent): ?>
                                                | Uwaga: <?= $norm->caution_margin_percent ?>%
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-bell-slash"></i> Wyłączone
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="actions-row mt-3 pt-3 border-top">
                        <div class="btn-group w-100" role="group">
                            <?= Html::a('<i class="fas fa-edit"></i> Edytuj', 
                                ['update-norm', 'id' => $template->id, 'parameterId' => $parameter->id, 'normId' => $norm->id], 
                                ['class' => 'btn btn-outline-primary']) ?>
                            
                            <?php if (!$norm->warning_enabled): ?>
                                <button type="button" class="btn btn-outline-warning" 
                                        data-norm-id="<?= $norm->id ?>"
                                        data-parameter-id="<?= $parameter->id ?>"
                                        onclick="enableWarningsFromSidebar(this.dataset.normId, this.dataset.parameterId)">
                                    <i class="fas fa-exclamation-triangle"></i> Włącz ostrzeżenia
                                </button>
                            <?php else: ?>
                                <?= Html::a('<i class="fas fa-cog"></i> Konfiguruj ostrzeżenia', 
                                    ['update-norm', 'id' => $template->id, 'parameterId' => $parameter->id, 'normId' => $norm->id, 'focus' => 'warnings'], 
                                    ['class' => 'btn btn-outline-info']) ?>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-outline-danger" 
                                    data-norm-id="<?= $norm->id ?>"
                                    data-parameter-id="<?= $parameter->id ?>"
                                    data-norm-name="<?= Html::encode($norm->name) ?>"
                                    onclick="deleteNormFromSidebar(this.dataset.normId, this.dataset.parameterId, this.dataset.normName)">
                                <i class="fas fa-trash"></i> Usuń
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="add-norm-section mt-4 pt-3 border-top">
        <div class="text-center">
            <?= Html::a('<i class="fas fa-plus"></i> Dodaj kolejną normę', 
                ['add-norm', 'id' => $template->id, 'parameterId' => $parameter->id], 
                ['class' => 'btn btn-outline-success']) ?>
        </div>
    </div>
<?php endif; ?>

<div class="modal-info mt-4 pt-3 border-top">
    <small class="text-muted">
        <i class="fas fa-info-circle"></i>
        <strong>Wskazówka:</strong> Możesz mieć wiele norm dla jednego parametru. 
        Oznacz jedną z nich jako "podstawową" podczas edycji.
    </small>
</div>