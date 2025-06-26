<?php
// Clase para la administración del área de administración de WordPress
require_once __DIR__ . '/class-g2wpi-logger.php';
class G2WPI_Admin {
    public function __construct() {
        try {
            add_action('admin_menu', [$this, 'register_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('admin_footer', [$this, 'admin_footer']);
            G2WPI_Logger::log('G2WPI_Admin inicializado', 'DEBUG');
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en __construct de G2WPI_Admin: ' . $e->getMessage(), 'ERROR');
        }
    }

    public function register_menu() {
        try {
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
            G2WPI_Logger::log('Menús de administración registrados', 'DEBUG');
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en register_menu: ' . $e->getMessage(), 'ERROR');
        }
    }

    public function enqueue_scripts($hook) {
        try {
            if ($hook === 'toplevel_page_g2wpi-importador' || $hook === 'g2wpi-importador_page_g2wpi-ajustes') {
                wp_enqueue_script('g2wpi-admin-js', G2WPI_PLUGIN_URL . 'assets/admin.js', ['jquery'], null, true);
                wp_enqueue_script('g2wpi-swal', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
                wp_enqueue_style('g2wpi-swal-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
                wp_enqueue_style('g2wpi-admin-table', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-table.css', [], null);
                $settings = get_option(G2WPI_OPTION_NAME);
                wp_localize_script('g2wpi-admin-js', 'g2wpi_picker', [
                    'clientId' => $settings['client_id'] ?? '',
                    'apiKey'   => $settings['api_key'] ?? ''
                ]);
                G2WPI_Logger::log('Scripts y estilos de administración encolados', 'DEBUG');
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en enqueue_scripts: ' . $e->getMessage(), 'ERROR');
        }
    }

    public function admin_footer() {
        try {
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
                G2WPI_Logger::log('admin_footer renderizado para ajustes', 'DEBUG');
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en admin_footer: ' . $e->getMessage(), 'ERROR');
        }
    }

    public function render_admin_page() {
        try {
            G2WPI_Logger::log('Renderizando página principal de administración', 'DEBUG');
            echo '<div class="wrap">';
            if (function_exists('g2wpi_render_docs_table')) g2wpi_render_docs_table();
            echo '</div>';
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en render_admin_page: ' . $e->getMessage(), 'ERROR');
        }
    }

    public function render_settings_page() {
        try {
            G2WPI_Logger::log('Renderizando página de ajustes', 'DEBUG');
            if (function_exists('g2wpi_render_settings_page')) g2wpi_render_settings_page();
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en render_settings_page: ' . $e->getMessage(), 'ERROR');
        }
    }
}
