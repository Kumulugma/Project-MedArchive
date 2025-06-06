<?php
use yii\helpers\Html;
use app\assets\TestQueueAsset;

TestQueueAsset::register($this);

$this->title = 'Zaplanuj badanie';
$this->params['breadcrumbs'][] = ['label' => 'Kolejka badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-queue-create">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'templates' => $templates,
    ]) ?>
</div>
