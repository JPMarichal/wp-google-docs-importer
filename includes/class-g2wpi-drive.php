<?php
// Clase para la interacción con Google Drive y Docs
class G2WPI_Drive {
    public static function fetch_drive_documents() {
        $settings = get_option(G2WPI_OPTION_NAME);
        $tokens = get_option(G2WPI_TOKEN_OPTION);
        if ((!$tokens || !isset($tokens['access_token'])) && isset($tokens['refresh_token'])) {
            // Intentar refrescar el token si hay refresh_token
            if (class_exists('G2WPI_OAuth') && method_exists('G2WPI_OAuth', 'refresh_access_token')) {
                $tokens = G2WPI_OAuth::refresh_access_token();
            }
        }
        if (!$tokens || !isset($tokens['access_token']) || !$settings['folder_id']) {
            G2WPI_Logger::log('Token de acceso o folder_id no disponible en fetch_drive_documents.', 'ERROR');
            return;
        }
        $access_token = $tokens['access_token'];
        $folder_id = $settings['folder_id'];
        $url = "https://www.googleapis.com/drive/v3/files?q=" . urlencode("'{$folder_id}' in parents and mimeType='application/vnd.google-apps.document' and trashed=false") .
            "&fields=files(id,name,modifiedTime)&orderBy=modifiedTime desc";
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        // Si el token expiró, intentar refrescar y reintentar una vez
        if (is_wp_error($response) || (isset($response['response']['code']) && $response['response']['code'] == 401)) {
            if (class_exists('G2WPI_OAuth') && method_exists('G2WPI_OAuth', 'refresh_access_token')) {
                $tokens = G2WPI_OAuth::refresh_access_token();
                if ($tokens && isset($tokens['access_token'])) {
                    $access_token = $tokens['access_token'];
                    $response = wp_remote_get($url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $access_token,
                        ]
                    ]);
                }
            }
        }
        if (is_wp_error($response)) {
            G2WPI_Logger::log('Error en wp_remote_get al obtener documentos de Drive: ' . print_r($response, true), 'ERROR');
            return;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['files'])) {
            G2WPI_Logger::log('No se encontraron archivos en la respuesta de Drive. Respuesta: ' . print_r($data, true), 'ERROR');
            return;
        }
        set_transient('g2wpi_drive_docs_' . $folder_id, $data['files'], 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Importa un documento de Google Docs como post en WordPress (HTML).
     * @param string $doc_id
     */
    public static function import_google_doc($doc_id) {
        G2WPI_Logger::log('INICIO import_google_doc', 'DEBUG');
        $tokens = get_option(G2WPI_TOKEN_OPTION);
        if ((!$tokens || !isset($tokens['access_token'])) && isset($tokens['refresh_token'])) {
            // Intentar refrescar el token si hay refresh_token
            if (class_exists('G2WPI_OAuth') && method_exists('G2WPI_OAuth', 'refresh_access_token')) {
                $tokens = G2WPI_OAuth::refresh_access_token();
                $access_token = $tokens ? $tokens['access_token'] : null;
            }
        } else {
            $access_token = $tokens['access_token'];
        }
        if (!$access_token) {
            G2WPI_Logger::log('No access token.', 'ERROR');
            wp_die('Error: No access token de Google.');
        }
        G2WPI_Logger::log('Token obtenido', 'DEBUG');

        // Obtener el nombre del documento para el título
        $meta_url = "https://www.googleapis.com/drive/v3/files/{$doc_id}?fields=name";
        G2WPI_Logger::log('meta_url=' . $meta_url, 'DEBUG');
        $meta_response = wp_remote_get($meta_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        // Si el token expiró, intentar refrescar y reintentar una vez
        if (is_wp_error($meta_response) || (isset($meta_response['response']['code']) && $meta_response['response']['code'] == 401)) {
            if (class_exists('G2WPI_OAuth') && method_exists('G2WPI_OAuth', 'refresh_access_token')) {
                $tokens = G2WPI_OAuth::refresh_access_token();
                if ($tokens && isset($tokens['access_token'])) {
                    $access_token = $tokens['access_token'];
                    $meta_response = wp_remote_get($meta_url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $access_token,
                        ]
                    ]);
                }
            }
        }
        G2WPI_Logger::log('meta_response=' . print_r($meta_response, true), 'DEBUG');
        $title = 'Documento importado';
        if (!is_wp_error($meta_response)) {
            $meta_data = json_decode(wp_remote_retrieve_body($meta_response), true);
            G2WPI_Logger::log('meta_data=' . print_r($meta_data, true), 'DEBUG');
            if (isset($meta_data['name'])) {
                $title = sanitize_text_field($meta_data['name']);
            }
        }

        // Exportar el documento como HTML
        $export_url = "https://www.googleapis.com/drive/v3/files/{$doc_id}/export?mimeType=text/html";
        G2WPI_Logger::log('export_url=' . $export_url, 'DEBUG');
        $response = wp_remote_get($export_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        // Si el token expiró, intentar refrescar y reintentar una vez
        if (is_wp_error($response) || (isset($response['response']['code']) && $response['response']['code'] == 401)) {
            if (class_exists('G2WPI_OAuth') && method_exists('G2WPI_OAuth', 'refresh_access_token')) {
                $tokens = G2WPI_OAuth::refresh_access_token();
                if ($tokens && isset($tokens['access_token'])) {
                    $access_token = $tokens['access_token'];
                    $response = wp_remote_get($export_url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $access_token,
                        ]
                    ]);
                }
            }
        }
        G2WPI_Logger::log('export_response=' . print_r($response, true), 'DEBUG');
        if (is_wp_error($response)) {
            G2WPI_Logger::log(print_r($response, true), 'ERROR');
            wp_die('Error al exportar el documento de Google Docs: ' . $response->get_error_message());
        }
        $content = wp_remote_retrieve_body($response);
        G2WPI_Logger::log('content_length=' . strlen($content), 'DEBUG');
        if (empty($content)) {
            G2WPI_Logger::log('El contenido HTML exportado está vacío.', 'ERROR');
            wp_die('Error: El contenido exportado está vacío.');
        }

        // Extraer solo el contenido dentro de <body>...</body>
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
            $content = $matches[1];
        }
        // Limpiar y convertir a HTML semántico
        $content = self::g2wpi_cleanup_html($content);

        // Obtener autor, estado, tipo y término desde la URL si existen
        $post_author = isset($_GET['g2wpi_author']) ? intval($_GET['g2wpi_author']) : get_current_user_id();
        $post_status = isset($_GET['g2wpi_status']) ? sanitize_text_field($_GET['g2wpi_status']) : 'draft';
        $post_type = isset($_GET['g2wpi_post_type']) ? sanitize_text_field($_GET['g2wpi_post_type']) : 'post';
        $term_id = isset($_GET['g2wpi_term']) ? intval($_GET['g2wpi_term']) : 0;
        // Validar estado permitido
        $allowed_statuses = ['draft', 'pending', 'publish'];
        if (!in_array($post_status, $allowed_statuses, true)) {
            $post_status = 'draft';
        }
        // Validar post type permitido
        $allowed_types = get_post_types(['public' => true]);
        if (!in_array($post_type, $allowed_types, true)) {
            $post_type = 'post';
        }
        // Crear el post en WordPress con los valores seleccionados
        $post_id = wp_insert_post([
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => $post_status,
            'post_type'     => $post_type,
            'post_author'   => $post_author,
        ]);
        G2WPI_Logger::log('post_id=' . print_r($post_id, true), 'DEBUG');
        if (is_wp_error($post_id)) {
            G2WPI_Logger::log('No se pudo crear el post. ' . $post_id->get_error_message(), 'ERROR');
            wp_die('Error al crear el post en WordPress: ' . $post_id->get_error_message());
        }
        // Asignar término si corresponde
        if ($term_id && $post_type) {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $tax) {
                if ($tax->hierarchical) {
                    wp_set_post_terms($post_id, [$term_id], $tax->name);
                    break;
                }
            }
        }

        // Registrar en la base de datos de importados (si existe la tabla)
        global $wpdb;
        if (defined('G2WPI_TABLE_NAME')) {
            $wpdb->insert(G2WPI_TABLE_NAME, [
                'google_doc_id' => $doc_id,
                'post_id' => $post_id,
                'imported_at' => current_time('mysql', 1)
            ]);
            G2WPI_Logger::log('Insert en tabla de importados realizado', 'DEBUG');
        }

        // Redirigir al listado
        G2WPI_Logger::log('Redirigiendo a listado', 'DEBUG');
        wp_redirect(admin_url('admin.php?page=g2wpi-importador'));
        exit;
    }

    /**
     * Limpia el HTML importado, convirtiendo estilos visuales en etiquetas semánticas.
     * Convierte títulos y párrafos, y elimina estilos inline innecesarios.
     */
    private static function g2wpi_cleanup_html($html) {
        // 1. Convertir títulos (h1, h2, h3) según estilos comunes de Google Docs
        // h1: font-size >= 24pt, bold
        $html = preg_replace(
            '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*2[4-9]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
            '<h1>$1</h1>', $html);
        // h2: font-size 18-23pt, bold
        $html = preg_replace(
            '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*1[8-9]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
            '<h2>$1</h2>', $html);
        $html = preg_replace(
            '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*2[0-3]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
            '<h2>$1</h2>', $html);
        // h3: font-size 14-17pt, bold
        $html = preg_replace(
            '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*1[4-7]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
            '<h3>$1</h3>', $html);

        // 1. Convertir spans con negrita/itálica a <strong>/<em> usando callback robusto
        $html = preg_replace_callback(
            '/<span([^>]*)style="([^"]*)"([^>]*)>(.*?)<\/span>/is',
            function($matches) {
                $style = strtolower($matches[2]);
                $content = $matches[4];
                $is_bold = (strpos($style, 'font-weight:700') !== false || strpos($style, 'font-weight:bold') !== false);
                $is_italic = (strpos($style, 'font-style:italic') !== false);
                if ($is_bold && $is_italic) {
                    return '<strong><em>' . $content . '</em></strong>';
                } elseif ($is_bold) {
                    return '<strong>' . $content . '</strong>';
                } elseif ($is_italic) {
                    return '<em>' . $content . '</em>';
                } else {
                    return $content;
                }
            },
            $html
        );

        // 2. Convertir párrafos (p) simples (sin span o con span sin estilos relevantes)
        $html = preg_replace('/<p[^>]*>(.*?)<\/p>/is', '<p>$1</p>', $html);
        // 3. Eliminar estilos inline innecesarios en span y p
        $html = preg_replace('/<(span|p)[^>]*style="[^"]*"[^>]*>/i', '<$1>', $html);
        // 4. Eliminar spans vacíos o sin contenido relevante
        $html = preg_replace('/<span>\s*<\/span>/i', '', $html);
        // 5. Opcional: eliminar clases innecesarias
        $html = preg_replace('/<(span|p)[^>]*class="[^"]*"[^>]*>/i', '<$1>', $html);
        return $html;
    }

    // Devuelve el nombre de una carpeta de Google Drive dado su ID
    public static function get_folder_name($folder_id) {
        $settings = get_option(G2WPI_OPTION_NAME);
        $tokens = get_option(G2WPI_TOKEN_OPTION);
        if (!$tokens || !isset($tokens['access_token'])) {
            G2WPI_Logger::log('No access token en get_folder_name.', 'ERROR');
            return '';
        }
        $access_token = $tokens['access_token'];
        $url = "https://www.googleapis.com/drive/v3/files/{$folder_id}?fields=name";
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        if (is_wp_error($response)) {
            G2WPI_Logger::log('Error en wp_remote_get al obtener nombre de carpeta: ' . print_r($response, true), 'ERROR');
            return '';
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['name'])) {
            G2WPI_Logger::log('No se encontró el nombre de la carpeta en la respuesta. Respuesta: ' . print_r($data, true), 'ERROR');
            return '';
        }
        return $data['name'];
    }

    public function get_document_metadata($doc_id, $access_token) {
        // Simula la llamada real, pero permite mockear en tests
        $meta_url = "https://www.googleapis.com/drive/v3/files/{$doc_id}?fields=name";
        $meta_response = wp_remote_get($meta_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        if (is_wp_error($meta_response)) {
            G2WPI_Logger::log('Error en wp_remote_get al obtener metadatos del documento: ' . print_r($meta_response, true), 'ERROR');
            return null;
        }
        $meta_data = json_decode(wp_remote_retrieve_body($meta_response), true);
        if (!isset($meta_data['name'])) {
            G2WPI_Logger::log('No se encontró el nombre del documento en la respuesta de metadatos. Respuesta: ' . print_r($meta_data, true), 'ERROR');
            return null;
        }
        return ['name' => sanitize_text_field($meta_data['name'])];
    }

    public function export_document_html($doc_id, $access_token) {
        $export_url = "https://www.googleapis.com/drive/v3/files/{$doc_id}/export?mimeType=text/html";
        $response = wp_remote_get($export_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ]
        ]);
        if (is_wp_error($response)) {
            G2WPI_Logger::log('Error en wp_remote_get al exportar documento HTML: ' . print_r($response, true), 'ERROR');
            return '';
        }
        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            G2WPI_Logger::log('El HTML exportado está vacío para el documento ' . $doc_id, 'ERROR');
            return '';
        }
        return $html;
    }
}
