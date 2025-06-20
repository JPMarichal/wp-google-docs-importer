<?php
/**
 * Plugin Name: Google Docs to WP Importer
 * Description: Importa documentos desde una carpeta de Google Drive como entradas en WordPress.
 * Version: 0.3.0
 * Author: Juan Pablo
 */

if (!defined('ABSPATH')) exit;

define('G2WPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('G2WPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('G2WPI_TABLE_NAME', $GLOBALS['wpdb']->prefix . 'google_docs_importados');
define('G2WPI_OPTION_GROUP', 'g2wpi_options');
define('G2WPI_OPTION_NAME', 'g2wpi_settings');
define('G2WPI_TOKEN_OPTION', 'g2wpi_tokens');

register_activation_hook(__FILE__, 'g2wpi_create_table');
function g2wpi_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS " . G2WPI_TABLE_NAME . " (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        google_doc_id VARCHAR(255) NOT NULL UNIQUE,
        post_id BIGINT(20),
        imported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

add_action('admin_menu', 'g2wpi_admin_menu');
function g2wpi_admin_menu() {
    add_menu_page('Importador de Google Docs', 'Google Docs Importer', 'manage_options', 'g2wpi-importador', 'g2wpi_render_admin_page', 'dashicons-google', 26);
    add_submenu_page('g2wpi-importador', 'Ajustes de Importador', 'Ajustes', 'manage_options', 'g2wpi-ajustes', 'g2wpi_render_settings_page');
}

function g2wpi_render_admin_page() {
    echo '<div class="wrap">';
    echo '<h1>Google Docs Importer</h1>';

    echo '<button id="g2wpi-refresh-list-btn" class="button button-secondary">Actualizar listado</button>';
    echo '<div id="g2wpi-docs-table">';
    g2wpi_render_docs_table();
    echo '</div>';
    echo '</div>';
}

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

add_action('wp_ajax_g2wpi_refresh_list_ajax', 'g2wpi_refresh_list_ajax');
function g2wpi_refresh_list_ajax() {
    g2wpi_fetch_drive_documents();
    g2wpi_render_docs_table();
    wp_die();
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_g2wpi-importador') {
        wp_enqueue_script('g2wpi-admin-js', G2WPI_PLUGIN_URL . 'assets/admin.js', ['jquery'], null, true);
    }
});

function g2wpi_import_google_doc($doc_id) {
    $settings = get_option(G2WPI_OPTION_NAME);
    $tokens = get_option(G2WPI_TOKEN_OPTION);
    if (!$tokens || !isset($tokens['access_token'])) return;

    $access_token = $tokens['access_token'];
    $url = "https://docs.googleapis.com/v1/documents/{$doc_id}";

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ]
    ]);

    if (is_wp_error($response)) return;

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['body']['content'])) return;

    $title = sanitize_text_field($data['title']);
    $content = '';

    foreach ($data['body']['content'] as $element) {
        if (isset($element['paragraph']['elements'])) {
            foreach ($element['paragraph']['elements'] as $el) {
                if (isset($el['textRun']['content'])) {
                    $content .= wp_kses_post(nl2br($el['textRun']['content']));
                }
            }
        }
    }

    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'draft',
        'post_type' => 'post',
    ]);

    if ($post_id && !is_wp_error($post_id)) {
        global $wpdb;
        $wpdb->insert(G2WPI_TABLE_NAME, [
            'google_doc_id' => $doc_id,
            'post_id' => $post_id,
            'imported_at' => current_time('mysql', 1)
        ]);
    }

    wp_redirect(admin_url('admin.php?page=g2wpi-importador'));
    exit;
}

function g2wpi_fetch_drive_documents() {
    $settings = get_option(G2WPI_OPTION_NAME);
    $tokens = get_option(G2WPI_TOKEN_OPTION);
    if (!$tokens || !isset($tokens['access_token']) || !$settings['folder_id']) return;

    $access_token = $tokens['access_token'];
    $folder_id = $settings['folder_id'];
    $url = "https://www.googleapis.com/drive/v3/files?q=" . urlencode("'{$folder_id}' in parents and mimeType='application/vnd.google-apps.document' and trashed=false") .
        "&fields=files(id,name,modifiedTime)&orderBy=modifiedTime desc";

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ]
    ]);

    if (is_wp_error($response)) return;

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['files'])) return;

    set_transient('g2wpi_drive_docs', $data['files'], 5 * MINUTE_IN_SECONDS);
}

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

add_action('admin_post_g2wpi_oauth_callback', 'g2wpi_handle_oauth_callback');
function g2wpi_handle_oauth_callback() {
    $settings = get_option(G2WPI_OPTION_NAME);
    if (!isset($_GET['code'])) wp_die('Falta el código de autorización.');
    $code = sanitize_text_field($_GET['code']);
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => http_build_query([
            'code' => $code,
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'redirect_uri' => admin_url('admin-post.php?action=g2wpi_oauth_callback'),
            'grant_type' => 'authorization_code'
        ])
    ]);
    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['access_token'])) {
            update_option(G2WPI_TOKEN_OPTION, $data);
            wp_redirect(admin_url('admin.php?page=g2wpi-ajustes&auth=success'));
            exit;
        }
    }
    wp_redirect(admin_url('admin.php?page=g2wpi-ajustes&auth=error'));
    exit;
}

add_action('admin_init', 'g2wpi_register_settings');
function g2wpi_register_settings() {
    register_setting(G2WPI_OPTION_GROUP, G2WPI_OPTION_NAME);
    add_settings_section('g2wpi_api_section', 'Credenciales de Google API', null, 'g2wpi-ajustes');
    add_settings_field('client_id', 'Client ID', 'g2wpi_render_input_field', 'g2wpi-ajustes', 'g2wpi_api_section', ['label_for' => 'client_id']);
    add_settings_field('client_secret', 'Client Secret', 'g2wpi_render_input_field', 'g2wpi-ajustes', 'g2wpi_api_section', ['label_for' => 'client_secret']);
    add_settings_field('folder_id', 'ID de Carpeta en Google Drive', 'g2wpi_render_input_field', 'g2wpi-ajustes', 'g2wpi_api_section', ['label_for' => 'folder_id']);
}

function g2wpi_render_input_field($args) {
    $options = get_option(G2WPI_OPTION_NAME);
    $value = isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : '';
    echo "<input type='text' name='" . G2WPI_OPTION_NAME . "[{$args['label_for']}]' value='$value' class='regular-text'>";
}
