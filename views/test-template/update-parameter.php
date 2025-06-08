<?php

use yii\helpers\Html;
use app\assets\TestTemplateAsset;

/* @var $this yii\web\View */
/* @var $template app\models\TestTemplate */
/* @var $parameter app\models\TestParameter */

TestTemplateAsset::register($this);

$this->title = 'Edytuj parametr: ' . $parameter->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name, 'url' => ['view', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'Edytuj parametr';
?>

<div class="update-parameter">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i class="fas fa-edit"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do szablonu', ['view', 'id' => $template->id], ['class' => 'btn btn-outline-secondary']) ?>
                
                <?php if (!empty($parameter->norms)): ?>
                    <button type="button" class="btn btn-outline-info ms-2" 
                            onclick="openNormsSidebar(<?= $parameter->id ?>, '<?= Html::encode($parameter->name) ?>')">
                        <i class="fas fa-cog"></i> Zarządzaj normami (<?= count($parameter->norms) ?>)
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="alert-container"></div>

    <?= $this->render('_parameter_form', [
        'template' => $template,
        'parameter' => $parameter,
    ]) ?>
</div>

<!-- Sidebar for Norms Management (if parameter has norms) -->
<?php if (!empty($parameter->norms)): ?>
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
    justify-content: space-between;
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

@media (max-width: 768px) {
    .norms-sidebar {
        width: 100%;
        right: -100%;
    }
}
</style>
<?php endif; ?>