<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Historia logowań';
$this->params['breadcrumbs'][] = ['label' => 'Profil', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-login-history">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do profilu', ['profile'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> Historia aktywności
            </h5>
        </div>
        <div class="card-body">
            <?php Pjax::begin(); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'attribute' => 'login_time',
                        'label' => 'Data i czas logowania',
                        'value' => function($model) {
                            return Yii::$app->formatter->asDatetime($model->login_time);
                        },
                    ],
                    [
                        'attribute' => 'logout_time',
                        'label' => 'Czas wylogowania',
                        'value' => function($model) {
                            return $model->logout_time ? Yii::$app->formatter->asDatetime($model->logout_time) : 'Brak danych';
                        },
                    ],
                    [
                        'attribute' => 'ip_address',
                        'label' => 'Adres IP',
                    ],
                    [
                        'attribute' => 'location',
                        'label' => 'Lokalizacja',
                        'value' => function($model) {
                            return $model->location ?: 'Nieznana';
                        },
                    ],
                    [
                        'attribute' => 'user_agent',
                        'label' => 'Przeglądarka',
                        'value' => function($model) {
                            // Wyciągnij podstawowe informacje o przeglądarce
                            $ua = $model->user_agent;
                            if (strpos($ua, 'Chrome') !== false) return 'Chrome';
                            if (strpos($ua, 'Firefox') !== false) return 'Firefox';
                            if (strpos($ua, 'Safari') !== false) return 'Safari';
                            if (strpos($ua, 'Edge') !== false) return 'Edge';
                            return 'Inna';
                        },
                    ],
                    [
                        'attribute' => 'success',
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->success 
                                ? '<span class="badge bg-success">Sukces</span>'
                                : '<span class="badge bg-danger">Błąd</span>';
                        },
                    ],
                ],
                'tableOptions' => ['class' => 'table table-striped table-hover'],
                'summary' => 'Wyświetlane {begin}-{end} z {totalCount} logowań.',
                'emptyText' => 'Brak historii logowań.',
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Ostatnie aktywności</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Informacja:</strong> Historia logowań jest przechowywana przez 90 dni ze względów bezpieczeństwa.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Bezpieczeństwo</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Jeśli zauważysz podejrzaną aktywność na swoim koncie, 
                        skontaktuj się natychmiast z administratorem.
                    </p>
                    <?= Html::a('<i class="fas fa-key"></i> Zmień hasło', ['change-password'], [
                        'class' => 'btn btn-warning btn-sm'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>