<?php
use yii\helpers\Html;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->title = 'Nowy wynik badania';
$this->params['breadcrumbs'][] = ['label' => 'Wyniki badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-result-create">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'templates' => $templates,
    ]) ?>
</div>
