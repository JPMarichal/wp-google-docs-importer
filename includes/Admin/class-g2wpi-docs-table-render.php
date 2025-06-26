<?php
if (!defined('ABSPATH')) exit;

class G2WPI_Docs_Table_Render {
    public static function render_table($docs_page, $docs, $total_docs, $per_page, $paged, $orderby, $order) {
        echo '<table class="wp-list-table widefat fixed striped g2wpi-docs-table">';
        $current_url = esc_url_raw(remove_query_arg(['orderby', 'order', 'paged']));
        $name_order = ($orderby === 'name' && $order === 'asc') ? 'desc' : 'asc';
        $name_arrow = ($orderby === 'name') ? ($order === 'asc' ? ' <span style="font-size:12px">&#9650;</span>' : ' <span style="font-size:12px">&#9660;</span>') : '';
        $import_order = ($orderby === 'imported' && $order === 'asc') ? 'desc' : 'asc';
        $import_arrow = ($orderby === 'imported') ? ($order === 'asc' ? ' <span style="font-size:12px">&#9650;</span>' : ' <span style="font-size:12px">&#9660;</span>') : '';
        $status_order = ($orderby === 'status' && $order === 'asc') ? 'desc' : 'asc';
        $status_arrow = ($orderby === 'status') ? ($order === 'asc' ? ' <span style="font-size:12px">&#9650;</span>' : ' <span style="font-size:12px">&#9660;</span>') : '';
        $type_order = ($orderby === 'type' && $order === 'asc') ? 'desc' : 'asc';
        $type_arrow = ($orderby === 'type') ? ($order === 'asc' ? ' <span style="font-size:12px">&#9650;</span>' : ' <span style="font-size:12px">&#9660;</span>') : '';
        $category_order = ($orderby === 'category' && $order === 'asc') ? 'desc' : 'asc';
        $category_arrow = ($orderby === 'category') ? ($order === 'asc' ? ' <span style="font-size:12px">&#9650;</span>' : ' <span style="font-size:12px">&#9660;</span>') : '';
        $date_order = ($orderby === 'date' && $order === 'asc') ? 'desc' : 'asc';
        $date_arrow = ($orderby === 'date') ? ($order === 'asc' ? ' <span style="font-size:12px">&#9650;</span>' : ' <span style="font-size:12px">&#9660;</span>') : '';
        echo '<thead><tr>';
        echo '<th class="nombre-columna"><a href="' . add_query_arg(['orderby' => 'name', 'order' => $name_order], $current_url) . '">' . esc_html__('Nombre', 'google-docs-importer') . $name_arrow . '</a></th>';
        echo '<th class="g2wpi-center"><a href="' . add_query_arg(['orderby' => 'imported', 'order' => $import_order], $current_url) . '">' . esc_html__('Importación', 'google-docs-importer') . $import_arrow . '</a></th>';
        echo '<th class="g2wpi-center">' . esc_html__('Acciones', 'google-docs-importer') . '</th>';
        echo '<th class="g2wpi-center"><a href="' . add_query_arg(['orderby' => 'status', 'order' => $status_order], $current_url) . '">' . esc_html__('Status', 'google-docs-importer') . $status_arrow . '</a></th>';
        echo '<th><a href="' . add_query_arg(['orderby' => 'type', 'order' => $type_order], $current_url) . '">' . esc_html__('Tipo', 'google-docs-importer') . $type_arrow . '</a></th>';
        echo '<th><a href="' . add_query_arg(['orderby' => 'category', 'order' => $category_order], $current_url) . '">' . esc_html__('Categoría', 'google-docs-importer') . $category_arrow . '</a></th>';
        echo '<th><a href="' . add_query_arg(['orderby' => 'date', 'order' => $date_order], $current_url) . '">' . esc_html__('Fecha', 'google-docs-importer') . $date_arrow . '</a></th>';
        echo '</tr></thead>';
        echo '<tbody>';
        if (!$docs || !is_array($docs)) {
            echo '<tr><td colspan="7">' . esc_html__('Haz clic en el botón -Actualizar listado- para obtener los documentos.', 'google-docs-importer') . '</td></tr>';
        } else {
            foreach ($docs_page as $doc) {
                list($accion, $post_links, $status_label, $status_class, $status_icon, $post_type_label, $category_label, $fecha) = self::get_doc_row($doc);
                $doc_url = 'https://docs.google.com/document/d/' . $doc['id'] . '/edit';
                $nombre = '<a href="' . esc_url($doc_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($doc['name']) . '</a>';
                echo '<tr>';
                echo '<td class="nombre-columna">' . $nombre . '</td>';
                echo '<td class="g2wpi-table-actions">' . $accion . '</td>';
                echo '<td class="g2wpi-table-actions">' . $post_links . '</td>';
                echo '<td class="g2wpi-status ' . esc_attr($status_class) . '">' . $status_icon . esc_html($status_label) . '</td>';
                echo '<td>' . esc_html($post_type_label) . '</td>';
                echo '<td>' . esc_html($category_label) . '</td>';
                echo '<td>' . esc_html($fecha) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        if ($docs && is_array($docs) && $total_docs > $per_page) {
            $total_pages = ceil($total_docs / $per_page);
            $base_url = remove_query_arg('paged');
            echo '<div class="g2wpi-pagination">';
            $show = 2;
            $ellipsis = false;
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
            if ($paged < $total_pages) {
                $next_url = esc_url(add_query_arg('paged', $paged + 1, $base_url));
                echo '<a href="' . $next_url . '" title="Siguiente">&raquo;</a>';
            }
            echo '</div>';
        }
    }

    public static function get_doc_row($doc) {
        global $wpdb, $g2wpi_selected_author, $g2wpi_selected_status, $g2wpi_selected_post_type, $g2wpi_selected_term;
        $imported = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $doc['id']));
        $status_label = __('____________', 'google-docs-importer');
        $status_class = '';
        $status_icon = '';
        $post_links = '—';
        // Usar los valores globales persistentes
        $author = $g2wpi_selected_author;
        $status = $g2wpi_selected_status;
        $post_type = $g2wpi_selected_post_type;
        $term = $g2wpi_selected_term;
        $import_url = admin_url('admin.php?page=g2wpi-importador&import=' . $doc['id'] . '&g2wpi_author=' . $author . '&g2wpi_status=' . $status . '&g2wpi_post_type=' . $post_type . '&g2wpi_term=' . $term);
        $accion = '<span class="dashicons dashicons-clock" style="color:#0073aa;vertical-align:middle;"></span> <a href="' . esc_url($import_url) . '" class="button">' . esc_html__('Importar', 'google-docs-importer') . '</a>';
        $fecha = '—';
        $post_type_label = '—';
        $category_label = '—';
        if ($imported) {
            $post_id = $imported->post_id;
            $post = get_post($post_id);
            if ($post) {
                $status = $post->post_status;
                switch ($status) {
                    case 'publish':
                        $status_label = __('Publicado', 'google-docs-importer');
                        $status_class = 'g2wpi-status-publish';
                        $status_icon = '<span class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle;"></span> ';
                        break;
                    case 'draft':
                        $status_label = __('Borrador', 'google-docs-importer');
                        $status_class = 'g2wpi-status-draft';
                        $status_icon = '<span class="dashicons dashicons-edit" style="color:#dba617;vertical-align:middle;"></span> ';
                        break;
                    case 'pending':
                        $status_label = __('Pendiente', 'google-docs-importer');
                        $status_class = 'g2wpi-status-pending';
                        $status_icon = '<span class="dashicons dashicons-clock" style="color:#0073aa;vertical-align:middle;"></span> ';
                        break;
                    case 'future':
                        $status_label = __('Programado', 'google-docs-importer');
                        $status_class = 'g2wpi-status-future';
                        $status_icon = '<span class="dashicons dashicons-calendar-alt" style="color:#0073aa;vertical-align:middle;"></span> ';
                        break;
                    case 'private':
                        $status_label = __('Privado', 'google-docs-importer');
                        $status_class = 'g2wpi-status-private';
                        $status_icon = '<span class="dashicons dashicons-lock" style="color:#666;vertical-align:middle;"></span> ';
                        break;
                    case 'inherit':
                        $status_label = __('Heredado', 'google-docs-importer');
                        $status_class = 'g2wpi-status-inherit';
                        $status_icon = '<span class="dashicons dashicons-admin-multisite" style="color:#888;vertical-align:middle;"></span> ';
                        break;
                    case 'auto-draft':
                        $status_label = __('Auto-borrador', 'google-docs-importer');
                        $status_class = 'g2wpi-status-autodraft';
                        $status_icon = '<span class="dashicons dashicons-welcome-write-blog" style="color:#aaa;vertical-align:middle;"></span> ';
                        break;
                    case 'trash':
                        $status_label = __('Papelera', 'google-docs-importer');
                        $status_class = 'g2wpi-status-trash';
                        $status_icon = '<span class="dashicons dashicons-trash" style="color:#dc3232;vertical-align:middle;"></span> ';
                        break;
                    case 'revision':
                        $status_label = __('Revisión', 'google-docs-importer');
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
                $post_links = '<a href="' . esc_url($view_url) . '" class="g2wpi-action-icon dashicons dashicons-visibility" title="' . esc_attr__('Ver', 'google-docs-importer') . '" target="_blank" style="color:#0073aa;"></a>';
                $post_links .= '<a href="' . esc_url($edit_url) . '" class="g2wpi-action-icon dashicons dashicons-edit" title="' . esc_attr__('Editar', 'google-docs-importer') . '" target="_blank" style="color:#dba617;"></a>';
                $post_links .= '<a href="' . esc_url($delete_url) . '" class="g2wpi-action-icon dashicons dashicons-trash" title="' . esc_attr__('Eliminar', 'google-docs-importer') . '" onclick="return confirm(\'' . esc_js(__('¿Seguro que deseas eliminar este post importado?', 'google-docs-importer')) . '\');" style="color:#dc3232;"></a>';
                $accion = '<span class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle;"></span> ' . esc_html__('Importado', 'google-docs-importer');
                $fecha = $imported->imported_at;
                $post_type_label = ($post->post_type === 'post') ? 'post' : $post->post_type;
                // Obtener categoría principal (solo para posts estándar)
                if ($post->post_type === 'post') {
                    $cats = get_the_category($post_id);
                    if (!empty($cats)) {
                        $category_label = $cats[0]->name;
                    } else {
                        $category_label = esc_html__('Sin categoría', 'google-docs-importer');
                    }
                } else {
                    // Para CPT, intentar obtener la taxonomía principal si existe
                    $taxonomies = get_object_taxonomies($post->post_type, 'objects');
                    $main_tax = null;
                    foreach ($taxonomies as $tax) {
                        if ($tax->hierarchical) { $main_tax = $tax->name; break; }
                    }
                    if ($main_tax) {
                        $terms = get_the_terms($post_id, $main_tax);
                        if (!empty($terms) && !is_wp_error($terms)) {
                            $category_label = $terms[0]->name;
                        } else {
                            $category_label = esc_html__('Sin término', 'google-docs-importer');
                        }
                    } else {
                        $category_label = '—';
                    }
                }
            }
        }
        return [$accion, $post_links, $status_label, $status_class, $status_icon, $post_type_label, $category_label, $fecha];
    }
}
