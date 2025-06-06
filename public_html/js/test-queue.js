$(document).ready(function() {
    // Date picker initialization
    $('.date-picker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        language: 'pl'
    });

    // Mark as completed
    $('.mark-completed').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var url = $this.attr('href');
        
        if (confirm('Czy na pewno chcesz oznaczyć to badanie jako wykonane?')) {
            $.post(url).done(function() {
                location.reload();
            }).fail(function() {
                alert('Błąd podczas aktualizacji statusu');
            });
        }
    });

    // Reminder management
    $('.send-reminder').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var queueId = $this.data('queue-id');
        
        $.post('/test-queue/send-reminder', { id: queueId })
            .done(function(response) {
                if (response.success) {
                    $this.replaceWith('<span class="badge bg-success">Wysłano</span>');
                } else {
                    alert('Błąd podczas wysyłania przypomnienia');
                }
            });
    });

    // Bulk actions
    $('#bulk-complete').on('click', function() {
        var selectedItems = [];
        $('.queue-checkbox:checked').each(function() {
            selectedItems.push($(this).val());
        });
        
        if (selectedItems.length === 0) {
            alert('Wybierz elementy do oznaczenia jako wykonane');
            return;
        }
        
        if (confirm('Czy na pewno chcesz oznaczyć wybrane badania jako wykonane?')) {
            $.post('/test-queue/bulk-complete', { ids: selectedItems })
                .done(function() {
                    location.reload();
                });
        }
    });

    // Calendar view toggle
    $('#calendar-view-toggle').on('click', function() {
        $('#list-view').toggle();
        $('#calendar-view').toggle();
        
        if ($('#calendar-view').is(':visible')) {
            initializeCalendar();
        }
    });

    function initializeCalendar() {
        // Simple calendar implementation
        // In production, you might want to use a library like FullCalendar
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        $.get('/test-queue/get-calendar-data')
            .done(function(data) {
                renderCalendar(calendarEl, data);
            });
    }

    function renderCalendar(element, data) {
        // Basic calendar rendering
        var html = '<div class="calendar-grid">';
        // Implementation would go here
        html += '</div>';
        
        element.innerHTML = html;
    }

    // Select all checkbox
    $('.select-all-queue').on('change', function() {
        var checked = $(this).prop('checked');
        $('.queue-checkbox').prop('checked', checked);
    });
});