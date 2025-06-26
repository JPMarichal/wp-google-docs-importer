<?php
require_once __DIR__ . '/class-g2wpi-drive.php';
require_once __DIR__ . '/class-g2wpi-htmlcleaner.php';
require_once __DIR__ . '/class-g2wpi-importlog.php';
require_once __DIR__ . '/class-g2wpi-logger.php';

class G2WPI_DocImporter {
    private $drive;
    private HtmlCleanerInterface $cleaner;
    private $logger;

    public function __construct($drive, HtmlCleanerInterface $cleaner, $logger) {
        $this->drive = $drive;
        $this->cleaner = $cleaner;
        $this->logger = $logger;
    }

    public function import($doc_id, $access_token, $params = []) {
        try {
            G2WPI_Logger::log("Inicio importación doc_id=$doc_id", 'DEBUG');
            // Obtener metadatos
            $meta = $this->drive->get_document_metadata($doc_id, $access_token);
            if (!$meta || !isset($meta['name'])) {
                $this->logger->log_import($doc_id, 0, 'error', 'No metadata');
                G2WPI_Logger::log("Error: No metadata para doc_id $doc_id", 'ERROR');
                return 0;
            }
            $title = sanitize_text_field($meta['name']);
            // Exportar HTML
            $content = $this->drive->export_document_html($doc_id, $access_token);
            if (empty($content)) {
                $this->logger->log_import($doc_id, 0, 'error', 'No content');
                G2WPI_Logger::log("Error: No content para doc_id $doc_id", 'ERROR');
                return 0;
            }
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
            if (is_wp_error($post_id)) {
                $error_msg = $post_id->get_error_message();
                $this->logger->log_import($doc_id, 0, 'error', 'wp_insert_post error: ' . $error_msg);
                G2WPI_Logger::log("Error: wp_insert_post falló para doc_id $doc_id. Mensaje: $error_msg", 'ERROR');
                return 0;
            }
            if ($post_id) {
                if ($term_id && $post_type) {
                    $taxonomies = get_object_taxonomies($post_type, 'objects');
                    foreach ($taxonomies as $tax) {
                        if ($tax->hierarchical) {
                            wp_set_post_terms($post_id, [$term_id], $tax->name);
                            break;
                        }
                    }
                }
                $this->logger->log_import($doc_id, $post_id, 'success');
                G2WPI_Logger::log("Importación exitosa doc_id=$doc_id post_id=$post_id", 'INFO');
            }
            return $post_id;
        } catch (Throwable $e) {
            $this->logger->log_import($doc_id, 0, 'error', 'Exception: ' . $e->getMessage());
            G2WPI_Logger::log("Excepción en importación doc_id=$doc_id: " . $e->getMessage(), 'ERROR');
            return 0;
        }
    }
}
