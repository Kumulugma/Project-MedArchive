$(document).ready(function() {
    // Parameter type change handler
    $(document).on('change', '#testparameter-type', function() {
        var type = $(this).val();
        showNormFields(type);
    });

    // Norm type change handler
    $(document).on('change', '#parameternorm-type', function() {
        var type = $(this).val();
        showNormConfig(type);
    });

    function showNormFields(type) {
        $('.norm-field').hide();
        
        switch(type) {
            case 'range':
                $('#min-value-field, #max-value-field').show();
                break;
            case 'single_threshold':
                $('#threshold-value-field, #threshold-direction-field').show();
                break;
            case 'multiple_thresholds':
                $('#thresholds-config-field').show();
                break;
        }
    }

    function showNormConfig(type) {
        $('.norm-config').removeClass('active');
        $('#norm-config-' + type).addClass('active');
    }

    // Add threshold button
    $(document).on('click', '.add-threshold', function() {
        var container = $('#thresholds-container');
        
        var html = '<div class="threshold-item row mb-2">' +
            '<div class="col-md-3">' +
                '<input type="number" step="0.01" class="form-control" name="threshold_value[]" placeholder="Wartość">' +
            '</div>' +
            '<div class="col-md-3">' +
                '<input type="text" class="form-control" name="threshold_label[]" placeholder="Etykieta">' +
            '</div>' +
            '<div class="col-md-2">' +
                '<select class="form-control" name="threshold_normal[]">' +
                    '<option value="1">Norma</option>' +
                    '<option value="0">Nieprawidłowy</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<select class="form-control" name="threshold_type[]">' +
                    '<option value="">Brak</option>' +
                    '<option value="low">Niski</option>' +
                    '<option value="high">Wysoki</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<button type="button" class="btn btn-danger btn-sm remove-threshold">Usuń</button>' +
            '</div>' +
        '</div>';
        
        container.append(html);
    });

    // Remove threshold button
    $(document).on('click', '.remove-threshold', function() {
        $(this).closest('.threshold-item').remove();
    });

    // Parameter ordering - USUNIĘTE SORTABLE (wymaga jQuery UI)
    // Zastąpione prostszym rozwiązaniem z przyciskami góra/dół
    $(document).on('click', '.move-parameter-up', function() {
        var row = $(this).closest('.parameter-item');
        var prev = row.prev('.parameter-item');
        if (prev.length) {
            row.insertBefore(prev);
            updateParameterOrder();
        }
    });

    $(document).on('click', '.move-parameter-down', function() {
        var row = $(this).closest('.parameter-item');
        var next = row.next('.parameter-item');
        if (next.length) {
            row.insertAfter(next);
            updateParameterOrder();
        }
    });

    function updateParameterOrder() {
        var orders = [];
        $('.parameter-item').each(function(index) {
            orders.push({
                id: $(this).data('parameter-id'),
                order: index
            });
        });
        
        // Save new order via AJAX
        $.post('/test-template/reorder-parameters', {
            orders: orders
        });
    }
});