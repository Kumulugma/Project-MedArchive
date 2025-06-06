<?php
use yii\helpers\Html;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->title = 'Porównanie wyników: ' . $template->name;
$this->params['breadcrumbs'][] = ['label' => 'Wyniki badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-result-compare">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= Html::beginForm('', 'post', ['id' => 'compare-form']) ?>
    
    <div class="row">
        <div class="col-md-6">
            <h4>Dostępne wyniki</h4>
            <div class="form-check mb-2">
                <?= Html::checkbox('select_all', false, ['class' => 'form-check-input select-all-results', 'id' => 'select-all']) ?>
                <?= Html::label('Zaznacz wszystkie', 'select-all', ['class' => 'form-check-label']) ?>
            </div>
            
            <?php foreach ($results as $result): ?>
                <div class="form-check">
                    <?= Html::checkbox('selected_results[]', in_array($result->id, array_column($selectedResults, 'id')), [
                        'value' => $result->id,
                        'class' => 'form-check-input result-checkbox',
                        'id' => 'result-' . $result->id
                    ]) ?>
                    <?= Html::label(
                        Yii::$app->formatter->asDate($result->test_date) . 
                        ($result->has_abnormal_values ? ' ⚠️' : ''),
                        'result-' . $result->id,
                        ['class' => 'form-check-label']
                    ) ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="col-md-6">
            <h4>Parametry do porównania</h4>
            <?php foreach ($template->parameters as $parameter): ?>
                <div class="form-check">
                    <?= Html::checkbox('selected_parameters[]', false, [
                        'value' => $parameter->id,
                        'class' => 'form-check-input parameter-checkbox',
                        'id' => 'param-' . $parameter->id
                    ]) ?>
                    <?= Html::label(
                        $parameter->name . ($parameter->unit ? ' (' . $parameter->unit . ')' : ''),
                        'param-' . $parameter->id,
                        ['class' => 'form-check-label']
                    ) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mt-3">
        <?= Html::submitButton('Porównaj wyniki', ['class' => 'btn btn-primary']) ?>
        <?= Html::button('Generuj wykres', ['class' => 'btn btn-success', 'id' => 'generate-chart']) ?>
    </div>
    
    <?= Html::endForm() ?>

    <?php if (!empty($selectedResults)): ?>
        <div class="mt-4">
            <h4>Tabela porównawcza</h4>
            <div class="table-responsive">
                <table class="table table-sm comparison-table">
                    <thead>
                        <tr>
                            <th>Parametr</th>
                            <?php foreach ($selectedResults as $result): ?>
                                <th><?= Yii::$app->formatter->asDate($result->test_date) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($template->parameters as $parameter): ?>
                            <tr>
                                <td><strong><?= Html::encode($parameter->name) ?></strong></td>
                                <?php foreach ($selectedResults as $result): ?>
                                    <td>
                                        <?php
                                        $value = null;
                                        foreach ($result->resultValues as $resultValue) {
                                            if ($resultValue->parameter_id == $parameter->id) {
                                                $value = $resultValue;
                                                break;
                                            }
                                        }
                                        
                                        if ($value) {
                                            $class = $value->is_abnormal ? 'abnormal-value' : '';
                                            if ($value->abnormality_type === 'low') $class .= ' abnormal-low';
                                            if ($value->abnormality_type === 'high') $class .= ' abnormal-high';
                                            
                                            echo Html::tag('span', Html::encode($value->value), ['class' => $class]);
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div id="chart-container" style="display: none;">
        <h4>Wykres porównawczy</h4>
        <div class="chart-container">
            <canvas id="comparisonChart"></canvas>
        </div>
    </div>
</div>
