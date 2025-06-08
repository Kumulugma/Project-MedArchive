/**
 * Enhanced Test Result JavaScript
 * Handles comparison functionality, charts, and interactions
 */

$(document).ready(function() {
    initializeComparisonPage();
    initializeFormValidation();
    initializeChartFunctionality();
    initializeTooltips();
});

/**
 * Initialize comparison page functionality
 */
function initializeComparisonPage() {
    // Zaznacz wszystkie wyniki
    $('#select-all').change(function() {
        const isChecked = $(this).is(':checked');
        $('.result-checkbox').prop('checked', isChecked);
        updateResultSelection();
    });
    
    // Zaznacz wszystkie parametry
    $('#select-all-params').change(function() {
        const isChecked = $(this).is(':checked');
        $('.parameter-checkbox').prop('checked', isChecked);
        updateParameterSelection();
    });
    
    // Obsługa individual checkboxes
    $('.result-checkbox').change(function() {
        updateResultSelection();
        updateSelectAllState('.result-checkbox', '#select-all');
    });
    
    $('.parameter-checkbox').change(function() {
        updateParameterSelection();
        updateSelectAllState('.parameter-checkbox', '#select-all-params');
    });
    
    // Highlight selected items
    updateResultSelection();
    updateParameterSelection();
}

/**
 * Update visual state of result selection
 */
function updateResultSelection() {
    $('.result-checkbox').each(function() {
        const $container = $(this).closest('.form-check');
        if ($(this).is(':checked')) {
            $container.addClass('selected').removeClass('border');
        } else {
            $container.removeClass('selected').addClass('border');
        }
    });
}

/**
 * Update visual state of parameter selection
 */
function updateParameterSelection() {
    $('.parameter-checkbox').each(function() {
        const $container = $(this).closest('.form-check');
        if ($(this).is(':checked')) {
            $container.addClass('selected').removeClass('border');
        } else {
            $container.removeClass('selected').addClass('border');
        }
    });
}

/**
 * Update "select all" checkbox state
 */
function updateSelectAllState(checkboxSelector, selectAllSelector) {
    const totalBoxes = $(checkboxSelector).length;
    const checkedBoxes = $(checkboxSelector + ':checked').length;
    const $selectAll = $(selectAllSelector);
    
    if (checkedBoxes === 0) {
        $selectAll.prop('indeterminate', false).prop('checked', false);
    } else if (checkedBoxes === totalBoxes) {
        $selectAll.prop('indeterminate', false).prop('checked', true);
    } else {
        $selectAll.prop('indeterminate', true).prop('checked', false);
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    // Validate form submission
    $('#compare-form').submit(function(e) {
        const selectedResults = $('.result-checkbox:checked').length;
        const selectedParameters = $('.parameter-checkbox:checked').length;
        
        if (selectedResults === 0) {
            e.preventDefault();
            showAlert('Wybierz co najmniej jeden wynik do porównania', 'warning');
            return false;
        }
        
        if (selectedParameters === 0) {
            e.preventDefault();
            showAlert('Wybierz co najmniej jeden parametr do porównania', 'warning');
            return false;
        }
        
        if (selectedResults < 2) {
            const proceed = confirm('Wybrano tylko jeden wynik. Czy kontynuować?');
            if (!proceed) {
                e.preventDefault();
                return false;
            }
        }
        
        // Show loading indicator
        showLoadingIndicator('Generowanie porównania...');
        return true;
    });
}

/**
 * Initialize chart functionality
 */
function initializeChartFunctionality() {
    // Generate chart button
    $('#generate-chart').click(function(e) {
        e.preventDefault();
        generateChart();
    });
    
    // Download chart button
    $('#download-chart').click(function(e) {
        e.preventDefault();
        downloadChart();
    });
    
    // Print comparison button
    $('#print-comparison').click(function(e) {
        e.preventDefault();
        printComparison();
    });
}

/**
 * Generate comparison chart
 */
function generateChart() {
    const selectedResults = [];
    const selectedParameters = [];
    
    $('.result-checkbox:checked').each(function() {
        selectedResults.push($(this).val());
    });
    
    $('.parameter-checkbox:checked').each(function() {
        selectedParameters.push($(this).val());
    });
    
    // Validation
    if (selectedResults.length === 0) {
        showAlert('Wybierz co najmniej jeden wynik', 'warning');
        return;
    }
    
    if (selectedParameters.length === 0) {
        showAlert('Wybierz co najmniej jeden parametr', 'warning');
        return;
    }
    
    if (selectedParameters.length > 6) {
        const proceed = confirm('Wybrano wiele parametrów (' + selectedParameters.length + '). Wykres może być trudny do odczytania. Kontynuować?');
        if (!proceed) return;
    }
    
    // Show loading
    $('#generate-chart').prop('disabled', true).html('<span class="loading-spinner"></span> Generowanie...');
    
    // Generate chart
    generateComparisonChart(selectedResults, selectedParameters);
}

/**
 * Generate comparison chart with data
 */
function generateComparisonChart(resultIds, parameterIds) {
    $.ajax({
        url: '/test-results/get-chart-data',
        method: 'POST',
        data: {
            resultIds: resultIds,
            parameterIds: parameterIds
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            createChart(response.data);
            showAlert('Wykres został wygenerowany pomyślnie', 'success');
        } else {
            showAlert('Błąd podczas generowania wykresu: ' + (response.error || 'Nieznany błąd'), 'danger');
        }
    })
    .fail(function(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        showAlert('Błąd połączenia z serwerem. Sprawdź połączenie internetowe.', 'danger');
    })
    .always(function() {
        $('#generate-chart').prop('disabled', false).html('<i class="fas fa-chart-line me-2"></i>Generuj wykres');
    });
}

/**
 * Create Chart.js chart
 */
function createChart(data) {
    const ctx = document.getElementById('comparisonChart');
    if (!ctx) {
        console.error('Element canvas nie został znaleziony');
        showAlert('Błąd: Nie można znaleźć elementu wykresu', 'danger');
        return;
    }

    // Destroy existing chart
    if (window.comparisonChart) {
        window.comparisonChart.destroy();
    }

    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6B6B', '#4ECDC4',
        '#45B7D1', '#96CEB4', '#FECA57', '#FF9FF3',
        '#A8E6CF', '#FFD93D', '#6BCF7F', '#4D4D4D'
    ];

    const datasets = [];

    data.parameters.forEach(function(parameter, index) {
        const color = colors[index % colors.length];
        
        datasets.push({
            label: parameter.name + (parameter.unit ? ' (' + parameter.unit + ')' : ''),
            data: parameter.values,
            borderColor: color,
            backgroundColor: color + '20',
            fill: false,
            tension: 0.2,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: color,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: color,
            pointHoverBorderWidth: 3
        });
    });

    const config = {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: '#e9ecef',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        callback: function(value) {
                            return parseFloat(value).toFixed(2);
                        }
                    }
                },
                x: {
                    grid: {
                        color: '#e9ecef',
                        borderDash: [3, 3]
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                title: {
                    display: true,
                    text: 'Porównanie wyników badań w czasie',
                    font: {
                        size: 18,
                        weight: 'bold'
                    },
                    padding: 20
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    cornerRadius: 6,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y;
                            return label + ': ' + (value !== null ? parseFloat(value).toFixed(2) : 'brak danych');
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    };

    window.comparisonChart = new Chart(ctx, config);
    
    // Show chart container with animation
    $('#chart-container').fadeIn(500);
    
    // Scroll to chart
    $('html, body').animate({
        scrollTop: $('#chart-container').offset().top - 100
    }, 800);
}

/**
 * Download chart as image
 */
function downloadChart() {
    if (!window.comparisonChart) {
        showAlert('Najpierw wygeneruj wykres', 'warning');
        return;
    }
    
    try {
        const link = document.createElement('a');
        link.download = 'porownanie-wynikow-' + new Date().toISOString().slice(0, 10) + '.png';
        link.href = window.comparisonChart.toBase64Image('image/png', 1.0);
        link.click();
        
        showAlert('Wykres został pobrany', 'success');
    } catch (error) {
        console.error('Download error:', error);
        showAlert('Błąd podczas pobierania wykresu', 'danger');
    }
}

/**
 * Print comparison
 */
function printComparison() {
    // Hide chart temporarily if visible
    const chartVisible = $('#chart-container').is(':visible');
    if (chartVisible) {
        $('#chart-container').hide();
    }
    
    window.print();
    
    // Restore chart visibility
    if (chartVisible) {
        $('#chart-container').show();
    }
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    // Initialize Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize custom tooltips for values
    $('.value-tooltip').each(function() {
        $(this).tooltip({
            placement: 'top',
            trigger: 'hover'
        });
    });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info', duration = 5000) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto dismiss
    if (duration > 0) {
        setTimeout(function() {
            $('.alert').last().fadeOut(500, function() {
                $(this).remove();
            });
        }, duration);
    }
}

/**
 * Get icon for alert type
 */
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Show loading indicator
 */
function showLoadingIndicator(message = 'Ładowanie...') {
    const loadingHtml = `
        <div id="loading-overlay" class="position-fixed w-100 h-100 d-flex align-items-center justify-content-center" 
             style="top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="bg-white p-4 rounded text-center">
                <div class="loading-spinner mb-3"></div>
                <div>${message}</div>
            </div>
        </div>
    `;
    
    $('body').append(loadingHtml);
}

/**
 * Hide loading indicator
 */
function hideLoadingIndicator() {
    $('#loading-overlay').fadeOut(300, function() {
        $(this).remove();
    });
}

// Hide loading on page load completion
$(window).on('load', function() {
    hideLoadingIndicator();
});

// Handle form submissions with loading
$(document).ajaxStart(function() {
    // You can add global AJAX loading here if needed
}).ajaxStop(function() {
    // Hide global AJAX loading here if needed
});