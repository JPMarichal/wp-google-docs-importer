<?php
class G2WPI_ImportLog {
    public function log_import($doc_id, $post_id) {
        global $wpdb;
        if (defined('G2WPI_TABLE_NAME')) {
            $wpdb->insert(G2WPI_TABLE_NAME, [
                'google_doc_id' => $doc_id,
                'post_id' => $post_id,
                'imported_at' => current_time('mysql', 1)
            ]);
        }
    }
}
