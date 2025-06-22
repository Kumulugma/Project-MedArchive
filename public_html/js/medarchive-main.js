/**
 * MedArchive - Main JavaScript Module
 * Zarządzanie normami w szablonach badań
 */

// Globalne zmienne
window.currentTemplateId = null;
window.currentParameterId = null;

// Utility functions
window.getTemplateIdFromUrl = function() {
    const match = window.location.pathname.match(/test-templates\/(\d+)/);
    return match ? parseInt(match[1]) : null;
};

window.getCsrfToken = function() {
    return $('meta[name=csrf-token]').attr('content');
};

/**
 * SIDEBAR MANAGEMENT - Główny system zarządzania normami
 */
window.openNormsSidebar = function(parameterId, parameterName) {
    console.log('openNormsSidebar called with:', {parameterId, parameterName});
    
    const templateId = window.getTemplateIdFromUrl();
    if (!templateId) {
        console.error('Cannot find template ID');
        return;
    }

    // Store current context
    window.currentTemplateId = templateId;
    window.currentParameterId = parameterId;
    
    // Set sidebar title
    $('#sidebarTitle').html('<i class="fas fa-cog"></i> Zarządzanie normami - ' + parameterName);
    
    // Show overlay and sidebar
    $('#sidebarOverlay').addClass('show');
    $('#normsSidebar').addClass('show');
    
    // Load content
    window.loadNormsContent(parameterId, templateId);
};

window.closeNormsSidebar = function() {
    $('#normsSidebar').removeClass('show');
    $('#sidebarOverlay').removeClass('show');
    
    // Clear context
    window.currentTemplateId = null;
    window.currentParameterId = null;
};

window.loadNormsContent = function(parameterId, templateId) {
    $('#sidebarContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Ładowanie...</span>
            </div>
            <p class="mt-2">Ładowanie norm...</p>
        </div>
    `);
    
    $.get('/test-templates/' + templateId + '/get-parameter-norms', {
        parameterId: parameterId
    })
    .done(function(data) {
        $('#sidebarContent').html(data);
    })
    .fail(function(xhr, status, error) {
        console.error('Load norms failed:', {xhr, status, error});
        $('#sidebarContent').html(`
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle"></i>
                Błąd podczas ładowania norm: ${error}
                <br><small>Status: ${status}</small>
            </div>
        `);
    });
};

/**
 * NORM OPERATIONS - Operacje na normach
 */
window.deleteNormFromSidebar = function(normId, parameterId, normName) {
    console.log('deleteNormFromSidebar called with:', {normId, parameterId, normName});
    
    const templateId = window.currentTemplateId || window.getTemplateIdFromUrl();
    if (!templateId) {
        console.error('Cannot find template ID');
        window.showSidebarAlert('danger', 'Błąd: Nie można określić ID szablonu');
        return;
    }
    
    // Convert to numbers and validate
    normId = parseInt(normId);
    parameterId = parseInt(parameterId);
    
    if (!normId || !parameterId || isNaN(normId) || isNaN(parameterId)) {
        window.showSidebarAlert('danger', 'Błąd: Nieprawidłowe parametry');
        console.error('Invalid parameters:', {normId, parameterId, normName});
        return;
    }
    
    if (confirm('Czy na pewno chcesz usunąć normę "' + normName + '"?\n\nTa operacja jest nieodwracalna.')) {
        // Show loading state
        const deleteBtn = $(`button[data-norm-id="${normId}"]`);
        const originalText = deleteBtn.html();
        deleteBtn.html('<i class="fas fa-spinner fa-spin"></i> Usuwanie...').prop('disabled', true);
        
        $.post('/test-templates/delete-norm-ajax', {
            id: templateId,
            parameterId: parameterId,
            normId: normId,
            _csrf: window.getCsrfToken()
        })
        .done(function(response) {
            console.log('Delete response:', response);
            if (response && response.success) {
                window.showSidebarAlert('success', 'Norma została usunięta.');
                // Reload content after short delay
                setTimeout(function() {
                    window.loadNormsContent(parameterId, templateId);
                }, 1000);
                // Reload page after longer delay to update main view
                setTimeout(function() {
                    location.reload();
                }, 3000);
            } else {
                window.showSidebarAlert('danger', 'Błąd: ' + (response && response.message ? response.message : 'Nie udało się usunąć normy.'));
                // Restore button
                deleteBtn.html(originalText).prop('disabled', false);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Delete failed:', {xhr, status, error});
            window.showSidebarAlert('danger', 'Błąd komunikacji z serwerem: ' + error);
            // Restore button
            deleteBtn.html(originalText).prop('disabled', false);
        });
    }
};

window.enableWarningsFromSidebar = function(normId, parameterId) {
    console.log('enableWarningsFromSidebar called with:', {normId, parameterId});
    
    const templateId = window.currentTemplateId || window.getTemplateIdFromUrl();
    if (!templateId) {
        console.error('Cannot find template ID');
        window.showSidebarAlert('danger', 'Błąd: Nie można określić ID szablonu');
        return;
    }
    
    // Convert to numbers and validate
    normId = parseInt(normId);
    parameterId = parseInt(parameterId);
    
    if (!normId || !parameterId || isNaN(normId) || isNaN(parameterId)) {
        window.showSidebarAlert('danger', 'Błąd: Nieprawidłowe parametry');
        console.error('Invalid parameters:', {normId, parameterId});
        return;
    }
    
    // Show loading state
    const enableBtn = $(`button[data-norm-id="${normId}"][onclick*="enableWarnings"]`);
    const originalText = enableBtn.html();
    enableBtn.html('<i class="fas fa-spinner fa-spin"></i> Włączanie...').prop('disabled', true);
    
    $.post('/test-templates/enable-norm-warnings', {
        id: templateId,
        parameterId: parameterId,
        normId: normId,
        _csrf: window.getCsrfToken()
    })
    .done(function(response) {
        console.log('Enable warnings response:', response);
        if (response && response.success) {
            window.showSidebarAlert('success', 'Ostrzeżenia zostały włączone.');
            // Reload content after short delay
            setTimeout(function() {
                window.loadNormsContent(parameterId, templateId);
            }, 1000);
            // Reload page after longer delay to update main view
            setTimeout(function() {
                location.reload();
            }, 3000);
        } else {
            window.showSidebarAlert('danger', 'Błąd: ' + (response && response.message ? response.message : 'Nie udało się włączyć ostrzeżeń.'));
            // Restore button
            enableBtn.html(originalText).prop('disabled', false);
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Enable warnings failed:', {xhr, status, error});
        window.showSidebarAlert('danger', 'Błąd komunikacji z serwerem: ' + error);
        // Restore button
        enableBtn.html(originalText).prop('disabled', false);
    });
};

window.disableWarningsFromSidebar = function(normId, parameterId) {
    console.log('disableWarningsFromSidebar called with:', {normId, parameterId});
    
    const templateId = window.currentTemplateId || window.getTemplateIdFromUrl();
    if (!templateId) {
        console.error('Cannot find template ID');
        window.showSidebarAlert('danger', 'Błąd: Nie można określić ID szablonu');
        return;
    }
    
    // Convert to numbers and validate
    normId = parseInt(normId);
    parameterId = parseInt(parameterId);
    
    if (!normId || !parameterId || isNaN(normId) || isNaN(parameterId)) {
        window.showSidebarAlert('danger', 'Błąd: Nieprawidłowe parametry');
        console.error('Invalid parameters:', {normId, parameterId});
        return;
    }
    
    if (confirm('Czy na pewno chcesz wyłączyć ostrzeżenia dla tej normy?')) {
        // Show loading state
        const disableBtn = $(`button[data-norm-id="${normId}"][onclick*="disableWarnings"]`);
        const originalText = disableBtn.html();
        disableBtn.html('<i class="fas fa-spinner fa-spin"></i> Wyłączanie...').prop('disabled', true);
        
        $.post('/test-templates/disable-norm-warnings', {
            id: templateId,
            parameterId: parameterId,
            normId: normId,
            _csrf: window.getCsrfToken()
        })
        .done(function(response) {
            console.log('Disable warnings response:', response);
            if (response && response.success) {
                window.showSidebarAlert('success', 'Ostrzeżenia zostały wyłączone.');
                // Reload content after short delay
                setTimeout(function() {
                    window.loadNormsContent(parameterId, templateId);
                }, 1000);
                // Reload page after longer delay to update main view
                setTimeout(function() {
                    location.reload();
                }, 3000);
            } else {
                window.showSidebarAlert('danger', 'Błąd: ' + (response && response.message ? response.message : 'Nie udało się wyłączyć ostrzeżeń.'));
                // Restore button
                disableBtn.html(originalText).prop('disabled', false);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Disable warnings failed:', {xhr, status, error});
            window.showSidebarAlert('danger', 'Błąd komunikacji z serwerem: ' + error);
            // Restore button
            disableBtn.html(originalText).prop('disabled', false);
        });
    }
};

/**
 * QUICK ACTIONS - Szybkie akcje
 */
window.quickEnableWarning = function(parameterId) {
    console.log('quickEnableWarning called for parameter:', parameterId);
    
    const templateId = window.getTemplateIdFromUrl();
    if (!templateId) {
        console.error('Cannot find template ID');
        alert('Błąd: Nie można określić ID szablonu');
        return;
    }
    
    parameterId = parseInt(parameterId);
    if (!parameterId || isNaN(parameterId)) {
        alert('Błąd: Nieprawidłowy ID parametru');
        return;
    }
    
    // Show loading state
    const quickBtn = $(`button[onclick*="quickEnableWarning(${parameterId})"]`);
    const originalText = quickBtn.html();
    quickBtn.html('<i class="fas fa-spinner fa-spin"></i> Włączanie...').prop('disabled', true);
    
    $.post('/test-templates/' + templateId + '/quick-enable-warning/' + parameterId, {
        _csrf: window.getCsrfToken()
    })
    .done(function(response) {
        console.log('Quick enable response:', response);
        if (response && response.success) {
            // Show success message
            window.showPageAlert('success', response.message);
            // Reload page after short delay
            setTimeout(function() {
                location.reload();
            }, 2000);
        } else {
            window.showPageAlert('danger', 'Błąd: ' + (response && response.message ? response.message : 'Nie udało się włączyć ostrzeżeń.'));
            // Restore button
            quickBtn.html(originalText).prop('disabled', false);
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Quick enable failed:', {xhr, status, error});
        window.showPageAlert('danger', 'Błąd komunikacji z serwerem: ' + error);
        // Restore button
        quickBtn.html(originalText).prop('disabled', false);
    });
};

window.bulkEnableWarnings = function() {
    console.log('bulkEnableWarnings called');
    
    const templateId = window.getTemplateIdFromUrl();
    if (!templateId) {
        console.error('Cannot find template ID');
        alert('Błąd: Nie można określić ID szablonu');
        return;
    }
    
    if (confirm('Czy na pewno chcesz włączyć ostrzeżenia dla wszystkich parametrów, które mają skonfigurowane normy?')) {
        // Show loading state
        const bulkBtn = $('button[onclick*="bulkEnableWarnings"]');
        const originalText = bulkBtn.html();
        bulkBtn.html('<i class="fas fa-spinner fa-spin"></i> Włączanie...').prop('disabled', true);
        
        $.post('/test-templates/' + templateId + '/bulk-enable-warnings', {
            _csrf: window.getCsrfToken()
        })
        .done(function(response) {
            console.log('Bulk enable response:', response);
            if (response && response.success) {
                window.showPageAlert('success', response.message);
                // Reload page after short delay
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                window.showPageAlert('danger', 'Błąd: ' + (response && response.message ? response.message : 'Nie udało się włączyć ostrzeżeń.'));
                // Restore button
                bulkBtn.html(originalText).prop('disabled', false);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Bulk enable failed:', {xhr, status, error});
            window.showPageAlert('danger', 'Błąd komunikacji z serwerem: ' + error);
            // Restore button
            bulkBtn.html(originalText).prop('disabled', false);
        });
    }
};

/**
 * ALERT SYSTEMS - Systemy powiadomień
 */
window.showSidebarAlert = function(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('#sidebarContent .alert').remove();
    
    // Add new alert
    $('#sidebarContent').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('#sidebarContent .alert').fadeOut();
    }, 5000);
};

window.showPageAlert = function(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Find container for alerts (usually after page header)
    let alertContainer = $('.page-header').next('.alert-container');
    if (alertContainer.length === 0) {
        // Create container if it doesn't exist
        alertContainer = $('<div class="alert-container mt-3"></div>');
        $('.page-header').after(alertContainer);
    }
    
    // Remove existing alerts
    alertContainer.find('.alert').remove();
    
    // Add new alert
    alertContainer.html(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        alertContainer.find('.alert').fadeOut();
    }, 5000);
};

/**
 * MODAL MANAGEMENT - Legacy support for existing modals
 */
window.loadNormsModal = function(parameterId, parameterName) {
    console.log('loadNormsModal called (LEGACY) - redirecting to sidebar');
    // Redirect legacy modal calls to new sidebar system
    window.openNormsSidebar(parameterId, parameterName);
};

window.deleteNormFromModal = function(normId, parameterId, normName) {
    console.log('deleteNormFromModal called (LEGACY) - redirecting to sidebar');
    // Redirect legacy modal calls to new sidebar system
    window.deleteNormFromSidebar(normId, parameterId, normName);
};

window.enableWarningsForNormModal = function(normId, parameterId) {
    console.log('enableWarningsForNormModal called (LEGACY) - redirecting to sidebar');
    // Redirect legacy modal calls to new sidebar system
    window.enableWarningsFromSidebar(normId, parameterId);
};

/**
 * FORM HELPERS - Helpers for norm forms
 */
window.toggleNormFields = function(type) {
    console.log('toggleNormFields called with type:', type);
    
    // Hide all specific fields
    $('#positive-negative-fields, #range-fields, #single-threshold-fields, #multiple-thresholds-fields').hide();
    
    // Show specific fields based on type
    switch(type) {
        case 'positive_negative':
            console.log('Showing positive-negative fields');
            $('#positive-negative-fields').show();
            break;
        case 'range':
            console.log('Showing range fields');
            $('#range-fields').show();
            break;
        case 'single_threshold':
            console.log('Showing single-threshold fields');
            $('#single-threshold-fields').show();
            break;
        case 'multiple_thresholds':
            console.log('Showing multiple-thresholds fields');
            $('#multiple-thresholds-fields').show();
            break;
        default:
            console.log('Unknown norm type:', type);
    }
    
    // Update zones preview if function exists
    if (typeof window.updateZonesPreview === 'function') {
        window.updateZonesPreview();
    }
};

window.toggleWarningFields = function(enabled) {
    console.log('toggleWarningFields called with enabled:', enabled);
    
    if (enabled) {
        $('#warning-fields').show();
    } else {
        $('#warning-fields').hide();
    }
    
    // Update zones preview if function exists
    if (typeof window.updateZonesPreview === 'function') {
        window.updateZonesPreview();
    }
};

/**
 * INITIALIZATION - Setup when DOM is ready
 */
$(document).ready(function() {
    console.log('MedArchive main.js initialized');
    
    // Sidebar support - Escape key closes sidebar
    $(document).keydown(function(e) {
        if (e.key === "Escape") {
            window.closeNormsSidebar();
        }
    });
    
    // Overlay click closes sidebar
    $(document).on('click', '#sidebarOverlay', function(e) {
        if (e.target === this) {
            window.closeNormsSidebar();
        }
    });
    
    // Fix Bootstrap modal z-index issues
    $(document).on('show.bs.modal', '.modal', function() {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
    
    // Auto-close alerts
    setTimeout(function() {
        $('.alert-auto-close').fadeOut();
    }, 5000);
    
    // Initialize form fields if on norm form page
    if ($('#norm-type-select').length) {
        const currentType = $('#norm-type-select').val();
        if (currentType) {
            window.toggleNormFields(currentType);
        }
        
        // Setup change handler
        $('#norm-type-select').on('change', function() {
            window.toggleNormFields($(this).val());
        });
    }
    
    // Initialize warning fields if on norm form page
    if ($('input[name="ParameterNorm[warning_enabled]"]').length) {
        const warningEnabled = $('input[name="ParameterNorm[warning_enabled]"]').is(':checked');
        window.toggleWarningFields(warningEnabled);
        
        // Setup change handler
        $('input[name="ParameterNorm[warning_enabled]"]').on('change', function() {
            window.toggleWarningFields($(this).is(':checked'));
        });
    }
    
    console.log('MedArchive main.js ready');
});

/**
 * ERROR HANDLING - Global error handler
 */
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // Optional: Send error to server for logging
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
    // Optional: Send error to server for logging
});

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        getTemplateIdFromUrl: window.getTemplateIdFromUrl,
        getCsrfToken: window.getCsrfToken,
        openNormsSidebar: window.openNormsSidebar,
        closeNormsSidebar: window.closeNormsSidebar
    };
}

// Dodaj do pliku assets/js/test-result-form.js lub podobnego

$(document).ready(function() {
    // Obsługa pól wprowadzania wartości parametrów
    $('input[name^="parameter_"]').each(function() {
        initializeValueInput($(this));
    });
});

function initializeValueInput($input) {
    // Dodaj tooltip z informacją o formatach
    $input.attr('title', 'Można używać przecinka (5,45) lub kropki (5.45) jako separator dziesiętny');
    
    // Inicjalizuj tooltip
    if (typeof bootstrap !== 'undefined') {
        new bootstrap.Tooltip($input[0]);
    }
    
    // Obsługa zmiany wartości - normalizacja w czasie rzeczywistym
    $input.on('blur', function() {
        normalizeInputValue($(this));
    });
    
    // Walidacja podczas pisania
    $input.on('input', function() {
        validateInputValue($(this));
    });
}

function normalizeInputValue($input) {
    let value = $input.val().trim();
    
    if (value === '') return;
    
    // Zamień przecinek na kropkę
    let normalizedValue = value.replace(',', '.');
    
    // Sprawdź czy to liczba
    if (isNumeric(normalizedValue)) {
        // Usuń wiodące zera, ale zachowaj pojedyncze zero
        let num = parseFloat(normalizedValue);
        
        // Formatuj z odpowiednią liczbą miejsc po przecinku
        if (num % 1 === 0) {
            // Liczba całkowita
            normalizedValue = num.toString();
        } else {
            // Liczba dziesiętna - zachowaj do 3 miejsc po przecinku
            normalizedValue = num.toFixed(3).replace(/\.?0+$/, '');
        }
        
        // Zastąp kropkę przecinkiem dla polskiego formatowania w wyświetleniu
        $input.val(normalizedValue.replace('.', ','));
        
        // Usuń ewentualne komunikaty błędów
        clearValidationError($input);
    }
}

function validateInputValue($input) {
    let value = $input.val().trim();
    
    if (value === '') {
        clearValidationError($input);
        return;
    }
    
    // Sprawdź typ parametru
    let parameterType = $input.data('parameter-type');
    let normType = $input.data('norm-type');
    
    // WYŁĄCZ walidację dla:
    // 1. Parametrów typu TEXT
    // 2. Norm typu positive_negative  
    // 3. Jeśli nie ma określonego typu (bezpieczna opcja)
    if (parameterType === 'text' || 
        normType === 'positive_negative' || 
        !parameterType) {
        clearValidationError($input);
        return; // Nie waliduj - backend zajmie się sprawdzeniem
    }
    
    // Tylko dla parametrów z określonym typem numerycznym sprawdzaj format
    let testValue = value.replace(',', '.');
    
    if (!isNumeric(testValue)) {
        showValidationError($input, 'Wartość musi być liczbą (np. 5,45 lub 5.45).');
    } else {
        clearValidationError($input);
        
        // Sprawdź względem normy jeśli dostępna
        let normId = $input.data('norm-id');
        if (normId) {
            validateAgainstNorm($input, testValue, normId);
        }
    }
}


function validateAgainstNorm($input, value, normId) {
    $.ajax({
        url: '/test-results/validate-value',
        method: 'POST',
        data: {
            value: value,
            normId: normId,
            _csrf: $('meta[name=csrf-token]').attr('content')
        },
        success: function(response) {
            if (response.is_normal) {
                showValidationSuccess($input, 'Wartość w normie');
            } else {
                showValidationWarning($input, response.message || 'Wartość poza normą');
            }
        }
    });
}

function isNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

function isAcceptableTextValue(value) {
    const acceptedValues = [
        'ujemny', 'negatywny', 'negative', '-',
        'dodatny', 'pozytywny', 'positive', '+',
        'ślad', 'trace', 'tr',
        'nieoznaczalny', 'niedostępny', 'n/a', 'nd',
        'hemoliza', 'lipemia', 'ikteryczne',
        'prawidłowy', 'nieprawidłowy', 'normal', 'abnormal'
    ];
    
    return acceptedValues.includes(value.toLowerCase());
}

function showValidationError($input, message) {
    clearValidationMessages($input);
    
    $input.addClass('is-invalid');
    
    let feedback = $('<div class="invalid-feedback"></div>').text(message);
    $input.after(feedback);
}

function showValidationWarning($input, message) {
    clearValidationMessages($input);
    
    $input.addClass('is-warning');
    
    let feedback = $('<div class="warning-feedback text-warning small"></div>').text(message);
    $input.after(feedback);
}

function showValidationSuccess($input, message) {
    clearValidationMessages($input);
    
    $input.addClass('is-valid');
    
    let feedback = $('<div class="valid-feedback"></div>').text(message);
    $input.after(feedback);
}

function clearValidationError($input) {
    $input.removeClass('is-invalid');
    $input.siblings('.invalid-feedback').remove();
}

function clearValidationMessages($input) {
    $input.removeClass('is-invalid is-valid is-warning');
    $input.siblings('.invalid-feedback, .valid-feedback, .warning-feedback').remove();
}