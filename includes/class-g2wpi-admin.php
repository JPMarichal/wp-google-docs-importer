<?php
// Clase para la administración del área de administración de WordPress
class G2WPI_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_footer', [$this, 'admin_footer']);
    }

    public function register_menu() {
        add_menu_page(
            __('Importador de Google Docs', 'google-docs-importer'),
            __('Google Docs Importer', 'google-docs-importer'),
            'manage_options',
            'g2wpi-importador',
            [$this, 'render_admin_page'],
            'dashicons-google',
            26
        );
        add_submenu_page(
            'g2wpi-importador',
            __('Ajustes de Importador', 'google-docs-importer'),
            __('Ajustes', 'google-docs-importer'),
            'manage_options',
            'g2wpi-ajustes',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'toplevel_page_g2wpi-importador' || $hook === 'g2wpi-importador_page_g2wpi-ajustes') {
            wp_enqueue_script('g2wpi-admin-js', G2WPI_PLUGIN_URL . 'assets/admin.js', ['jquery'], null, true);
            wp_enqueue_script('g2wpi-swal', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
            wp_enqueue_style('g2wpi-swal-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
            wp_enqueue_style('g2wpi-admin-table', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-table.css', [], null);
        }
    }

    public function admin_footer() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && ($screen->id === 'g2wpi-importador_page_g2wpi-ajustes' || $screen->id === 'settings_page_g2wpi-ajustes')) {
            ?>
            <script>
            jQuery(function($){
                var form = $('form[action="options.php"]');
                var btn = form.find('input[type=submit],button[type=submit]');
                form.on('submit', function(e) {
                    e.preventDefault();
                    var formData = form.serialize();
                    btn.prop('disabled', true).val('Guardando...');
                    $.post(ajaxurl, formData + '&action=g2wpi_save_settings_ajax', function(response) {
                        btn.prop('disabled', false).val('Save Changes');
                        if (typeof Swal !== 'undefined') {
                            if(response.success) {
                                Swal.fire({icon:'success',title:'¡Guardado!',text:'Cambios guardados correctamente',timer:1800,showConfirmButton:false});
                            } else {
                                Swal.fire({icon:'error',title:'Error',text:'Error al guardar los cambios'});
                            }
                        } else {
                            if(response.success) {
                                alert('Cambios guardados correctamente');
                            } else {
                                alert('Error al guardar los cambios');
                            }
                        }
                    }).fail(function(){
                        btn.prop('disabled', false).val('Save Changes');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({icon:'error',title:'Error',text:'Error de red al guardar los cambios'});
                        } else {
                            alert('Error de red al guardar los cambios');
                        }
                    });
                });
            });
            </script>
            <?php
        }
    }

    public function render_admin_page() {
        echo '<div class="wrap">';
        if (function_exists('g2wpi_render_docs_table')) g2wpi_render_docs_table();
        echo '</div>';
    }

    public function render_settings_page() {
        if (function_exists('g2wpi_render_settings_page')) g2wpi_render_settings_page();
    }
}
