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

    public static function import_google_doc($doc_id) {
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
}
