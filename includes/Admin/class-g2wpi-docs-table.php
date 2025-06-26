<?php
/**
 * Clase para renderizar la tabla de documentos de Google Docs Importer
 */
if (!defined('ABSPATH')) exit;

require_once G2WPI_PLUGIN_DIR . 'includes/Admin/class-g2wpi-docs-sorter.php';
require_once G2WPI_PLUGIN_DIR . 'includes/Admin/class-g2wpi-docs-filters.php';
require_once G2WPI_PLUGIN_DIR . 'includes/Admin/class-g2wpi-docs-table-render.php';

class G2WPI_Docs_Table {
    public static function render() {
        global $wpdb;
        $config = require G2WPI_PLUGIN_DIR . 'includes/g2wpi-config.php';
        $per_page = isset($config['docs_per_page']) ? (int)$config['docs_per_page'] : 20;
        wp_enqueue_style('dashicons');
        wp_enqueue_style('g2wpi-admin-icons', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-icons.css');
        wp_enqueue_style('g2wpi-admin-table', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-table.css');
        echo '<span class="g2wpi-table-sep"></span>';
        list($docs, $orderby, $order) = G2WPI_Docs_Sorter::get_sorted_docs();
        $total_docs = is_array($docs) ? count($docs) : 0;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = max(1, $per_page);
        $offset = ($paged - 1) * $per_page;
        $docs_page = ($docs && is_array($docs)) ? array_slice($docs, $offset, $per_page) : [];
        G2WPI_Docs_Filters::render_filters();
        G2WPI_Docs_Table_Render::render_table($docs_page, $docs, $total_docs, $per_page, $paged, $orderby, $order);
    }
}
