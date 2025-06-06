<?php
use yii\helpers\Html;
use app\assets\TestQueueAsset;

TestQueueAsset::register($this);

$this->title = 'Edytuj zaplanowane badanie: ' . $model->testTemplate->name;
$this->params['breadcrumbs'][] = ['label' => 'Kolejka badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Badanie #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edytuj';
?>

<div class="test-queue-update">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'templates' => [$model->testTemplate],
    ]) ?>
</div>