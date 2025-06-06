<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';
?>

<div class="dashboard-index">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Wszystkie wyniki
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalResults ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Szablony badań
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalTemplates ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-medical fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Nadchodzące badania
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($upcomingTests) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Wyniki nieprawidłowe
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $abnormalResults ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Nadchodzące badania</h6>
                    <a href="<?= Url::to(['/test-queue/index']) ?>" class="btn btn-sm btn-primary">Zobacz wszystkie</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingTests)): ?>
                        <p class="text-muted">Brak zaplanowanych badań</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Badanie</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingTests as $test): ?>
                                        <tr>
                                            <td><?= Html::encode($test->testTemplate->name) ?></td>
                                            <td><?= Yii::$app->formatter->asDate($test->scheduled_date) ?></td>
                                            <td>
                                                <?php if ($test->isDue()): ?>
                                                    <span class="badge bg-warning">Pilne</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= $test->getStatusOptions()[$test->status] ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Ostatnie wyniki</h6>
                    <a href="<?= Url::to(['/test-result/index']) ?>" class="btn btn-sm btn-primary">Zobacz wszystkie</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentResults)): ?>
                        <p class="text-muted">Brak wyników badań</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Badanie</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentResults as $result): ?>
                                        <tr>
                                            <td><?= Html::encode($result->testTemplate->name) ?></td>
                                            <td><?= Yii::$app->formatter->asDate($result->test_date) ?></td>
                                            <td>
                                                <?php if ($result->has_abnormal_values): ?>
                                                    <span class="badge bg-danger">Nieprawidłowe</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Prawidłowe</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Szybkie akcje</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="<?= Url::to(['/test-result/create']) ?>" class="btn btn-success btn-block">
                                <i class="fas fa-plus"></i> Dodaj wynik badania
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= Url::to(['/test-template/create']) ?>" class="btn btn-info btn-block">
                                <i class="fas fa-file-medical"></i> Nowy szablon badania
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= Url::to(['/test-queue/create']) ?>" class="btn btn-warning btn-block">
                                <i class="fas fa-calendar-plus"></i> Zaplanuj badanie
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= Url::to(['/test-result/index', 'filter' => 'abnormal']) ?>" class="btn btn-danger btn-block">
                                <i class="fas fa-exclamation-triangle"></i> Nieprawidłowe wyniki
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>