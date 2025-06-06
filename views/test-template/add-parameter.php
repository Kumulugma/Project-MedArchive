<?php
use yii\helpers\Html;
use app\assets\TestTemplateAsset;

TestTemplateAsset::register($this);

$this->title = 'Dodaj parametr: ' . $template->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name, 'url' => ['view', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'Nowy parametr';
?>

<div class="add-parameter">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_parameter_form', [
        'template' => $template,
        'parameter' => $parameter,
    ]) ?>
</div>