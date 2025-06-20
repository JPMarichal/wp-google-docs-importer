<?php
/**
 * Plugin Name: Google Docs to WP Importer
 * Description: Importa documentos desde una carpeta de Google Drive como entradas en WordPress.
 * Version: 0.3.0
 * Author: Juan Pablo
 */

if (!defined('ABSPATH')) exit;

error_log('G2WPI DEBUG: Plugin google-docs-importer.php cargado');

define('G2WPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('G2WPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('G2WPI_TABLE_NAME', $GLOBALS['wpdb']->prefix . 'google_docs_importados');
define('G2WPI_OPTION_GROUP', 'g2wpi_options');
define('G2WPI_OPTION_NAME', 'g2wpi_settings');
define('G2WPI_TOKEN_OPTION', 'g2wpi_tokens');

require_once G2WPI_PLUGIN_DIR . 'includes/class-g2wpi-admin.php';
require_once G2WPI_PLUGIN_DIR . 'includes/class-g2wpi-settings.php';
require_once G2WPI_PLUGIN_DIR . 'includes/class-g2wpi-oauth.php';
require_once G2WPI_PLUGIN_DIR . 'includes/class-g2wpi-drive.php';
require_once G2WPI_PLUGIN_DIR . 'includes/class-g2wpi-ajax.php';
require_once G2WPI_PLUGIN_DIR . 'includes/class-g2wpi-db.php';
require_once G2WPI_PLUGIN_DIR . 'includes/Admin/class-g2wpi-docs-table.php';
require_once G2WPI_PLUGIN_DIR . 'includes/g2wpi-config.php';
$config = require G2WPI_PLUGIN_DIR . 'includes/g2wpi-config.php';

register_activation_hook(__FILE__, ['G2WPI_DB', 'create_table']);

// Instanciar clases principales
new G2WPI_Admin();
new G2WPI_Settings();
new G2WPI_OAuth();
new G2WPI_Ajax();

// Función para renderizar la tabla de documentos (usada por admin y AJAX)
function g2wpi_render_docs_table() {
    // Solo mostrar el bloque visual atractivo y el botón estilizado, con separación entre el dashicon y la "o"
    echo '<div style="display:flex;align-items:center;gap:18px;margin-bottom:18px;">';
    echo '<span style="font-size:2.2em;line-height:1;display:flex;align-items:center;">'
        . '<span class="dashicons dashicons-google" style="color:#4285f4;font-size:1.2em;margin-right:12px;"></span>'
        . '<span style="font-weight:700;font-size:1.1em;letter-spacing:0.5px;">oogle Docs Importer</span>'
        . '</span>';
    echo '<a href="' . esc_url(admin_url('admin.php?page=g2wpi-importador&refresh=1')) . '" class="button button-secondary" style="margin-left:18px;">'
        . '<span class="dashicons dashicons-update" style="vertical-align:middle;margin-right:4px;"></span>Actualizar listado</a>';
    echo '</div>';
    G2WPI_Docs_Table::render();
}

// Función para renderizar la página de ajustes (usada por admin)
function g2wpi_render_settings_page() {
    $settings = get_option(G2WPI_OPTION_NAME);
    $client_id = $settings['client_id'] ?? '';
    $redirect_uri = admin_url('admin-post.php?action=g2wpi_oauth_callback');
    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/documents.readonly',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);
    echo '<div class="wrap">';
    echo '<h1>Ajustes del Importador de Google Docs</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields(G2WPI_OPTION_GROUP);
    do_settings_sections('g2wpi-ajustes');
    submit_button();
    echo '</form>';
    echo '<h2>Autenticación con Google</h2>';
    echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">Conectar con Google</a>';
    echo '</div>';
}

add_action('admin_init', function() {
    if (isset($_GET['import']) && current_user_can('manage_options')) {
        G2WPI_Drive::import_google_doc(sanitize_text_field($_GET['import']));
    }
});

// Manejar eliminación de post importado
add_action('admin_init', function() {
    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        $doc_id = sanitize_text_field($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'g2wpi_delete_' . $doc_id)) {
            global $wpdb;
            $imported = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $doc_id));
            if ($imported) {
                wp_delete_post($imported->post_id, true);
                $wpdb->delete(G2WPI_TABLE_NAME, ['google_doc_id' => $doc_id]);
            }
            // Limpiar el transient para que la tabla se actualice
            delete_transient('g2wpi_drive_docs');
            // Forzar recarga del listado al volver
            wp_redirect(admin_url('admin.php?page=g2wpi-importador&refresh=1'));
            exit;
        }
    }
});

// Forzar actualización del listado si se pasa refresh=1
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'g2wpi-importador' && isset($_GET['refresh'])) {
        // Actualiza el listado usando el método real del plugin
        if (class_exists('G2WPI_Drive') && method_exists('G2WPI_Drive', 'fetch_drive_documents')) {
            G2WPI_Drive::fetch_drive_documents();
        }
    }
});

// Hook para refrescar el listado automáticamente si no hay datos en el transient
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'g2wpi-importador') {
        $docs = get_transient('g2wpi_drive_docs');
        if (!$docs || !is_array($docs)) {
            if (class_exists('G2WPI_Drive') && method_exists('G2WPI_Drive', 'fetch_drive_documents')) {
                G2WPI_Drive::fetch_drive_documents();
            }
        }
    }
});
