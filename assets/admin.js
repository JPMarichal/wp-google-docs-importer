jQuery(document).ready(function($) {
    $('#g2wpi-refresh-list-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Actualizando...');
        $.post(ajaxurl, { action: 'g2wpi_refresh_list_ajax' }, function(response) {
            $('#g2wpi-docs-table').html(response);
            $btn.prop('disabled', false).text('Actualizar listado');
        });
    });

    // Búsqueda incremental en la tabla de documentos
    $(document).on('input', '#g2wpi-search-docs', function() {
        var search = $(this).val().toLowerCase();
        // Buscar la tabla de documentos aunque esté fuera del mismo div
        $('table.wp-list-table.widefat.fixed.striped tbody tr').each(function() {
            var nombre = $(this).find('td:first').text().toLowerCase();
            if (nombre.indexOf(search) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
