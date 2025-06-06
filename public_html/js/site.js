$(document).ready(function() {
    // Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Confirmation dialogs
    $('[data-confirm]').on('click', function(e) {
        if (!confirm($(this).data('confirm'))) {
            e.preventDefault();
            return false;
        }
    });

    // Auto-hide alerts
    $('.alert').each(function() {
        var alert = $(this);
        if (!alert.hasClass('alert-danger')) {
            setTimeout(function() {
                alert.fadeOut();
            }, 5000);
        }
    });
});