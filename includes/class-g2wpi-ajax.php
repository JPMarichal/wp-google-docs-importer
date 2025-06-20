<?php
// Clase para las acciones AJAX
class G2WPI_Ajax {
    public function __construct() {
        add_action('wp_ajax_g2wpi_refresh_list_ajax', [$this, 'refresh_list_ajax']);
        add_action('wp_ajax_g2wpi_save_settings_ajax', [$this, 'save_settings_ajax']);
    }

    public function refresh_list_ajax() {
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
}
