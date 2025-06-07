/**
 * Dashboard JavaScript
 * Specific functions for dashboard page
 */

$(document).ready(function() {
    initializeDashboard();
    initializeStatCards();
    initializeCharts();
});

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
    // Animate statistics cards
    animateStatCards();
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Auto-refresh functionality
    initializeAutoRefresh();
    
    // Initialize action buttons
    initializeActionButtons();
}

/**
 * Initialize statistics cards with click actions
 */
function initializeStatCards() {
    $('.dashboard-stats .card').on('click', function() {
        var card = $(this);
        var index = card.closest('.col-xl-3').index();
        
        // Navigate based on card clicked
        switch(index) {
            case 0: // Total results
                window.location.href = '/test-result/index';
                break;
            case 1: // Active templates
                window.location.href = '/test-template/index';
                break;
            case 2: // Abnormal results
                window.location.href = '/test-result/index?TestResultSearch[has_abnormal_values]=1';
                break;
            case 3: // Scheduled tests
                window.location.href = '/test-queue/index';
                break;
        }
    });
}

/**
 * Animate statistics cards numbers
 */
function animateStatCards() {
    $('.dashboard-stats .h5').each(function() {
        var $this = $(this);
        var countTo = parseInt($this.text()) || 0;
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'easeInOutQuad',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
                
                // Add pulse animation for abnormal results if > 0
                if (countTo > 0 && $this.closest('.border-left-warning, .border-left-danger').length) {
                    $this.addClass('text-danger').css('animation', 'pulse 2s infinite');
                }
            }
        });
    });
}

/**
 * Initialize charts
 */
function initializeCharts() {
    // Chart is initialized in the view file
    // This function can be extended for additional chart functionality
    
    // Make chart responsive
    $(window).on('resize', function() {
        if (window.statisticsChart) {
            window.statisticsChart.resize();
        }
    });
}

/**
 * Initialize auto-refresh functionality
 */
function initializeAutoRefresh() {
    // Auto-refresh dashboard every 5 minutes
    setInterval(function() {
        refreshDashboardData();
    }, 300000); // 5 minutes
}

/**
 * Refresh dashboard data via AJAX
 */
function refreshDashboardData() {
    $.get('/dashboard/refresh-data')
        .done(function(data) {
            updateStatistics(data);
            updateTables(data);
        })
        .fail(function() {
            console.log('Failed to refresh dashboard data');
        });
}

/**
 * Update statistics with new data
 */
function updateStatistics(data) {
    if (data.statistics) {
        $('.dashboard-stats .h5').each(function(index) {
            var $this = $(this);
            var newValue = data.statistics[index] || 0;
            var oldValue = parseInt($this.text()) || 0;
            
            if (newValue !== oldValue) {
                $this.addClass('text-success');
                animateValueChange($this, oldValue, newValue);
                setTimeout(function() {
                    $this.removeClass('text-success');
                }, 2000);
            }
        });
    }
}

/**
 * Animate value change
 */
function animateValueChange($element, oldValue, newValue) {
    $({ countNum: oldValue }).animate({
        countNum: newValue
    }, {
        duration: 1000,
        step: function() {
            $element.text(Math.floor(this.countNum));
        },
        complete: function() {
            $element.text(this.countNum);
        }
    });
}

/**
 * Update tables with new data
 */
function updateTables(data) {
    // This can be implemented to update tables without full page reload
    // For now, we'll just show a subtle notification
    if (data.hasUpdates) {
        showUpdateNotification();
    }
}

/**
 * Show update notification
 */
function showUpdateNotification() {
    var notification = $('<div class="alert alert-info alert-dismissible fade show position-fixed" style="top: 80px; right: 20px; z-index: 1050;">' +
        '<i class="fas fa-info-circle"></i> Dane zostały zaktualizowane' +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    
    $('body').append(notification);
    
    setTimeout(function() {
        notification.alert('close');
    }, 3000);
}

/**
 * Initialize action buttons
 */
function initializeActionButtons() {
    // Quick action buttons
    $('.btn[href*="create"]').on('click', function() {
        $(this).addClass('btn-loading');
    });
    
    // Complete test buttons
    $('[data-method="post"]').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        setTimeout(function() {
            $btn.prop('disabled', false);
        }, 2000);
    });
}

/**
 * Medical specific dashboard functions
 */
var DashboardMedical = {
    
    /**
     * Highlight abnormal values
     */
    highlightAbnormalValues: function() {
        $('.abnormal-value').each(function() {
            $(this).closest('tr').addClass('table-warning');
        });
    },
    
    /**
     * Show urgent tests notification
     */
    showUrgentNotification: function(count) {
        if (count > 0) {
            var notification = $('<div class="alert alert-warning alert-dismissible fade show position-fixed" style="bottom: 20px; left: 20px; z-index: 1050;">' +
                '<i class="fas fa-exclamation-triangle"></i> Masz ' + count + ' pilnych badań do wykonania!' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            
            $('body').append(notification);
        }
    }
};

// Export for global use
window.DashboardMedical = DashboardMedical;

// Check for urgent tests on page load
$(document).ready(function() {
    var urgentCount = $('[data-urgent="true"]').length;
    if (urgentCount > 0) {
        DashboardMedical.showUrgentNotification(urgentCount);
    }
});

// Custom easing function
$.easing.easeInOutQuad = function(x, t, b, c, d) {
    if ((t /= d / 2) < 1) return c / 2 * t * t + b;
    return -c / 2 * ((--t) * (t - 2) - 1) + b;
};