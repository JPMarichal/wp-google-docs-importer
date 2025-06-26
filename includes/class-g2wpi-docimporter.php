<?php
require_once __DIR__ . '/class-g2wpi-drive.php';
require_once __DIR__ . '/class-g2wpi-htmlcleaner.php';
require_once __DIR__ . '/class-g2wpi-importlog.php';

class G2WPI_DocImporter {
    private $drive;
    private $cleaner;
    private $logger;

    public function __construct($drive, $cleaner, $logger) {
        $this->drive = $drive;
        $this->cleaner = $cleaner;
        $this->logger = $logger;
    }

    public function import($doc_id, $access_token, $params = []) {
        // Obtener metadatos
        $meta = $this->drive->get_document_metadata($doc_id, $access_token);
        $title = isset($meta['name']) ? sanitize_text_field($meta['name']) : 'Documento importado';
        // Exportar HTML
        $content = $this->drive->export_document_html($doc_id, $access_token);
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
            $content = $matches[1];
        }
        $content = $this->cleaner->clean($content);
        // Parámetros de post
        $post_author = isset($params['author']) ? intval($params['author']) : get_current_user_id();
        $post_status = isset($params['status']) ? sanitize_text_field($params['status']) : 'draft';
        $post_type = isset($params['post_type']) ? sanitize_text_field($params['post_type']) : 'post';
        $term_id = isset($params['term_id']) ? intval($params['term_id']) : 0;
        $allowed_statuses = ['draft', 'pending', 'publish'];
        if (!in_array($post_status, $allowed_statuses, true)) {
            $post_status = 'draft';
        }
        $allowed_types = get_post_types(['public' => true]);
        if (!in_array($post_type, $allowed_types, true)) {
            $post_type = 'post';
        }
        $post_id = wp_insert_post([
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => $post_status,
            'post_type'     => $post_type,
            'post_author'   => $post_author,
        ]);
        if (!is_wp_error($post_id)) {
            if ($term_id && $post_type) {
                $taxonomies = get_object_taxonomies($post_type, 'objects');
                foreach ($taxonomies as $tax) {
                    if ($tax->hierarchical) {
                        wp_set_post_terms($post_id, [$term_id], $tax->name);
                        break;
                    }
                }
            }
            $this->logger->log_import($doc_id, $post_id);
        }
        return $post_id;
    }
}
