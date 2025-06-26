<?php
interface HtmlCleanerInterface {
    public function clean($html);
}

require_once __DIR__ . '/class-g2wpi-logger.php';
class G2WPI_HtmlCleaner implements HtmlCleanerInterface {
    public function clean($html) {
        try {
            G2WPI_Logger::log('Inicio limpieza HTML', 'DEBUG');
            // 1. Convertir títulos (h1, h2, h3) según estilos comunes de Google Docs
            $html = preg_replace(
                '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*2[4-9]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
                '<h1>$1</h1>', $html);
            $html = preg_replace(
                '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*1[8-9]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
                '<h2>$1</h2>', $html);
            $html = preg_replace(
                '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*2[0-3]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
                '<h2>$1</h2>', $html);
            $html = preg_replace(
                '/<p[^>]*><span[^>]*style="[^"]*font-size:\s*1[4-7]pt;[^"]*font-weight:\s*700;[^"]*"[^>]*>(.*?)<\/span><\/p>/is',
                '<h3>$1</h3>', $html);
            // Convertir spans con negrita/itálica a <strong>/<em>
            $html = preg_replace_callback(
                '/<span([^>]*)style="([^"]*)"([^>]*)>(.*?)<\/span>/is',
                function($matches) {
                    $style = strtolower($matches[2]);
                    $content = $matches[4];
                    $is_bold = (strpos($style, 'font-weight:700') !== false || strpos($style, 'font-weight:bold') !== false);
                    $is_italic = (strpos($style, 'font-style:italic') !== false);
                    if ($is_bold && $is_italic) {
                        return '<strong><em>' . $content . '</em></strong>';
                    } elseif ($is_bold) {
                        return '<strong>' . $content . '</strong>';
                    } elseif ($is_italic) {
                        return '<em>' . $content . '</em>';
                    } else {
                        return $content;
                    }
                },
                $html
            );
            $html = preg_replace('/<p[^>]*>(.*?)<\/p>/is', '<p>$1</p>', $html);
            $html = preg_replace('/<(span|p)[^>]*style="[^"]*"[^>]*>/i', '<$1>', $html);
            $html = preg_replace('/<span>\s*<\/span>/i', '', $html);
            $html = preg_replace('/<(span|p)[^>]*class="[^"]*"[^>]*>/i', '<$1>', $html);
            G2WPI_Logger::log('Limpieza HTML completada', 'DEBUG');
            return $html;
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en clean de G2WPI_HtmlCleaner: ' . $e->getMessage(), 'ERROR');
            return $html;
        }
    }
}
