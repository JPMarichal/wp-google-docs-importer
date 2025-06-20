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
    // Enqueue dashicons y CSS personalizado
    wp_enqueue_style('dashicons');
    wp_enqueue_style('g2wpi-admin-icons', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-icons.css');
    echo '<style>.g2wpi-table-actions { text-align: center; } .g2wpi-action-icon { margin-right: 8px; } .g2wpi-action-icon:last-child { margin-right: 0; } .g2wpi-table-sep { margin-bottom: 18px; display: block; } th.g2wpi-center { text-align: center !important; } .g2wpi-status { text-align: center; font-weight: normal; } .g2wpi-status-publish { color: #46b450; } .g2wpi-status-draft { color: #dba617; } .g2wpi-status-trash { color: #dc3232; } .g2wpi-status-pending { color: #0073aa; } </style>';
    // Separador visual para el botón
    echo '<span class="g2wpi-table-sep"></span>';
    $docs = get_transient('g2wpi_drive_docs');
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Nombre</th><th class="g2wpi-center">Importación</th><th class="g2wpi-center">Acciones</th><th class="g2wpi-center">Status</th><th>Fecha</th></tr></thead>';
    echo '<tbody>';
    if (!$docs || !is_array($docs)) {
        echo '<tr><td colspan="4">Haz clic en "Actualizar listado" para obtener los documentos.</td></tr>';
    } else {
        foreach ($docs as $doc) {
            $imported = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $doc['id']));
            $status_label = '____________';
            $status_class = '';
            $status_icon = '';
            $post_links = '—';
            $accion = '<a href="' . admin_url('admin.php?page=g2wpi-importador&import=' . $doc['id']) . '" class="button">Importar</a>';
            $fecha = '—';
            if ($imported) {
                $post_id = $imported->post_id;
                $post = get_post($post_id);
                if ($post) {
                    $status = $post->post_status;
                    switch ($status) {
                        case 'publish':
                            $status_label = 'Publicado';
                            $status_class = 'g2wpi-status-publish';
                            $status_icon = '<span class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle;"></span> ';
                            break;
                        case 'draft':
                            $status_label = 'Borrador';
                            $status_class = 'g2wpi-status-draft';
                            $status_icon = '<span class="dashicons dashicons-edit" style="color:#dba617;vertical-align:middle;"></span> ';
                            break;
                        case 'pending':
                            $status_label = 'Pendiente';
                            $status_class = 'g2wpi-status-pending';
                            $status_icon = '<span class="dashicons dashicons-clock" style="color:#0073aa;vertical-align:middle;"></span> ';
                            break;
                        case 'future':
                            $status_label = 'Programado';
                            $status_class = 'g2wpi-status-future';
                            $status_icon = '<span class="dashicons dashicons-calendar-alt" style="color:#0073aa;vertical-align:middle;"></span> ';
                            break;
                        case 'private':
                            $status_label = 'Privado';
                            $status_class = 'g2wpi-status-private';
                            $status_icon = '<span class="dashicons dashicons-lock" style="color:#666;vertical-align:middle;"></span> ';
                            break;
                        case 'inherit':
                            $status_label = 'Heredado';
                            $status_class = 'g2wpi-status-inherit';
                            $status_icon = '<span class="dashicons dashicons-admin-multisite" style="color:#888;vertical-align:middle;"></span> ';
                            break;
                        case 'auto-draft':
                            $status_label = 'Auto-borrador';
                            $status_class = 'g2wpi-status-autodraft';
                            $status_icon = '<span class="dashicons dashicons-welcome-write-blog" style="color:#aaa;vertical-align:middle;"></span> ';
                            break;
                        case 'trash':
                            $status_label = 'Papelera';
                            $status_class = 'g2wpi-status-trash';
                            $status_icon = '<span class="dashicons dashicons-trash" style="color:#dc3232;vertical-align:middle;"></span> ';
                            break;
                        case 'revision':
                            $status_label = 'Revisión';
                            $status_class = 'g2wpi-status-revision';
                            $status_icon = '<span class="dashicons dashicons-backup" style="color:#999;vertical-align:middle;"></span> ';
                            break;
                        default:
                            $status_label = ucfirst($status);
                            $status_class = '';
                            $status_icon = '<span class="dashicons dashicons-minus" style="vertical-align:middle;"></span> ';
                            break;
                    }
                    $view_url = $status === 'draft' ? get_preview_post_link($post_id) : get_permalink($post_id);
                    $edit_url = get_edit_post_link($post_id);
                    $delete_url = wp_nonce_url(admin_url('admin.php?page=g2wpi-importador&delete=' . $doc['id']), 'g2wpi_delete_' . $doc['id']);
                    $post_links = '<a href="' . esc_url($view_url) . '" class="g2wpi-action-icon dashicons dashicons-visibility" title="Ver" target="_blank" style="color:#0073aa;"></a>';
                    $post_links .= '<a href="' . esc_url($edit_url) . '" class="g2wpi-action-icon dashicons dashicons-edit" title="Editar" target="_blank" style="color:#dba617;"></a>';
                    $post_links .= '<a href="' . esc_url($delete_url) . '" class="g2wpi-action-icon dashicons dashicons-trash" title="Eliminar" onclick="return confirm(\'¿Seguro que deseas eliminar este post importado?\');" style="color:#dc3232;"></a>';
                    $accion = '<span class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle;"></span> Importado';
                    $fecha = $imported->imported_at;
                }
            } else {
                $accion = '<span class="dashicons dashicons-clock" style="color:#0073aa;vertical-align:middle;"></span> <a href="' . admin_url('admin.php?page=g2wpi-importador&import=' . $doc['id']) . '" class="button">Importar</a>';
            }
            $doc_url = 'https://docs.google.com/document/d/' . $doc['id'] . '/edit';
            $nombre = '<a href="' . esc_url($doc_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($doc['name']) . '</a>';
            echo '<tr>';
            echo '<td>' . $nombre . '</td>';
            echo '<td class="g2wpi-table-actions">' . $accion . '</td>';
            echo '<td class="g2wpi-table-actions">' . $post_links . '</td>';
            echo '<td class="g2wpi-status ' . esc_attr($status_class) . '">' . $status_icon . esc_html($status_label) . '</td>';
            echo '<td>' . esc_html($fecha) . '</td>';
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
