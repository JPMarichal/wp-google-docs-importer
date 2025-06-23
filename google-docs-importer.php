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
    echo '<div class="g2wpi-main-container">';
    // Título principal con margin-bottom 0
    echo '<h1 class="g2wpi-title" style="margin-bottom:0;">' . esc_html__('Importador de Google Docs', 'google-docs-importer') . '</h1>';
    // Bloque informativo (nombre de la carpeta)
    $settings = get_option('g2wpi_settings');
    $folder_id = $settings['folder_id'] ?? '';
    $folder_name = '';
    if ($folder_id && class_exists('G2WPI_Drive') && method_exists('G2WPI_Drive', 'get_folder_name')) {
        $folder_name = G2WPI_Drive::get_folder_name($folder_id);
    }
    if ($folder_name) {
        echo '<div class="g2wpi-folder-info" style="margin:6px 0 10px 0;padding:3px 0 2px 10px;color:#2271b1;border-top:1px solid #e0e0e0;border-bottom:1px solid #e0e0e0;background:#f9f9fb;">';
        echo '<span class="dashicons dashicons-category" style="vertical-align:middle;margin-right:3px;font-size:16px;width:16px;height:16px;"></span>';
        echo esc_html($folder_name);
        echo '</div>';
    }
    echo '<nav class="g2wpi-toolbar">';
    // Botones más pequeños usando clases y estilos inline
    echo '<button id="g2wpi-change-folder-btn" class="button" style="padding:2px 10px;font-size:12px;height:26px;line-height:20px;min-width:0;">'
        .'<span class="dashicons dashicons-category" style="font-size:14px;width:14px;height:14px;"></span> '
        .esc_html__('Cambiar carpeta', 'google-docs-importer')
        .'</button>';
    echo '<button id="g2wpi-refresh-list-btn" class="button" style="padding:2px 10px;font-size:12px;height:26px;line-height:20px;min-width:0;">'
        .'<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span> '
        .esc_html__('Actualizar listado', 'google-docs-importer')
        .'</button>';
    echo '</nav>';
    echo '<div class="g2wpi-searchbar">'
        .'<input type="text" id="g2wpi-search-docs" placeholder="' . esc_attr__('Buscar por nombre de documento...', 'google-docs-importer') . '" autocomplete="off" />'
        .'</div>';
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
    echo '<h1>' . esc_html__('Ajustes del Importador de Google Docs', 'google-docs-importer') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields(G2WPI_OPTION_GROUP);
    do_settings_sections('g2wpi-ajustes');
    submit_button();
    echo '</form>';
    echo '<h2>' . esc_html__('Autenticación con Google', 'google-docs-importer') . '</h2>';
    echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . esc_html__('Conectar con Google', 'google-docs-importer') . '</a>';
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

// Forzar carga del CSS desde PHP para el admin principal
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_g2wpi-importador') {
        wp_enqueue_style('g2wpi-admin-ui', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-ui.css', [], null);
    }
});

// Unifica la carga de idioma y traducciones en plugins_loaded
add_action('plugins_loaded', function() {
    load_plugin_textdomain('google-docs-importer', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
