<?php
/**
 * Clase para renderizar la tabla de documentos de Google Docs Importer
 */
if (!defined('ABSPATH')) exit;

class G2WPI_Docs_Table {
    public static function render() {
        global $wpdb;
        $config = require G2WPI_PLUGIN_DIR . 'includes/g2wpi-config.php';
        $per_page = isset($config['docs_per_page']) ? (int)$config['docs_per_page'] : 20;
        wp_enqueue_style('dashicons');
        wp_enqueue_style('g2wpi-admin-icons', G2WPI_PLUGIN_URL . 'assets/css/g2wpi-admin-icons.css');
        echo '<style>
            .g2wpi-pagination { display: flex; justify-content: center; align-items: center; margin: 18px 0; gap: 4px; flex-wrap: wrap; }
            .g2wpi-pagination a, .g2wpi-pagination span { padding: 4px 10px; border-radius: 4px; border: 1px solid #ddd; background: #fff; color: #0073aa; text-decoration: none; margin: 0 2px; font-weight: 500; transition: background 0.2s, color 0.2s; }
            .g2wpi-pagination a:hover { background: #0073aa; color: #fff; }
            .g2wpi-pagination .current-page { background: #0073aa; color: #fff; border-color: #0073aa; cursor: default; }
            .g2wpi-pagination .g2wpi-ellipsis { border: none; background: none; color: #888; cursor: default; }
        </style>';
        echo '<span class="g2wpi-table-sep"></span>';
        $docs = get_transient('g2wpi_drive_docs');
        $total_docs = is_array($docs) ? count($docs) : 0;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        $docs_page = ($docs && is_array($docs)) ? array_slice($docs, $offset, $per_page) : [];
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Nombre</th><th class="g2wpi-center">Importación</th><th class="g2wpi-center">Acciones</th><th class="g2wpi-center">Status</th><th>Tipo</th><th>Fecha</th></tr></thead>';
        echo '<tbody>';
        if (!$docs || !is_array($docs)) {
            echo '<tr><td colspan="6">Haz clic en "Actualizar listado" para obtener los documentos.</td></tr>';
        } else {
            foreach ($docs_page as $doc) {
                list($accion, $post_links, $status_label, $status_class, $status_icon, $post_type_label, $fecha) = self::get_doc_row($doc);
                $doc_url = 'https://docs.google.com/document/d/' . $doc['id'] . '/edit';
                $nombre = '<a href="' . esc_url($doc_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($doc['name']) . '</a>';
                echo '<tr>';
                echo '<td>' . $nombre . '</td>';
                echo '<td class="g2wpi-table-actions">' . $accion . '</td>';
                echo '<td class="g2wpi-table-actions">' . $post_links . '</td>';
                echo '<td class="g2wpi-status ' . esc_attr($status_class) . '">' . $status_icon . esc_html($status_label) . '</td>';
                echo '<td>' . esc_html($post_type_label) . '</td>';
                echo '<td>' . esc_html($fecha) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        // Paginación amigable
        if ($docs && is_array($docs) && $total_docs > $per_page) {
            $total_pages = ceil($total_docs / $per_page);
            $base_url = remove_query_arg('paged');
            echo '<div class="g2wpi-pagination">';
            $show = 2; // páginas visibles a la izquierda y derecha
            $ellipsis = false;
            // Botón anterior
            if ($paged > 1) {
                $prev_url = esc_url(add_query_arg('paged', $paged - 1, $base_url));
                echo '<a href="' . $prev_url . '" title="Anterior">&laquo;</a>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $paged - $show && $i <= $paged + $show)) {
                    $url = esc_url(add_query_arg('paged', $i, $base_url));
                    $class = ($i == $paged) ? 'current-page' : '';
                    if ($i == $paged) {
                        echo '<span class="current-page">' . $i . '</span>';
                    } else {
                        echo '<a href="' . $url . '" class="' . $class . '">' . $i . '</a>';
                    }
                    $ellipsis = false;
                } elseif (!$ellipsis) {
                    echo '<span class="g2wpi-ellipsis">…</span>';
                    $ellipsis = true;
                }
            }
            // Botón siguiente
            if ($paged < $total_pages) {
                $next_url = esc_url(add_query_arg('paged', $paged + 1, $base_url));
                echo '<a href="' . $next_url . '" title="Siguiente">&raquo;</a>';
            }
            echo '</div>';
        }
    }

    private static function get_doc_row($doc) {
        global $wpdb;
        $imported = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $doc['id']));
        $status_label = '____________';
        $status_class = '';
        $status_icon = '';
        $post_links = '—';
        $accion = '<span class="dashicons dashicons-clock" style="color:#0073aa;vertical-align:middle;"></span> <a href="' . admin_url('admin.php?page=g2wpi-importador&import=' . $doc['id']) . '" class="button">Importar</a>';
        $fecha = '—';
        $post_type_label = '—';
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
                $post_type_label = ($post->post_type === 'post') ? 'post' : $post->post_type;
            }
        }
        return [$accion, $post_links, $status_label, $status_class, $status_icon, $post_type_label, $fecha];
    }
}
