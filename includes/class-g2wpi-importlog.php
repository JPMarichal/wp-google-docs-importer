<?php
require_once __DIR__ . '/class-g2wpi-logger.php';
class G2WPI_ImportLog {
    public function log_import($doc_id, $post_id, $status = 'success', $message = null) {
        global $wpdb;
        try {
            if (defined('G2WPI_TABLE_NAME')) {
                $result = $wpdb->insert(G2WPI_TABLE_NAME, [
                    'google_doc_id' => $doc_id,
                    'post_id' => $post_id,
                    'imported_at' => current_time('mysql', 1),
                    'status' => $status,
                    'message' => $message
                ]);
                if ($result === false) {
                    G2WPI_Logger::log('Error al insertar log de importación en la base de datos: ' . $wpdb->last_error, 'ERROR');
                } else {
                    G2WPI_Logger::log("Log de importación insertado: doc_id=$doc_id post_id=$post_id status=$status", 'DEBUG');
                }
                // Eliminar logs más antiguos que 1 mes
                $delete_result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM ".G2WPI_TABLE_NAME." WHERE imported_at < %s",
                    date('Y-m-d H:i:s', strtotime('-1 month'))
                ));
                if ($delete_result === false) {
                    G2WPI_Logger::log('Error al eliminar logs antiguos de importación: ' . $wpdb->last_error, 'ERROR');
                } else {
                    G2WPI_Logger::log('Logs antiguos de importación eliminados correctamente', 'DEBUG');
                }
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en log_import: ' . $e->getMessage(), 'ERROR');
        }
    }
}
