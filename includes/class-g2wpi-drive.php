<?php
// Clase para la interacción con Google Drive y Docs
class G2WPI_Drive {
    public static function fetch_drive_documents() {
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

    /**
     * Importa un documento de Google Docs como post en WordPress (HTML).
     * @param string $doc_id
     */
    public static function import_google_doc($doc_id) {
        error_log('G2WPI DEBUG: INICIO import_google_doc');
        $tokens = get_option(G2WPI_TOKEN_OPTION);
        if (!$tokens || !isset($tokens['access_token'])) {
            error_log('G2WPI ERROR: No access token.');
            wp_die('Error: No access token de Google.');
        }
        $access_token = $tokens['access_token'];
        error_log('G2WPI DEBUG: Token obtenido');

        // Obtener el nombre del documento para el título
        $meta_url = "https://www.googleapis.com/drive/v3/files/{$doc_id}?fields=name";
        error_log('G2WPI DEBUG: meta_url=' . $meta_url);
        $meta_response = wp_remote_get($meta_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        error_log('G2WPI DEBUG: meta_response=' . print_r($meta_response, true));
        $title = 'Documento importado';
        if (!is_wp_error($meta_response)) {
            $meta_data = json_decode(wp_remote_retrieve_body($meta_response), true);
            error_log('G2WPI DEBUG: meta_data=' . print_r($meta_data, true));
            if (isset($meta_data['name'])) {
                $title = sanitize_text_field($meta_data['name']);
            }
        }

        // Exportar el documento como HTML
        $export_url = "https://www.googleapis.com/drive/v3/files/{$doc_id}/export?mimeType=text/html";
        error_log('G2WPI DEBUG: export_url=' . $export_url);
        $response = wp_remote_get($export_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        error_log('G2WPI DEBUG: export_response=' . print_r($response, true));
        if (is_wp_error($response)) {
            error_log('G2WPI ERROR: ' . print_r($response, true));
            wp_die('Error al exportar el documento de Google Docs: ' . $response->get_error_message());
        }
        $content = wp_remote_retrieve_body($response);
        error_log('G2WPI DEBUG: content_length=' . strlen($content));
        if (empty($content)) {
            error_log('G2WPI ERROR: El contenido HTML exportado está vacío.');
            wp_die('Error: El contenido exportado está vacío.');
        }

        // Extraer solo el contenido dentro de <body>...</body>
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
            $content = $matches[1];
        }

        // Crear el post en WordPress con autor 1
        $post_id = wp_insert_post([
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'draft',
            'post_type'     => 'post',
            'post_author'   => 1,
        ]);
        error_log('G2WPI DEBUG: post_id=' . print_r($post_id, true));
        if (is_wp_error($post_id)) {
            error_log('G2WPI ERROR: No se pudo crear el post. ' . $post_id->get_error_message());
            wp_die('Error al crear el post en WordPress: ' . $post_id->get_error_message());
        }

        // Registrar en la base de datos de importados (si existe la tabla)
        global $wpdb;
        if (defined('G2WPI_TABLE_NAME')) {
            $wpdb->insert(G2WPI_TABLE_NAME, [
                'google_doc_id' => $doc_id,
                'post_id' => $post_id,
                'imported_at' => current_time('mysql', 1)
            ]);
            error_log('G2WPI DEBUG: Insert en tabla de importados realizado');
        }

        // Redirigir al listado
        error_log('G2WPI DEBUG: Redirigiendo a listado');
        wp_redirect(admin_url('admin.php?page=g2wpi-importador'));
        exit;
    }
}
