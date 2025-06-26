<?php
if (!defined('ABSPATH')) exit;

class G2WPI_Docs_Sorter {
    public static function get_sorted_docs() {
        global $wpdb;
        $settings = get_option(G2WPI_OPTION_NAME);
        $folder_id = $settings['folder_id'] ?? '';
        $docs = $folder_id ? get_transient('g2wpi_drive_docs_' . $folder_id) : false;
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
        $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'desc' : 'asc';
        if ($docs && is_array($docs)) {
            usort($docs, function($a, $b) use ($orderby, $order, $wpdb) {
                if ($orderby === 'imported') {
                    $importedA = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $a['id']));
                    $importedB = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . G2WPI_TABLE_NAME . " WHERE google_doc_id = %s", $b['id']));
                    if ($importedA == $importedB) return 0;
                    if ($order === 'asc') {
                        return $importedA - $importedB;
                    } else {
                        return $importedB - $importedA;
                    }
                } else if ($orderby === 'status') {
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
        return [$docs, $orderby, $order];
    }
}
