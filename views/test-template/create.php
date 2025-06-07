<?php

use yii\helpers\Html;
use app\assets\TestTemplateAsset;

TestTemplateAsset::register($this);

$this->title = 'Nowy szablon badania';
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-template-create">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do listy', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>