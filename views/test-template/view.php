<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\assets\TestTemplateAsset;

/* @var $this yii\web\View */
/* @var $model app\models\TestTemplate */

TestTemplateAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-template-view">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i class="fas fa-file-medical"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-edit"></i> Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fas fa-copy"></i> Klonuj', ['clone', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a('<i class="fas fa-trash"></i> Usuń', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-outline-danger',
                    'data' => [
                        'confirm' => 'Czy na pewno chcesz usunąć ten szablon? Ta operacja jest nieodwracalna.',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        </div>
    </div>

    <div class="alert-container"></div>

    <!-- Template Details -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Szczegóły szablonu</h5>
                </div>
                <div class="card-body">
                    <div class="template-details">
                        <div class="detail-item mb-3">
                            <label class="detail-label">Nazwa badania:</label>
                            <div class="detail-value"><?= Html::encode($model->name) ?></div>
                        </div>
                        
                        <?php if (isset($model->description) && $model->description): ?>
                            <div class="detail-item">
                                <label class="detail-label">Opis:</label>
                                <div class="detail-value"><?= Html::encode($model->description) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- Parameters Section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list"></i> Parametry badania (<?= count($model->parameters) ?>)</h5>
                    <div>
                        <?php if (!empty($model->parameters)): ?>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="bulkEnableWarnings()">
                                <i class="fas fa-bell"></i> Włącz wszystkie ostrzeżenia
                            </button>
                        <?php endif; ?>
                        <?= Html::a('<i class="fas fa-plus"></i> Dodaj parametr', 
                            ['add-parameter', 'id' => $model->id], 
                            ['class' => 'btn btn-success btn-sm']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($model->parameters)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-plus-circle fa-3x mb-3"></i>
                            <h5>Brak parametrów</h5>
                            <p>Ten szablon nie ma jeszcze zdefiniowanych parametrów.</p>
                            <?= Html::a('<i class="fas fa-plus"></i> Dodaj pierwszy parametr', 
                                ['add-parameter', 'id' => $model->id], 
                                ['class' => 'btn btn-primary']) ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Parametr</th>
                                        <th>Jednostka</th>
                                        <th>Normy</th>
                                        <th>Ostrzeżenia</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($model->parameters as $parameter): ?>
                                        <tr>
                                            <td>
                                                <strong><?= Html::encode($parameter->name) ?></strong>
                                                <?php if (isset($parameter->description) && $parameter->description): ?>
                                                    <br><small class="text-muted"><?= Html::encode($parameter->description) ?></small>
                                                <?php endif; ?>
                                                <?php if ($parameter->type): ?>
                                                    <br><small class="badge badge-secondary"><?= Html::encode($parameter->type) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $parameter->unit ? Html::encode($parameter->unit) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td>
                                                <?php if (empty($parameter->norms)): ?>
                                                    <span class="badge bg-secondary">Brak norm</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?= count($parameter->norms) ?> norm(y)</span>
                                                    <?php if ($parameter->primaryNorm): ?>
                                                        <br><small class="text-muted">
                                                            Podstawowa: <?= Html::encode($parameter->primaryNorm->name) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $hasWarnings = false;
                                                foreach ($parameter->norms as $norm) {
                                                    if ($norm->warning_enabled) {
                                                        $hasWarnings = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if ($hasWarnings): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-bell"></i> Włączone
                                                    </span>
                                                <?php elseif (!empty($parameter->norms)): ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-bell-slash"></i> Wyłączone
                                                    </span>
                                                    <br>
                                                    <button type="button" class="btn btn-outline-warning btn-xs mt-1" 
                                                            onclick="quickEnableWarning(<?= $parameter->id ?>)">
                                                        <i class="fas fa-bolt"></i> Włącz
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <!-- Zarządzanie normami -->
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="openNormsSidebar(<?= $parameter->id ?>, '<?= Html::encode($parameter->name) ?>')">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    
                                                    <!-- Edytuj parametr -->
                                                    <?= Html::a('<i class="fas fa-edit"></i>', 
                                                        ['update-parameter', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                        ['class' => 'btn btn-outline-secondary btn-sm', 'title' => 'Edytuj parametr']) ?>
                                                    
                                                    <!-- Usuń parametr -->
                                                    <?= Html::a('<i class="fas fa-trash"></i>', 
                                                        ['delete-parameter', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                        [
                                                            'class' => 'btn btn-outline-danger btn-sm',
                                                            'title' => 'Usuń parametr',
                                                            'data-confirm' => 'Czy na pewno chcesz usunąć parametr "' . $parameter->name . '"? Ta operacja usunie również wszystkie powiązane normy.',
                                                            'data-method' => 'post'
                                                        ]) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar for Norms Management -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div id="normsSidebar" class="norms-sidebar">
    <div class="sidebar-header">
        <h5 id="sidebarTitle">Zarządzanie normami</h5>
        <button type="button" class="btn-close" onclick="closeNormsSidebar()"></button>
    </div>
    <div class="sidebar-body">
        <div id="sidebarContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<!-- CSS for Sidebar -->
<style>
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.sidebar-overlay.show {
    opacity: 1;
    visibility: visible;
}

.norms-sidebar {
    position: fixed;
    top: 0;
    right: -500px;
    width: 500px;
    height: 100%;
    background-color: white;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    transition: right 0.3s ease;
    overflow-y: auto;
}

.norms-sidebar.show {
    right: 0;
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
    display: flex;
    justify-content: between;
    align-items: center;
}

.sidebar-header h5 {
    margin: 0;
    flex-grow: 1;
}

.sidebar-body {
    padding: 0;
    height: calc(100% - 70px);
    overflow-y: auto;
}

.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    line-height: 1;
    border-radius: 0.2rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

@media (max-width: 768px) {
    .norms-sidebar {
        width: 100%;
        right: -100%;
    }
}
</style>

<script>
// Ensure MedArchive functions are available
document.addEventListener('DOMContentLoaded', function() {
    // Check if main JS is loaded
    if (typeof window.openNormsSidebar === 'undefined') {
        console.error('MedArchive main.js not loaded properly');
    }
});
</script>