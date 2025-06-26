<?php
class G2WPI_ImportLog {
    public function log_import($doc_id, $post_id, $status = 'success', $message = null) {
        global $wpdb;
        if (defined('G2WPI_TABLE_NAME')) {
            $wpdb->insert(G2WPI_TABLE_NAME, [
                'google_doc_id' => $doc_id,
                'post_id' => $post_id,
                'imported_at' => current_time('mysql', 1),
                'status' => $status,
                'message' => $message
            ]);
            // Eliminar logs más antiguos que 1 mes
            $wpdb->query($wpdb->prepare(
                "DELETE FROM ".G2WPI_TABLE_NAME." WHERE imported_at < %s",
                date('Y-m-d H:i:s', strtotime('-1 month'))
            ));
        }
    }
}
