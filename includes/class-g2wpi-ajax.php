<?php
// Clase para las acciones AJAX
require_once __DIR__ . '/class-g2wpi-logger.php';
class G2WPI_Ajax {
    public function __construct() {
        add_action('wp_ajax_g2wpi_refresh_list_ajax', [$this, 'refresh_list_ajax']);
        add_action('wp_ajax_g2wpi_save_settings_ajax', [$this, 'save_settings_ajax']);
        add_action('wp_ajax_g2wpi_save_folder_id', [$this, 'save_folder_id_ajax']);
    }

    public function refresh_list_ajax() {
        try {
            G2WPI_Logger::log('AJAX: refresh_list_ajax llamado', 'DEBUG');
            $settings = get_option(G2WPI_OPTION_NAME);
            $folder_id = $settings['folder_id'] ?? '';
            if (!empty($_POST['force_refresh']) && $folder_id) {
                delete_transient('g2wpi_drive_docs_' . $folder_id);
                G2WPI_Logger::log('AJAX: Transient de documentos eliminado por force_refresh', 'INFO');
            }
            if (class_exists('G2WPI_Drive')) {
                G2WPI_Drive::fetch_drive_documents();
                G2WPI_Logger::log('AJAX: fetch_drive_documents ejecutado', 'DEBUG');
            }
            if (function_exists('g2wpi_render_docs_table')) g2wpi_render_docs_table();
        } catch (Throwable $e) {
            G2WPI_Logger::log('AJAX: Excepción en refresh_list_ajax: ' . $e->getMessage(), 'ERROR');
        }
        wp_die();
    }

    public function save_settings_ajax() {
        try {
            G2WPI_Logger::log('AJAX: save_settings_ajax llamado. Datos: ' . print_r($_POST, true), 'DEBUG');
            check_admin_referer('g2wpi_options-options');
            $raw_options = $_POST[G2WPI_OPTION_NAME] ?? [];
            $options = [];
            // Sanitizar y validar cada campo esperado
            $options['client_id'] = isset($raw_options['client_id']) ? sanitize_text_field($raw_options['client_id']) : '';
            $options['api_key'] = isset($raw_options['api_key']) ? sanitize_text_field($raw_options['api_key']) : '';
            // Añade aquí más campos según sea necesario, usando la función de sanitización adecuada
            // Ejemplo para un campo booleano:
            // $options['enable_feature'] = isset($raw_options['enable_feature']) ? (bool) $raw_options['enable_feature'] : false;

            $result = update_option(G2WPI_OPTION_NAME, $options);
            if ($result) {
                G2WPI_Logger::log('AJAX: Opciones guardadas correctamente.', 'INFO');
                wp_send_json_success();
            } else {
                G2WPI_Logger::log('AJAX: Error al guardar opciones.', 'ERROR');
                wp_send_json_error();
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('AJAX: Excepción en save_settings_ajax: ' . $e->getMessage(), 'ERROR');
            wp_send_json_error(['message' => 'Excepción: ' . $e->getMessage()]);
        }
    }

    public function save_folder_id_ajax() {
        try {
            G2WPI_Logger::log('AJAX: save_folder_id_ajax llamado. Datos: ' . print_r($_POST, true), 'DEBUG');
            if (!current_user_can('manage_options')) {
                G2WPI_Logger::log('AJAX: No autorizado en save_folder_id_ajax', 'ERROR');
                wp_send_json_error(['message' => 'No autorizado']);
            }
            $folder_id = isset($_POST['folder_id']) ? sanitize_text_field($_POST['folder_id']) : '';
            if (!$folder_id) {
                G2WPI_Logger::log('AJAX: Falta folder_id en save_folder_id_ajax', 'ERROR');
                wp_send_json_error(['message' => 'Falta folder_id']);
            }
            $options = get_option(G2WPI_OPTION_NAME);
            $options['folder_id'] = $folder_id;
            $result = update_option(G2WPI_OPTION_NAME, $options);
            if ($result) {
                delete_transient('g2wpi_drive_docs');
                G2WPI_Logger::log('AJAX: folder_id guardado correctamente y transient invalidado.', 'INFO');
                wp_send_json_success();
            } else {
                G2WPI_Logger::log('AJAX: No se pudo guardar folder_id.', 'ERROR');
                wp_send_json_error(['message' => 'No se pudo guardar']);
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('AJAX: Excepción en save_folder_id_ajax: ' . $e->getMessage(), 'ERROR');
            wp_send_json_error(['message' => 'Excepción: ' . $e->getMessage()]);
        }
    }
}
