<?php

use yii\helpers\Html;
use app\assets\TestTemplateAsset;

/* @var $this yii\web\View */
/* @var $template app\models\TestTemplate */
/* @var $parameter app\models\TestParameter */
/* @var $norm app\models\ParameterNorm */

TestTemplateAsset::register($this);

$this->title = 'Dodaj normę: ' . $parameter->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name, 'url' => ['view', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'Nowa norma';
?>

<div class="add-norm">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i class="fas fa-plus"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do szablonu', ['view', 'id' => $template->id], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="alert-container"></div>

    <?= $this->render('_enhanced_norm_form', [
        'template' => $template,
        'parameter' => $parameter,
        'norm' => $norm,
    ]) ?>
</div>