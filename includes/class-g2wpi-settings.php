<?php
// Clase para la gestión de opciones y settings API
class G2WPI_Settings {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting(G2WPI_OPTION_GROUP, G2WPI_OPTION_NAME);
        add_settings_section('g2wpi_api_section', __('Credenciales de Google API', 'google-docs-importer'), null, 'g2wpi-ajustes');
        add_settings_field('client_id', __('Client ID', 'google-docs-importer'), [$this, 'render_input_field'], 'g2wpi-ajustes', 'g2wpi_api_section', ['label_for' => 'client_id']);
        add_settings_field('client_secret', __('Client Secret', 'google-docs-importer'), [$this, 'render_input_field'], 'g2wpi-ajustes', 'g2wpi_api_section', ['label_for' => 'client_secret']);
        add_settings_field('api_key', __('API Key', 'google-docs-importer'), [$this, 'render_input_field'], 'g2wpi-ajustes', 'g2wpi_api_section', ['label_for' => 'api_key']);
        // Eliminado el campo de idioma, ahora el idioma se toma de la configuración general de WordPress
    }

    public function render_input_field($args) {
        $options = get_option(G2WPI_OPTION_NAME);
        $value = isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : '';
        echo "<input type='text' name='" . G2WPI_OPTION_NAME . "[{$args['label_for']}]' value='$value' class='regular-text'>";
    }
}
