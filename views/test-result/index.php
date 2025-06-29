<?php
use yii\helpers\Html;
use yii\grid\GridView;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->registerMetaTag([
    'name' => 'csrf-param',
    'content' => Yii::$app->request->csrfParam,
]);
$this->registerMetaTag([
    'name' => 'csrf-token',
    'content' => Yii::$app->request->csrfToken,
]);

$this->title = 'Wyniki badań';
$this->params['breadcrumbs'][] = $this->title;
?>


<div class="test-result-index">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('Nowy wynik', ['create'], ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
    'summary' => 'Wyświetlono <b>{begin}-{end}</b> z <b>{totalCount}</b> wpisów',
        'emptyText' => 'Nie znaleziono wyników.',
        'options' => ['class' => 'table-responsive'],
        'tableOptions' => ['class' => 'table table-striped table-sm'],
        'columns' => [
            'id',
            [
                'attribute' => 'testTemplateName',
                'label' => 'Badanie',
                'value' => function($model) {
                    return $model->testTemplate->name;
                }
            ],
            [
                'attribute' => 'test_date',
                'format' => 'date',
                'filter' => Html::input('date', 'TestResultSearch[test_date]', $searchModel->test_date, ['class' => 'form-control'])
            ],
            [
                'label' => 'Kluczowe wartości',
                'format' => 'raw',
                'value' => function($model) {
                    $values = [];
                    $count = 0;
                    foreach ($model->resultValues as $resultValue) {
                        if ($count >= 3) break; // Pokazuj maksymalnie 3 wartości
                        
                        $value = $resultValue->value;
                        $unit = $resultValue->parameter->unit ? ' ' . $resultValue->parameter->unit : '';
                        
                        // Określ kolor na podstawie warning_level, nie tylko is_abnormal
                        $class = 'text-success'; // default
                        if ($resultValue->is_abnormal) {
                            $class = 'text-danger';
                        } elseif ($resultValue->warning_level) {
                            switch ($resultValue->warning_level) {
                                case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                                    $class = 'text-warning';
                                    break;
                                case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                                    $class = 'text-info';
                                    break;
                                case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL:
                                    $class = 'text-danger';
                                    break;
                            }
                        }
                        
                        $values[] = '<span class="' . $class . '">' . 
                                   Html::encode($resultValue->parameter->name) . ': ' . 
                                   Html::encode($value) . $unit . '</span>';
                        $count++;
                    }
                    
                    if (count($model->resultValues) > 3) {
                        $values[] = '<small class="text-muted">i ' . (count($model->resultValues) - 3) . ' więcej...</small>';
                    }
                    
                    return implode('<br>', $values);
                }
            ],
            [
    'attribute' => 'has_abnormal_values',
    'label' => 'Status',
    'format' => 'raw',
    'value' => function($model) {
        $status = $model->getDetailedStatus();
        
        $badge = '<span class="badge ' . $status['badge_class'] . '">';
        $badge .= '<i class="' . $status['icon'] . '"></i> ';
        $badge .= $status['message'];
        
        // Dodaj licznik ostrzeżeń jeśli są
        if ($status['warning_count'] > 0 && $status['status'] !== 'abnormal') {
            $badge .= ' (' . $status['warning_count'] . ')';
        }
        
        $badge .= '</span>';
        
        // Sprawdź czy wynik ma problemy (nieprawidłowy lub ostrzeżenia) i czy można zaplanować nowe badanie
        $hasProblems = ($status['status'] === 'abnormal' || $status['warning_count'] > 0);
        
        if ($hasProblems) {
            // Sprawdź czy nie ma już zaplanowanego badania z tego szablonu w przyszłości
            $existingQueueItem = \app\models\TestQueue::find()
                ->where([
                    'test_template_id' => $model->test_template_id,
                    'status' => 'pending'
                ])
                ->andWhere(['>', 'scheduled_date', date('Y-m-d')])
                ->exists();
            
            if (!$existingQueueItem) {
                $badge .= '<br><div class="mt-1">';
                $badge .= Html::a(
                    '<i class="fas fa-calendar-plus"></i> Zaplanuj ponowne', 
                    '#',
                    [
                        'class' => 'btn btn-outline-primary btn-xs schedule-retest-btn',
                        'data-template-id' => $model->test_template_id,
                        'data-template-name' => Html::encode($model->testTemplate->name),
                        'data-bs-toggle' => 'tooltip',
                        'title' => 'Zaplanuj ponowne badanie z tym szablonem'
                    ]
                );
                $badge .= '</div>';
            } else {
                $badge .= '<br><small class="text-muted mt-1">';
                $badge .= '<i class="fas fa-calendar-check"></i> Już zaplanowane';
                $badge .= '</small>';
            }
        }
        
        return $badge;
    },
    'filter' => Html::dropDownList('TestResultSearch[has_abnormal_values]', $searchModel->has_abnormal_values, [
        '' => 'Wszystkie',
        '1' => 'Nieprawidłowe',
        '0' => 'Prawidłowe'
    ], ['class' => 'form-control'])
],
            [
                'attribute' => 'comment',
                'value' => function($model) {
                    return $model->comment ? Html::encode(substr($model->comment, 0, 50)) . 
                           (strlen($model->comment) > 50 ? '...' : '') : '';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info',
                            'title' => 'Zobacz szczegóły'
                        ]);
                    },
                    'update' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-secondary',
                            'title' => 'Edytuj'
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Usuń',
                            'data-confirm' => 'Czy na pewno chcesz usunąć ten wynik?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>

<!-- CSS dla lepszych kolorów ostrzeżeń -->
<style>
.text-warning {
    color: #fd7e14 !important;
    font-weight: 500;
}

.text-info {
    color: #0dcaf0 !important;
    font-weight: 500;
}

.badge .fas {
    font-size: 0.75em;
}

.badge.bg-warning {
    color: #000 !important;
}
</style>

<?php
$this->registerJs("
$(document).ready(function() {
    // Inicjalizacja tooltipów
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Obsługa kliknięcia przycisku planowania
    \$(document).on('click', '.schedule-retest-btn', function(e) {
        e.preventDefault();
        
        var btn = \$(this);
        var templateId = btn.data('template-id');
        var templateName = btn.data('template-name');
        
        showScheduleModal(templateId, templateName);
    });
    
    // Obsługa przycisku submit w modal
    \$(document).on('click', '#submitScheduleBtn', function() {
        var templateId = \$(this).data('template-id');
        submitSchedule(templateId);
    });
});

function showScheduleModal(templateId, templateName) {
    // Usuń poprzedni modal jeśli istnieje
    \$('#scheduleModal').remove();
    
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    var minDate = tomorrow.toISOString().split('T')[0];
    
    var modalHtml = '<div class=\"modal fade\" id=\"scheduleModal\" tabindex=\"-1\">' +
        '<div class=\"modal-dialog\">' +
        '<div class=\"modal-content\">' +
        '<div class=\"modal-header\">' +
        '<h5 class=\"modal-title\"><i class=\"fas fa-calendar-plus\"></i> Zaplanuj ponowne badanie</h5>' +
        '<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"></button>' +
        '</div>' +
        '<div class=\"modal-body\">' +
        '<div class=\"alert alert-info\">' +
        '<i class=\"fas fa-info-circle\"></i> Planujesz ponowne badanie: <strong>' + templateName + '</strong>' +
        '</div>' +
        '<form id=\"scheduleForm\">' +
        '<div class=\"mb-3\">' +
        '<label for=\"scheduledDate\" class=\"form-label\">Data planowanego badania *</label>' +
        '<input type=\"date\" class=\"form-control\" id=\"scheduledDate\" min=\"' + minDate + '\" required>' +
        '<div class=\"form-text\">Wybierz datę w przyszłości</div>' +
        '</div>' +
        '<div class=\"mb-3\">' +
        '<label for=\"scheduleComment\" class=\"form-label\">Komentarz</label>' +
        '<textarea class=\"form-control\" id=\"scheduleComment\" rows=\"3\" placeholder=\"Powód ponownego badania...\"></textarea>' +
        '</div>' +
        '</form>' +
        '</div>' +
        '<div class=\"modal-footer\">' +
        '<button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Anuluj</button>' +
        '<button type=\"button\" class=\"btn btn-primary\" id=\"submitScheduleBtn\" data-template-id=\"' + templateId + '\">Zaplanuj badanie</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    \$('body').append(modalHtml);
    
    if (typeof bootstrap !== 'undefined') {
        var modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
        modal.show();
    }
}

function submitSchedule(templateId) {
    var scheduledDate = \$('#scheduledDate').val();
    var comment = \$('#scheduleComment').val();
    
    // Walidacja
    if (!scheduledDate) {
        \$('#scheduledDate').addClass('is-invalid');
        return;
    }
    
    var selectedDate = new Date(scheduledDate);
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate <= today) {
        \$('#scheduledDate').addClass('is-invalid');
        return;
    }
    
    var csrfToken = \$('meta[name=csrf-token]').attr('content');
    
    var requestData = {
        test_template_id: templateId,
        scheduled_date: scheduledDate,
        comment: comment,
        _csrf: csrfToken
    };
    
    // Wyślij request
    \$.ajax({
        url: '" . \yii\helpers\Url::to(['/test-queue/schedule-retest']) . "',
        type: 'POST',
        data: requestData,
        dataType: 'json',
        beforeSend: function() {
            \$('#submitScheduleBtn').prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Planowanie...');
        },
        success: function(response) {
            if (response.success) {
                \$('#scheduleModal').modal('hide');
                
                // Pokaż toast notification
                showToast('Sukces', 'Badanie zostało zaplanowane na ' + scheduledDate, 'success');
                
                // Odśwież stronę po 2 sekundach
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                showToast('Błąd', response.error || 'Wystąpił błąd podczas planowania', 'error');
            }
        },
        error: function(xhr) {
            var errorMsg = 'Wystąpił błąd połączenia';
            if (xhr.status === 404) {
                errorMsg = 'Endpoint nie został znaleziony';
            } else if (xhr.status === 403) {
                errorMsg = 'Brak uprawnień';
            }
            showToast('Błąd', errorMsg, 'error');
        },
        complete: function() {
            \$('#submitScheduleBtn').prop('disabled', false).html('<i class=\"fas fa-calendar-plus\"></i> Zaplanuj badanie');
        }
    });
}

// Toast notification function
function showToast(title, message, type) {
    var bgClass = type === 'error' ? 'bg-danger' : type === 'success' ? 'bg-success' : 'bg-info';
    var toastHtml = '<div class=\"toast align-items-center text-white ' + bgClass + ' border-0\" role=\"alert\">' +
        '<div class=\"d-flex\">' +
        '<div class=\"toast-body\"><strong>' + title + ':</strong> ' + message + '</div>' +
        '<button type=\"button\" class=\"btn-close btn-close-white me-2 m-auto\" data-bs-dismiss=\"toast\"></button>' +
        '</div>' +
        '</div>';
    
    if (\$('#toast-container').length === 0) {
        \$('body').append('<div id=\"toast-container\" class=\"toast-container position-fixed top-0 end-0 p-3\"></div>');
    }
    
    var toastElement = \$(toastHtml);
    \$('#toast-container').append(toastElement);
    
    var toast = new bootstrap.Toast(toastElement[0]);
    toast.show();
    
    toastElement.on('hidden.bs.toast', function() {
        \$(this).remove();
    });
}
");
?>