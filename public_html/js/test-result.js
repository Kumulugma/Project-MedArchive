$(document).ready(function() {
    // Template selection change
    $('#testresult-test_template_id').on('change', function() {
        var templateId = $(this).val();
        if (templateId) {
            loadTemplateParameters(templateId);
        } else {
            $('#parameters-container').empty();
        }
    });

    function loadTemplateParameters(templateId) {
        $.get('/test-result/get-template-parameters', { templateId: templateId })
            .done(function(data) {
                $('#parameters-container').html(data);
                initializeParameterFields();
            })
            .fail(function() {
                alert('Błąd podczas ładowania parametrów');
            });
    }

    function initializeParameterFields() {
        // Norm selection change
        $('.norm-select').on('change', function() {
            var $this = $(this);
            var normId = $this.val();
            var $valueInput = $this.closest('.parameter-group').find('.value-input');
            
            if (normId) {
                $valueInput.attr('data-norm-id', normId);
                validateValue($valueInput);
            }
        });

        // Value input change
        $('.value-input').on('input', function() {
            validateValue($(this));
        });
    }

    function validateValue($input) {
        var value = $input.val();
        var normId = $input.attr('data-norm-id');
        
        if (!value || !normId) {
            $input.removeClass('is-invalid is-valid abnormal-value');
            return;
        }

        $.post('/test-result/validate-value', {
            value: value,
            normId: normId
        }).done(function(response) {
            $input.removeClass('is-invalid is-valid abnormal-value');
            
            if (response.is_normal) {
                $input.addClass('is-valid');
            } else {
                $input.addClass('is-invalid abnormal-value');
                if (response.type === 'low') {
                    $input.addClass('abnormal-low');
                } else if (response.type === 'high') {
                    $input.addClass('abnormal-high');
                }
            }
            
            // Show validation message
            var $feedback = $input.siblings('.invalid-feedback');
            if (response.message) {
                $feedback.text(response.message);
            }
        });
    }

    // Comparison functionality
    $('.select-all-results').on('change', function() {
        var checked = $(this).prop('checked');
        $('.result-checkbox').prop('checked', checked);
    });

    $('#generate-chart').on('click', function() {
        var selectedResults = [];
        var selectedParameters = [];
        
        $('.result-checkbox:checked').each(function() {
            selectedResults.push($(this).val());
        });
        
        $('.parameter-checkbox:checked').each(function() {
            selectedParameters.push($(this).val());
        });
        
        if (selectedResults.length === 0 || selectedParameters.length === 0) {
            alert('Wybierz wyniki i parametry do porównania');
            return;
        }
        
        generateComparisonChart(selectedResults, selectedParameters);
    });

    function generateComparisonChart(resultIds, parameterIds) {
        $.post('/test-result/get-chart-data', {
            resultIds: resultIds,
            parameterIds: parameterIds
        }).done(function(response) {
            if (response.success) {
                createChart(response.data);
            } else {
                alert('Błąd podczas generowania wykresu');
            }
        });
    }

    function createChart(data) {
        var ctx = document.getElementById('comparisonChart');
        if (!ctx) return;

        if (window.comparisonChart) {
            window.comparisonChart.destroy();
        }

        var datasets = [];
        var colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
        ];

        data.parameters.forEach(function(parameter, index) {
            datasets.push({
                label: parameter.name + (parameter.unit ? ' (' + parameter.unit + ')' : ''),
                data: parameter.values,
                borderColor: colors[index % colors.length],
                backgroundColor: colors[index % colors.length] + '20',
                fill: false,
                tension: 0.1
            });
        });

        window.comparisonChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Porównanie wyników badań'
                    }
                }
            }
        });

        $('#chart-container').show();
    }
});
