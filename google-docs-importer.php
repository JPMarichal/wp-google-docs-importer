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

register_activation_hook(__FILE__, ['G2WPI_DB', 'create_table']);

// Instanciar clases principales
new G2WPI_Admin();
new G2WPI_Settings();
new G2WPI_OAuth();
new G2WPI_Ajax();

// Función para renderizar la tabla de documentos (usada por admin y AJAX)
function g2wpi_render_docs_table() {
    global $wpdb;
    $docs = get_transient('g2wpi_drive_docs');
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Nombre</th><th>Post asociado</th><th>Fecha</th><th>Acciones</th></tr></thead>';
    echo '<tbody>';
    if (!$docs || !is_array($docs)) {
        echo '<tr><td colspan="4">Haz clic en "Actualizar listado" para obtener los documentos.</td></tr>';
    } else {
        foreach ($docs as $doc) {
            $imported = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $doc['id']));
            $post_link = $imported ? '<a href="' . get_edit_post_link($imported->post_id) . '" target="_blank">Ver post</a>' : '—';
            $fecha = $imported ? $imported->imported_at : '—';
            $accion = $imported ? 'Importado' : '<a href="' . admin_url('admin.php?page=g2wpi-importador&import=' . $doc['id']) . '" class="button">Importar</a>';
            $doc_url = 'https://docs.google.com/document/d/' . $doc['id'] . '/edit';
            $nombre = '<a href="' . esc_url($doc_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($doc['name']) . '</a>';
            echo '<tr>';
            echo '<td>' . $nombre . '</td>';
            echo '<td>' . $post_link . '</td>';
            echo '<td>' . esc_html($fecha) . '</td>';
            echo '<td>' . $accion . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
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
