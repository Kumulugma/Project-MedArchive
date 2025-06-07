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
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-eye"></i> Podgląd', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do listy', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'templates' => [$model->testTemplate],
    ]) ?>
</div>