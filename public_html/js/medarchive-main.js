/**
 * MedArchive - Main JavaScript
 * Medical Archive System Frontend Functions
 */

$(document).ready(function() {
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize confirmation dialogs
    initializeConfirmations();
    
    // Auto-hide alerts
    autoHideAlerts();
    
    // Initialize form enhancements
    enhanceForms();
    
    // Initialize medical-specific features
    initializeMedicalFeatures();
    
    // Initialize animations
    initializeAnimations();
    
    // Initialize responsive tables
    initializeResponsiveTables();
});

/**
 * Initialize responsive tables for mobile devices
 */
function initializeResponsiveTables() {
    if (window.innerWidth <= 768) {
        $('.table tbody td').each(function() {
            var $cell = $(this);
            var columnIndex = $cell.index();
            var $header = $('.table thead th').eq(columnIndex);
            
            if ($header.length && !$cell.attr('data-label')) {
                var headerText = $header.text().trim();
                $cell.attr('data-label', headerText);
            }
        });
        
        // Add mobile-specific classes
        $('.table tbody tr').each(function() {
            var $row = $(this);
            
            // Check for abnormal values
            if ($row.find('.abnormal-value').length > 0) {
                $row.attr('data-abnormal', 'true');
            }
            
            // Check for urgent/overdue items
            if ($row.find('.badge.bg-warning').length > 0) {
                $row.attr('data-urgent', 'true');
            }
            
            if ($row.find('.badge.bg-danger').length > 0) {
                $row.attr('data-overdue', 'true');
            }
        });
    }
    
    // Re-initialize on window resize
    $(window).on('resize', function() {
        if (window.innerWidth <= 768) {
            initializeResponsiveTables();
        }
    });
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize confirmation dialogs with medical theme
 */
function initializeConfirmations() {
    $('[data-confirm]').on('click', function(e) {
        var message = $(this).data('confirm') || 'Czy na pewno chcesz wykonać tę operację?';
        
        if (!confirm('🏥 MedArchive\n\n' + message)) {
            e.preventDefault();
            return false;
        }
    });
}

/**
 * Auto-hide success alerts after 5 seconds
 */
function autoHideAlerts() {
    $('.alert').each(function() {
        var alert = $(this);
        if (alert.hasClass('alert-success') || alert.hasClass('alert-info')) {
            setTimeout(function() {
                alert.fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
    });
}

/**
 * Enhance forms with medical theme features
 */
function enhanceForms() {
    // Add focus animations to form controls
    $('.form-control, .form-select').on('focus', function() {
        $(this).closest('.form-group, .mb-3').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.form-group, .mb-3').removeClass('focused');
    });
    
    // Add validation styling
    $('.form-control').on('input change', function() {
        var $this = $(this);
        var value = $this.val();
        
        if ($this.attr('required') && value.trim() === '') {
            $this.removeClass('is-valid').addClass('is-invalid');
        } else if (value.trim() !== '') {
            $this.removeClass('is-invalid').addClass('is-valid');
        } else {
            $this.removeClass('is-valid is-invalid');
        }
    });
}

/**
 * Medical-specific feature enhancements
 */
function initializeMedicalFeatures() {
    // Highlight abnormal values with animation
    $('.abnormal-value').each(function() {
        $(this).addClass('animate__animated animate__pulse animate__infinite');
    });
    
    // Add medical icons to specific buttons
    enhanceMedicalButtons();
    
    // Initialize parameter value validation
    initializeParameterValidation();
}

/**
 * Add medical icons to buttons
 */
function enhanceMedicalButtons() {
    // Add icons to buttons that don't have them
    $('a[href*="create"], .btn:contains("Dodaj"), .btn:contains("Nowy")').each(function() {
        var $btn = $(this);
        if (!$btn.find('i').length) {
            $btn.prepend('<i class="fas fa-plus"></i> ');
        }
    });
    
    $('a[href*="update"], .btn:contains("Edytuj")').each(function() {
        var $btn = $(this);
        if (!$btn.find('i').length) {
            $btn.prepend('<i class="fas fa-edit"></i> ');
        }
    });
    
    $('a[href*="delete"], .btn:contains("Usuń")').each(function() {
        var $btn = $(this);
        if (!$btn.find('i').length) {
            $btn.prepend('<i class="fas fa-trash"></i> ');
        }
    });
    
    $('a[href*="view"], .btn:contains("Podgląd")').each(function() {
        var $btn = $(this);
        if (!$btn.find('i').length) {
            $btn.prepend('<i class="fas fa-eye"></i> ');
        }
    });
}

/**
 * Initialize parameter value validation for medical data
 */
function initializeParameterValidation() {
    $('.value-input').on('input', function() {
        var $input = $(this);
        var value = parseFloat($input.val());
        var parameterId = $input.data('parameter-id');
        
        if (!isNaN(value) && parameterId) {
            validateMedicalValue($input, value, parameterId);
        }
    });
}

/**
 * Validate medical values against norms
 */
function validateMedicalValue($input, value, parameterId) {
    var $group = $input.closest('.parameter-group');
    var $normSelect = $group.find('.norm-select');
    var normId = $normSelect.val();
    
    if (normId) {
        $.post('/test-result/validate-value', {
            value: value,
            normId: normId,
            _csrf: $('meta[name=csrf-token]').attr('content')
        })
        .done(function(response) {
            updateValueValidationUI($input, response);
        })
        .fail(function() {
            console.log('Validation failed');
        });
    }
}

/**
 * Update UI based on validation response
 */
function updateValueValidationUI($input, response) {
    $input.removeClass('is-valid is-invalid abnormal-value abnormal-low abnormal-high');
    
    if (response.is_normal) {
        $input.addClass('is-valid');
        showValidationMessage($input, 'Wartość prawidłowa', 'success');
    } else {
        $input.addClass('is-invalid abnormal-value');
        
        if (response.type === 'low') {
            $input.addClass('abnormal-low');
            showValidationMessage($input, 'Wartość poniżej normy', 'warning');
        } else if (response.type === 'high') {
            $input.addClass('abnormal-high');
            showValidationMessage($input, 'Wartość powyżej normy', 'warning');
        } else {
            showValidationMessage($input, 'Wartość nieprawidłowa', 'danger');
        }
    }
}

/**
 * Show validation message
 */
function showValidationMessage($input, message, type) {
    var $feedback = $input.siblings('.validation-feedback');
    if (!$feedback.length) {
        $feedback = $('<div class="validation-feedback"></div>');
        $input.after($feedback);
    }
    
    $feedback.removeClass('text-success text-warning text-danger')
             .addClass('text-' + type)
             .html('<i class="fas fa-info-circle"></i> ' + message);
}

/**
 * Initialize subtle animations
 */
function initializeAnimations() {
    // Animate cards on hover
    $('.card').hover(
        function() {
            $(this).addClass('shadow-medical');
        },
        function() {
            $(this).removeClass('shadow-medical');
        }
    );
    
    // Animate buttons on click
    $('.btn').on('click', function() {
        var $btn = $(this);
        $btn.addClass('animate__animated animate__pulse');
        setTimeout(function() {
            $btn.removeClass('animate__animated animate__pulse');
        }, 600);
    });
    
    // Animate table rows on hover (desktop only)
    if (window.innerWidth > 768) {
        $('.table tbody tr').hover(
            function() {
                $(this).addClass('table-hover-effect');
            },
            function() {
                $(this).removeClass('table-hover-effect');
            }
        );
    }
}

/**
 * Medical data specific functions
 */
var MedicalData = {
    
    /**
     * Format medical values for display
     */
    formatValue: function(value, unit) {
        if (unit) {
            return value + ' ' + unit;
        }
        return value;
    },
    
    /**
     * Check if value is within normal range
     */
    isNormalValue: function(value, min, max) {
        return value >= min && value <= max;
    },
    
    /**
     * Get abnormality type
     */
    getAbnormalityType: function(value, min, max) {
        if (value < min) return 'low';
        if (value > max) return 'high';
        return null;
    }
};

/**
 * Dashboard specific enhancements
 */
function initializeDashboard() {
    // Animate dashboard counters
    $('.dashboard-stats .h5').each(function() {
        var $this = $(this);
        var countTo = parseInt($this.text()) || 0;
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
}

/**
 * Initialize dashboard if on dashboard page
 */
if (window.location.pathname.includes('dashboard')) {
    $(document).ready(function() {
        initializeDashboard();
    });
}

/**
 * Global error handler
 */
window.addEventListener('error', function(e) {
    console.error('MedArchive Error:', e.error);
});

/**
 * Export functions for external use
 */
window.MedArchive = {
    MedicalData: MedicalData,
    validateMedicalValue: validateMedicalValue,
    showValidationMessage: showValidationMessage,
    initializeResponsiveTables: initializeResponsiveTables
};
    if (normId) {
        $.post('/test-result/validate-value', {
            value: value,
            normId: normId,
            _csrf: $('meta[name=csrf-token]').attr('content')
        })
        .done(function(response) {
            updateValueValidationUI($input, response);
        })
        .fail(function() {
            console.log('Validation failed');
        });
    }

/**
 * Update UI based on validation response
 */
function updateValueValidationUI($input, response) {
    $input.removeClass('is-valid is-invalid abnormal-value abnormal-low abnormal-high');
    
    if (response.is_normal) {
        $input.addClass('is-valid');
        showValidationMessage($input, 'Wartość prawidłowa', 'success');
    } else {
        $input.addClass('is-invalid abnormal-value');
        
        if (response.type === 'low') {
            $input.addClass('abnormal-low');
            showValidationMessage($input, 'Wartość poniżej normy', 'warning');
        } else if (response.type === 'high') {
            $input.addClass('abnormal-high');
            showValidationMessage($input, 'Wartość powyżej normy', 'warning');
        } else {
            showValidationMessage($input, 'Wartość nieprawidłowa', 'danger');
        }
    }
}

/**
 * Show validation message
 */
function showValidationMessage($input, message, type) {
    var $feedback = $input.siblings('.validation-feedback');
    if (!$feedback.length) {
        $feedback = $('<div class="validation-feedback"></div>');
        $input.after($feedback);
    }
    
    $feedback.removeClass('text-success text-warning text-danger')
             .addClass('text-' + type)
             .html('<i class="fas fa-info-circle"></i> ' + message);
}

/**
 * Initialize subtle animations
 */
function initializeAnimations() {
    // Animate cards on hover
    $('.card').hover(
        function() {
            $(this).addClass('shadow-medical');
        },
        function() {
            $(this).removeClass('shadow-medical');
        }
    );
    
    // Animate buttons on click
    $('.btn').on('click', function() {
        var $btn = $(this);
        $btn.addClass('animate__animated animate__pulse');
        setTimeout(function() {
            $btn.removeClass('animate__animated animate__pulse');
        }, 600);
    });
    
    // Animate table rows on hover
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('table-hover-effect');
        },
        function() {
            $(this).removeClass('table-hover-effect');
        }
    );
}

/**
 * Medical data specific functions
 */
var MedicalData = {
    
    /**
     * Format medical values for display
     */
    formatValue: function(value, unit) {
        if (unit) {
            return value + ' ' + unit;
        }
        return value;
    },
    
    /**
     * Check if value is within normal range
     */
    isNormalValue: function(value, min, max) {
        return value >= min && value <= max;
    },
    
    /**
     * Get abnormality type
     */
    getAbnormalityType: function(value, min, max) {
        if (value < min) return 'low';
        if (value > max) return 'high';
        return null;
    }
};

/**
 * Dashboard specific enhancements
 */
function initializeDashboard() {
    // Animate dashboard counters
    $('.dashboard-stats .h5').each(function() {
        var $this = $(this);
        var countTo = parseInt($this.text()) || 0;
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
}

/**
 * Initialize dashboard if on dashboard page
 */
if (window.location.pathname.includes('dashboard')) {
    $(document).ready(function() {
        initializeDashboard();
    });
}

/**
 * Global error handler
 */
window.addEventListener('error', function(e) {
    console.error('MedArchive Error:', e.error);
});

/**
 * Export functions for external use
 */
window.MedArchive = {
    MedicalData: MedicalData,
    validateMedicalValue: validateMedicalValue,
    showValidationMessage: showValidationMessage
};