<?php
// Clase para la gestión de la base de datos personalizada
require_once __DIR__ . '/class-g2wpi-logger.php';
class G2WPI_DB {
    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS " . G2WPI_TABLE_NAME . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            google_doc_id VARCHAR(255) NOT NULL UNIQUE,
            post_id BIGINT(20),
            imported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'success',
            message TEXT DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        try {
            G2WPI_Logger::log('Intentando crear tabla personalizada con dbDelta', 'DEBUG');
            dbDelta($sql);
            G2WPI_Logger::log('Tabla personalizada creada o actualizada correctamente', 'INFO');
        } catch (Throwable $e) {
            G2WPI_Logger::log('Error al crear tabla personalizada: ' . $e->getMessage(), 'ERROR');
        }
    }
}
