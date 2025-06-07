<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\assets\DashboardAsset;

DashboardAsset::register($this);

$this->title = 'Dashboard - MedArchive';
$this->params['breadcrumbs'][] = 'Dashboard';
?>

<div class="dashboard-index">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <?= Html::a('<i class="fas fa-plus"></i> Nowy wynik badania', ['/test-result/create'], ['class' => 'btn btn-success']) ?>
                    <?= Html::a('<i class="fas fa-calendar-plus"></i> Zaplanuj badanie', ['/test-queue/create'], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statystyki -->
    <div class="row dashboard-stats mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-medical-primary text-uppercase mb-1">
                                Łączna liczba wyników
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalResults ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-medical-primary"></i>
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
                            <div class="text-xs font-weight-bold text-medical-accent text-uppercase mb-1">
                                Aktywne szablony
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalTemplates ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-medical fa-2x text-medical-accent"></i>
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
                                Nieprawidłowe wyniki
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $abnormalResults ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
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
                                Zaplanowane badania
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($upcomingTests) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Najbliższe badania -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-medical-primary">
                        <i class="fas fa-calendar-check"></i> Najbliższe badania
                    </h6>
                    <a href="<?= Url::to(['/test-queue/index']) ?>" class="btn btn-sm btn-primary">Zobacz wszystkie</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingTests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Brak zaplanowanych badań w najbliższym czasie</p>
                            <?= Html::a('<i class="fas fa-plus"></i> Zaplanuj badanie', ['/test-queue/create'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th data-label="Badanie">Badanie</th>
                                        <th data-label="Data">Data</th>
                                        <th data-label="Status">Status</th>
                                        <th data-label="Akcje">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingTests as $test): ?>
                                        <tr <?= $test->isDue() ? 'data-urgent="true"' : '' ?>>
                                            <td data-label="Badanie"><?= Html::encode($test->testTemplate->name) ?></td>
                                            <td data-label="Data"><?= Yii::$app->formatter->asDate($test->scheduled_date) ?></td>
                                            <td data-label="Status">
                                                <?php if ($test->isDue()): ?>
                                                    <span class="badge bg-warning"><i class="fas fa-clock"></i> Pilne</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= $test->getStatusOptions()[$test->status] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Akcje">
                                                <?= Html::a('<i class="fas fa-eye"></i>', ['/test-queue/view', 'id' => $test->id], [
                                                    'class' => 'btn btn-outline-primary btn-sm',
                                                    'title' => 'Podgląd',
                                                    'data-bs-toggle' => 'tooltip'
                                                ]) ?>
                                                <?= Html::a('<i class="fas fa-check"></i>', ['/test-queue/complete', 'id' => $test->id], [
                                                    'class' => 'btn btn-outline-success btn-sm',
                                                    'title' => 'Oznacz jako wykonane',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'data-method' => 'post',
                                                    'data-confirm' => 'Czy oznaczyć jako wykonane?'
                                                ]) ?>
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

        <!-- Ostatnie wyniki -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-medical-primary">
                        <i class="fas fa-flask"></i> Ostatnie wyniki badań
                    </h6>
                    <a href="<?= Url::to(['/test-result/index']) ?>" class="btn btn-sm btn-primary">Zobacz wszystkie</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentResults)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-vial fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Brak wyników badań</p>
                            <?= Html::a('<i class="fas fa-plus"></i> Dodaj wynik', ['/test-result/create'], ['class' => 'btn btn-success']) ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th data-label="Badanie">Badanie</th>
                                        <th data-label="Data">Data</th>
                                        <th data-label="Status">Status</th>
                                        <th data-label="Akcje">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentResults as $result): ?>
                                        <tr <?= $result->has_abnormal_values ? 'data-abnormal="true"' : '' ?>>
                                            <td data-label="Badanie"><?= Html::encode($result->testTemplate->name) ?></td>
                                            <td data-label="Data"><?= Yii::$app->formatter->asDate($result->test_date) ?></td>
                                            <td data-label="Status">
                                                <?php if ($result->has_abnormal_values): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Nieprawidłowe</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Prawidłowe</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Akcje">
                                                <?= Html::a('<i class="fas fa-eye"></i>', ['/test-result/view', 'id' => $result->id], [
                                                    'class' => 'btn btn-outline-primary btn-sm',
                                                    'title' => 'Podgląd',
                                                    'data-bs-toggle' => 'tooltip'
                                                ]) ?>
                                                <?= Html::a('<i class="fas fa-chart-line"></i>', ['/test-result/compare', 'templateId' => $result->test_template_id], [
                                                    'class' => 'btn btn-outline-info btn-sm',
                                                    'title' => 'Porównaj',
                                                    'data-bs-toggle' => 'tooltip'
                                                ]) ?>
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

    <!-- Wykres statystyk -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-medical-primary">
                        <i class="fas fa-chart-area"></i> Statystyki badań (ostatnie 30 dni)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="statisticsChart" width="100%" height="30"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard chart initialization
$(document).ready(function() {
    // Inicjalizuj wykres statystyk
    var ctx = document.getElementById('statisticsChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($i) { return date('d.m', strtotime("-$i days")); }, range(29, 0))) ?>,
            datasets: [{
                label: 'Liczba badań',
                data: [<?= implode(',', array_fill(0, 30, rand(0, 10))) ?>], // Przykładowe dane
                borderColor: 'rgb(44, 90, 160)',
                backgroundColor: 'rgba(44, 90, 160, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>