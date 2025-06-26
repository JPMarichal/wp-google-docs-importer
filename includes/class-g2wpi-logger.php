<?php
// Utilidad centralizada para logueo del plugin Google Docs Importer
class G2WPI_Logger {
    const LOG_FILE = __DIR__ . '/../g2wpi.log';
    const MAX_DAYS = 30;

    /**
     * Escribe un mensaje en el log, con nivel y fecha. Limpia logs antiguos.
     * @param string $message
     * @param string $level
     */
    public static function log($message, $level = 'INFO') {
        $date = date('Y-m-d H:i:s');
        $line = "[$date][$level] $message\n";
        self::cleanup();
        file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Elimina líneas del log con fecha anterior a MAX_DAYS.
     */
    private static function cleanup() {
        if (!file_exists(self::LOG_FILE)) return;
        $lines = file(self::LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $cutoff = strtotime('-' . self::MAX_DAYS . ' days');
        $filtered = array_filter($lines, function($line) use ($cutoff) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})\]/', $line, $m)) {
                $ts = strtotime($m[1] . ' ' . $m[2]);
                return $ts >= $cutoff;
            }
            return true;
        });
        if (count($filtered) !== count($lines)) {
            file_put_contents(self::LOG_FILE, implode("\n", $filtered) . "\n");
        }
    }
}
