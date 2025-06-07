/**
 * Test Queue JavaScript
 * Specific functions for test queue management
 */

$(document).ready(function() {
    initializeTestQueue();
    initializeBulkOperations();
    initializeFormValidation();
    initializeDatePicker();
});

/**
 * Initialize test queue functionality
 */
function initializeTestQueue() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Highlight urgent/overdue tests
    highlightUrgentTests();
    
    // Initialize auto-complete for scheduled tests
    initializeAutoComplete();
    
    // Initialize status change handlers
    initializeStatusHandlers();
}

/**
 * Initialize bulk operations
 */
function initializeBulkOperations() {
    // Toggle bulk actions visibility
    $('.queue-checkbox').on('change', function() {
        var checkedCount = $('.queue-checkbox:checked').length;
        var $bulkBtn = $('#bulk-complete');
        
        if (checkedCount > 0) {
            $bulkBtn.show().text('Oznacz wybrane (' + checkedCount + ') jako wykonane');
        } else {
            $bulkBtn.hide();
        }
    });
    
    // Select all functionality
    $('#select-all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.queue-checkbox:not(:disabled)').prop('checked', isChecked).trigger('change');
    });
    
    // Bulk complete action
    $('#bulk-complete').on('click', function(e) {
        e.preventDefault();
        handleBulkComplete();
    });
}

/**
 * Handle bulk complete operation
 */
function handleBulkComplete() {
    var selected = [];
    $('.queue-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    
    if (selected.length === 0) {
        showNotification('Nie wybrano żadnych elementów!', 'warning');
        return;
    }
    
    var message = 'Czy oznaczyć wybrane badania (' + selected.length + ') jako wykonane?';
    if (confirm('🏥 MedArchive\n\n' + message)) {
        $('#bulk-complete').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Przetwarzanie...');
        
        $.post('/test-queue/bulk-complete', {
            ids: selected,
            _csrf: $('meta[name=csrf-token]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showNotification('Pomyślnie oznaczono ' + selected.length + ' badań jako wykonane', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Wystąpił błąd: ' + response.message, 'danger');
            }
        })
        .fail(function() {
            showNotification('Wystąpił błąd podczas przetwarzania żądania', 'danger');
        })
        .always(function() {
            $('#bulk-complete').prop('disabled', false).html('<i class="fas fa-check-double"></i> Oznacz wykonane');
        });
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    // Validate scheduled date
    $('#testqueue-scheduled_date').on('change', function() {
        validateScheduledDate($(this));
    });
    
    // Validate template selection
    $('#testqueue-test_template_id').on('change', function() {
        validateTemplateSelection($(this));
    });
    
    // Form submission validation
    $('.test-queue-form form').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
}

/**
 * Validate scheduled date
 */
function validateScheduledDate($input) {
    var selectedDate = new Date($input.val());
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    
    $input.removeClass('is-valid is-invalid');
    
    if (!$input.val()) {
        $input.addClass('is-invalid');
        showFieldError($input, 'Data badania jest wymagana');
        return false;
    }
    
    if (selectedDate < today) {
        $input.addClass('is-invalid');
        showFieldError($input, 'Nie można zaplanować badania na datę z przeszłości');
        return false;
    }
    
    // Check if date is weekend (optional warning)
    var dayOfWeek = selectedDate.getDay();
    if (dayOfWeek === 0 || dayOfWeek === 6) {
        $input.addClass('is-valid');
        showFieldWarning($input, 'Wybrana data przypada na weekend');
    } else {
        $input.addClass('is-valid');
        hideFieldMessage($input);
    }
    
    return true;
}

/**
 * Validate template selection
 */
function validateTemplateSelection($select) {
    $select.removeClass('is-valid is-invalid');
    
    if (!$select.val()) {
        $select.addClass('is-invalid');
        showFieldError($select, 'Wybór szablonu badania jest wymagany');
        return false;
    }
    
    $select.addClass('is-valid');
    hideFieldMessage($select);
    return true;
}

/**
 * Validate entire form
 */
function validateForm() {
    var isValid = true;
    
    isValid &= validateScheduledDate($('#testqueue-scheduled_date'));
    isValid &= validateTemplateSelection($('#testqueue-test_template_id'));
    
    return isValid;
}

/**
 * Show field error message
 */
function showFieldError($field, message) {
    hideFieldMessage($field);
    var $error = $('<div class="invalid-feedback d-block"><i class="fas fa-exclamation-circle"></i> ' + message + '</div>');
    $field.after($error);
}

/**
 * Show field warning message
 */
function showFieldWarning($field, message) {
    hideFieldMessage($field);
    var $warning = $('<div class="text-warning small mt-1"><i class="fas fa-exclamation-triangle"></i> ' + message + '</div>');
    $field.after($warning);
}

/**
 * Hide field message
 */
function hideFieldMessage($field) {
    $field.siblings('.invalid-feedback, .text-warning').remove();
}

/**
 * Initialize date picker enhancements
 */
function initializeDatePicker() {
    // Add keyboard navigation
    $('#testqueue-scheduled_date').on('keydown', function(e) {
        var $input = $(this);
        var currentDate = new Date($input.val() || new Date());
        
        switch(e.which) {
            case 38: // Arrow up - add 1 day
                e.preventDefault();
                currentDate.setDate(currentDate.getDate() + 1);
                $input.val(formatDate(currentDate)).trigger('change');
                break;
            case 40: // Arrow down - subtract 1 day
                e.preventDefault();
                currentDate.setDate(currentDate.getDate() - 1);
                $input.val(formatDate(currentDate)).trigger('change');
                break;
        }
    });
}

/**
 * Format date for input
 */
function formatDate(date) {
    var year = date.getFullYear();
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    return year + '-' + month + '-' + day;
}

/**
 * Highlight urgent/overdue tests
 */
function highlightUrgentTests() {
    // Add pulsing animation to overdue tests
    $('tr[data-overdue="true"]').each(function() {
        $(this).find('.badge').addClass('animate__animated animate__pulse animate__infinite');
    });
    
    // Add warning styling to urgent tests
    $('tr[data-urgent="true"]').each(function() {
        $(this).find('td:first').prepend('<i class="fas fa-exclamation-triangle text-warning me-2"></i>');
    });
}

/**
 * Initialize auto-complete functionality
 */
function initializeAutoComplete() {
    // Auto-complete for comments based on previous entries
    $('#testqueue-comment').on('focus', function() {
        // This could be extended to load common comments via AJAX
        var commonComments = [
            'Badanie kontrolne',
            'Pierwsza wizyta',
            'Badanie okresowe',
            'Badanie profilaktyczne',
            'Kontrola po leczeniu'
        ];
        
        $(this).autocomplete({
            source: commonComments,
            minLength: 2
        });
    });
}

/**
 * Initialize status change handlers
 */
function initializeStatusHandlers() {
    // Handle status changes
    $('.status-change').on('change', function() {
        var $select = $(this);
        var testId = $select.data('test-id');
        var newStatus = $select.val();
        
        updateTestStatus(testId, newStatus, $select);
    });
}

/**
 * Update test status via AJAX
 */
function updateTestStatus(testId, status, $element) {
    $element.prop('disabled', true);
    
    $.post('/test-queue/update-status', {
        id: testId,
        status: status,
        _csrf: $('meta[name=csrf-token]').attr('content')
    })
    .done(function(response) {
        if (response.success) {
            showNotification('Status został zaktualizowany', 'success');
            // Update row styling based on new status
            updateRowStyling($element.closest('tr'), status);
        } else {
            showNotification('Błąd aktualizacji: ' + response.message, 'danger');
            $element.val($element.data('original-value')); // Revert
        }
    })
    .fail(function() {
        showNotification('Wystąpił błąd podczas aktualizacji', 'danger');
        $element.val($element.data('original-value')); // Revert
    })
    .always(function() {
        $element.prop('disabled', false);
    });
}

/**
 * Update row styling based on status
 */
function updateRowStyling($row, status) {
    $row.removeClass('table-warning table-danger table-success');
    
    switch(status) {
        case 'completed':
            $row.addClass('table-success');
            break;
        case 'cancelled':
            $row.addClass('table-danger');
            break;
    }
}

/**
 * Show notification message
 */
function showNotification(message, type) {
    var alertClass = 'alert-' + type;
    var icon = type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle');
    
    var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show position-fixed" style="top: 80px; right: 20px; z-index: 1050; min-width: 300px;">' +
        '<i class="fas fa-' + icon + '"></i> ' + message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    
    $('body').append($alert);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $alert.alert('close');
    }, 5000);
}

/**
 * Export functions for external use
 */
window.TestQueue = {
    validateForm: validateForm,
    updateTestStatus: updateTestStatus,
    showNotification: showNotification,
    highlightUrgentTests: highlightUrgentTests
};