<?php
if (!defined('ABSPATH')) exit;

class G2WPI_Docs_Filters {
    public static function render_filters() {
        global $g2wpi_selected_author, $g2wpi_selected_status, $g2wpi_selected_post_type, $g2wpi_selected_term;
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
        echo '<form method="get" class="g2wpi-import-options" action="">';
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
        echo '<div style="font-size:10px;color:#666;margin-top:4px;line-height:1.2;">' . esc_html__('Las opciones seleccionadas se guardan y se aplicarán a todas las importaciones hasta que las cambies.', 'google-docs-importer') . '</div>';
        echo '</form>';
    }
}
