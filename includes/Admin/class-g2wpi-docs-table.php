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
            .g2wpi-docs-table th { padding: 8px 12px; }
            .g2wpi-docs-table .nombre-columna { width: 40%; }
            .g2wpi-docs-table .g2wpi-center { width: 10%; }
            .g2wpi-docs-table .g2wpi-table-actions { white-space: nowrap; }
            .g2wpi-import-options { margin: 10px 0 18px 0; font-size: 12px; display: flex; gap: 12px; align-items: center; }
            .g2wpi-import-options label { font-weight: 500; margin-right: 4px; }
            .g2wpi-import-options select { font-size: 12px; padding: 2px 6px; height: 24px; }
            .g2wpi-import-options button { font-size: 12px; padding: 2px 10px; height: 24px; }
        </style>';
        echo '<span class="g2wpi-table-sep"></span>';
        $settings = get_option(G2WPI_OPTION_NAME);
        $folder_id = $settings['folder_id'] ?? '';
        $docs = $folder_id ? get_transient('g2wpi_drive_docs_' . $folder_id) : false;
        // Obtener parámetros de ordenamiento
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
        $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'desc' : 'asc';
        // Ordenar los documentos por nombre
        if ($docs && is_array($docs)) {
            usort($docs, function($a, $b) use ($orderby, $order, $wpdb) {
                if ($orderby === 'imported') {
                    // Buscar si cada doc está importado
                    $importedA = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $a['id']));
                    $importedB = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $b['id']));
                    // Importados primero si desc, no importados primero si asc
                    if ($importedA == $importedB) return 0;
                    if ($order === 'asc') {
                        return $importedA - $importedB;
                    } else {
                        return $importedB - $importedA;
                    }
                } else if ($orderby === 'status') {
                    // Obtener status de cada post (si existe)
                    $statusA = '';
                    $statusB = '';
                    $importedA = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $a['id']));
                    $importedB = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $b['id']));
                    if ($importedA && $importedA->post_id) {
                        $postA = get_post($importedA->post_id);
                        $statusA = $postA ? $postA->post_status : '';
                    }
                    if ($importedB && $importedB->post_id) {
                        $postB = get_post($importedB->post_id);
                        $statusB = $postB ? $postB->post_status : '';
                    }
                    $cmp = strcasecmp($statusA, $statusB);
                    return $order === 'asc' ? $cmp : -$cmp;
                } else if ($orderby === 'type') {
                    // Obtener tipo de post (post_type)
                    $typeA = '';
                    $typeB = '';
                    $importedA = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $a['id']));
                    $importedB = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $b['id']));
                    if ($importedA && $importedA->post_id) {
                        $postA = get_post($importedA->post_id);
                        $typeA = $postA ? $postA->post_type : '';
                    }
                    if ($importedB && $importedB->post_id) {
                        $postB = get_post($importedB->post_id);
                        $typeB = $postB ? $postB->post_type : '';
                    }
                    $cmp = strcasecmp($typeA, $typeB);
                    return $order === 'asc' ? $cmp : -$cmp;
                } else if ($orderby === 'category') {
                    // Obtener categoría principal o término principal
                    $catA = '';
                    $catB = '';
                    $importedA = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $a['id']));
                    $importedB = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $b['id']));
                    if ($importedA && $importedA->post_id) {
                        $postA = get_post($importedA->post_id);
                        if ($postA) {
                            if ($postA->post_type === 'post') {
                                $cats = get_the_category($postA->ID);
                                $catA = (!empty($cats)) ? $cats[0]->name : '';
                            } else {
                                $taxonomies = get_object_taxonomies($postA->post_type, 'objects');
                                $main_tax = null;
                                foreach ($taxonomies as $tax) {
                                    if ($tax->hierarchical) { $main_tax = $tax->name; break; }
                                }
                                if ($main_tax) {
                                    $terms = get_the_terms($postA->ID, $main_tax);
                                    $catA = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';
                                }
                            }
                        }
                    }
                    if ($importedB && $importedB->post_id) {
                        $postB = get_post($importedB->post_id);
                        if ($postB) {
                            if ($postB->post_type === 'post') {
                                $cats = get_the_category($postB->ID);
                                $catB = (!empty($cats)) ? $cats[0]->name : '';
                            } else {
                                $taxonomies = get_object_taxonomies($postB->post_type, 'objects');
                                $main_tax = null;
                                foreach ($taxonomies as $tax) {
                                    if ($tax->hierarchical) { $main_tax = $tax->name; break; }
                                }
                                if ($main_tax) {
                                    $terms = get_the_terms($postB->ID, $main_tax);
                                    $catB = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';
                                }
                            }
                        }
                    }
                    $cmp = strcasecmp($catA, $catB);
                    return $order === 'asc' ? $cmp : -$cmp;
                } else if ($orderby === 'date') {
                    // Ordenar por fecha de importación (imported_at)
                    $dateA = '';
                    $dateB = '';
                    $importedA = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $a['id']));
                    $importedB = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $b['id']));
                    if ($importedA && isset($importedA->imported_at)) {
                        $dateA = $importedA->imported_at;
                    }
                    if ($importedB && isset($importedB->imported_at)) {
                        $dateB = $importedB->imported_at;
                    }
                    $cmp = strcmp($dateA, $dateB);
                    return $order === 'asc' ? $cmp : -$cmp;
                } else {
                    $valA = isset($a[$orderby]) ? $a[$orderby] : '';
                    $valB = isset($b[$orderby]) ? $b[$orderby] : '';
                    $cmp = strcasecmp($valA, $valB);
                    return $order === 'asc' ? $cmp : -$cmp;
                }
            });
        }
        $total_docs = is_array($docs) ? count($docs) : 0;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        $docs_page = ($docs && is_array($docs)) ? array_slice($docs, $offset, $per_page) : [];
        // Mostrar el nombre de la carpeta seleccionada justo ANTES de la tabla y la toolbar
        // (El bloque de impresión del nombre de la carpeta se ha movido fuera de esta función para ubicarse entre el h1 y la toolbar)
        // Formulario compacto de selección
        // --- PERSISTENCIA DE OPCIONES DE IMPORTACIÓN ---
        // (session_start() ahora se maneja en el archivo principal del plugin)
        global $g2wpi_selected_author, $g2wpi_selected_status, $g2wpi_selected_post_type, $g2wpi_selected_term;
        // Usar $_GET si existen (acaba de enviar el formulario), si no, usar $_SESSION
        if (isset($_GET['g2wpi_author'])) {
            $g2wpi_selected_author = intval($_GET['g2wpi_author']);
            $_SESSION['g2wpi_author'] = $g2wpi_selected_author;
        } else {
            $g2wpi_selected_author = isset($_SESSION['g2wpi_author']) ? intval($_SESSION['g2wpi_author']) : get_current_user_id();
        }
        if (isset($_GET['g2wpi_status'])) {
            $g2wpi_selected_status = sanitize_text_field($_GET['g2wpi_status']);
            $_SESSION['g2wpi_status'] = $g2wpi_selected_status;
        } else {
            $g2wpi_selected_status = isset($_SESSION['g2wpi_status']) ? sanitize_text_field($_SESSION['g2wpi_status']) : 'draft';
        }
        if (isset($_GET['g2wpi_post_type'])) {
            $g2wpi_selected_post_type = sanitize_text_field($_GET['g2wpi_post_type']);
            $_SESSION['g2wpi_post_type'] = $g2wpi_selected_post_type;
        } else {
            $g2wpi_selected_post_type = isset($_SESSION['g2wpi_post_type']) ? sanitize_text_field($_SESSION['g2wpi_post_type']) : 'post';
        }
        if (isset($_GET['g2wpi_term'])) {
            $g2wpi_selected_term = intval($_GET['g2wpi_term']);
            $_SESSION['g2wpi_term'] = $g2wpi_selected_term;
        } else {
            $g2wpi_selected_term = isset($_SESSION['g2wpi_term']) ? intval($_SESSION['g2wpi_term']) : 0;
        }
        // En los selectores, usar SIEMPRE los valores globales
        echo '<form method="get" class="g2wpi-import-options" action="">';
        // Opciones de selección
        foreach ($_GET as $k => $v) {
            if (!in_array($k, ['g2wpi_author','g2wpi_status','g2wpi_post_type','g2wpi_term','paged'])) {
                echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '" />';
            }
        }
        echo '<label for="g2wpi_author">' . esc_html__('Autor:', 'google-docs-importer') . '</label>';
        echo '<select name="g2wpi_author" id="g2wpi_author">';
        $users = get_users([ 'who' => 'authors', 'orderby' => 'display_name' ]);
        foreach ($users as $user) {
            $selected = $g2wpi_selected_author == $user->ID ? 'selected' : '';
            echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
        }
        echo '</select>';
        echo '<label for="g2wpi_status">' . esc_html__('Estado:', 'google-docs-importer') . '</label>';
        echo '<select name="g2wpi_status" id="g2wpi_status">';
        $statuses = [
            'draft' => __('Borrador', 'google-docs-importer'),
            'pending' => __('Pendiente de revisión', 'google-docs-importer'),
            'publish' => __('Publicado', 'google-docs-importer'),
        ];
        foreach ($statuses as $key => $label) {
            $selected = $g2wpi_selected_status == $key ? 'selected' : '';
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        $post_types = get_post_types(['public' => true], 'objects');
        echo '<label for="g2wpi_post_type">' . esc_html__('Tipo:', 'google-docs-importer') . '</label>';
        echo '<select name="g2wpi_post_type" id="g2wpi_post_type" onchange="this.form.submit()">';
        foreach ($post_types as $pt) {
            $selected = $g2wpi_selected_post_type == $pt->name ? 'selected' : '';
            echo '<option value="' . esc_attr($pt->name) . '" ' . $selected . '>' . esc_html($pt->labels->singular_name) . '</option>';
        }
        echo '</select>';
        $taxonomies = get_object_taxonomies($g2wpi_selected_post_type, 'objects');
        $main_tax = null;
        foreach ($taxonomies as $tax) {
            if ($tax->hierarchical && strpos($tax->name, 'tag') === false) { $main_tax = $tax; break; }
        }
        echo '<label for="g2wpi_term">' . ($main_tax ? esc_html($main_tax->labels->singular_name) : esc_html__('Categoría', 'google-docs-importer')) . ':</label>';
        echo '<select name="g2wpi_term" id="g2wpi_term">';
        echo '<option value="0">' . esc_html__('Sin asignar', 'google-docs-importer') . '</option>';
        if ($main_tax) {
            $terms = get_terms([ 'taxonomy' => $main_tax->name, 'hide_empty' => false ]);
            foreach ($terms as $term) {
                $selected = $g2wpi_selected_term == $term->term_id ? 'selected' : '';
                echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
            }
        }
        echo '</select>';
        echo '<button type="submit">' . esc_html__('Aplicar', 'google-docs-importer') . '</button>';
        // Mensaje de ayuda bajo las opciones
        echo '<div style="font-size:10px;color:#666;margin-top:4px;line-height:1.2;">' . esc_html__('Las opciones seleccionadas se guardan y se aplicarán a todas las importaciones hasta que las cambies.', 'google-docs-importer') . '</div>';
        echo '</form>';
        echo '<table class="wp-list-table widefat fixed striped g2wpi-docs-table">';
        // Encabezado con ordenamiento para Nombre y para Importación
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
