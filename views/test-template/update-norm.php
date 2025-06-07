<?php
use yii\helpers\Html;
use app\assets\TestTemplateAsset;

TestTemplateAsset::register($this);

$this->title = 'Edytuj normę: ' . $norm->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name, 'url' => ['view', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'Edytuj normę';
?>

<div class="update-norm">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do szablonu', ['view', 'id' => $template->id], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <?= $this->render('_norm_form', [
        'template' => $template,
        'parameter' => $parameter,
        'norm' => $norm,
    ]) ?>
</div>
