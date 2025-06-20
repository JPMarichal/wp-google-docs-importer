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
});
