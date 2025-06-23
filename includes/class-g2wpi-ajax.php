<?php
// Clase para las acciones AJAX
class G2WPI_Ajax {
    public function __construct() {
        add_action('wp_ajax_g2wpi_refresh_list_ajax', [$this, 'refresh_list_ajax']);
        add_action('wp_ajax_g2wpi_save_settings_ajax', [$this, 'save_settings_ajax']);
        add_action('wp_ajax_g2wpi_save_folder_id', [$this, 'save_folder_id_ajax']);
    }

    public function refresh_list_ajax() {
        // Forzar refresco si se solicita
        $settings = get_option(G2WPI_OPTION_NAME);
        $folder_id = $settings['folder_id'] ?? '';
        if (!empty($_POST['force_refresh']) && $folder_id) {
            delete_transient('g2wpi_drive_docs_' . $folder_id);
        }
        if (class_exists('G2WPI_Drive')) {
            G2WPI_Drive::fetch_drive_documents();
        }
        if (function_exists('g2wpi_render_docs_table')) g2wpi_render_docs_table();
        wp_die();
    }

    public function save_settings_ajax() {
        check_admin_referer('g2wpi_options-options');
        $options = $_POST[G2WPI_OPTION_NAME] ?? [];
        $result = update_option(G2WPI_OPTION_NAME, $options);
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function save_folder_id_ajax() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }
        $folder_id = isset($_POST['folder_id']) ? sanitize_text_field($_POST['folder_id']) : '';
        if (!$folder_id) {
            wp_send_json_error(['message' => 'Falta folder_id']);
        }
        $options = get_option(G2WPI_OPTION_NAME);
        $options['folder_id'] = $folder_id;
        $result = update_option(G2WPI_OPTION_NAME, $options);
        if ($result) {
            // Invalida el transient de documentos al cambiar la carpeta
            delete_transient('g2wpi_drive_docs');
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'No se pudo guardar']);
        }
    }
}
