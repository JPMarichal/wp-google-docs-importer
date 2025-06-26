<?php
// Clase para la autenticación OAuth con Google
require_once __DIR__ . '/class-g2wpi-logger.php';
class G2WPI_OAuth {
    public function __construct() {
        add_action('admin_post_g2wpi_oauth_callback', [$this, 'handle_oauth_callback']);
    }

    public function handle_oauth_callback() {
        $settings = get_option(G2WPI_OPTION_NAME);
        try {
            if (!isset($_GET['code'])) {
                G2WPI_Logger::log('Falta el código de autorización en callback OAuth.', 'ERROR');
                wp_die('Falta el código de autorización.');
            }
            $code = sanitize_text_field($_GET['code']);
            G2WPI_Logger::log('Intentando obtener token OAuth con code=' . $code, 'DEBUG');
            $response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => http_build_query([
                    'code' => $code,
                    'client_id' => $settings['client_id'],
                    'client_secret' => $settings['client_secret'],
                    'redirect_uri' => admin_url('admin-post.php?action=g2wpi_oauth_callback'),
                    'grant_type' => 'authorization_code'
                ])
            ]);
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['access_token'])) {
                    // Guardar también el refresh_token si está presente
                    if (isset($data['refresh_token'])) {
                        $data_to_save = $data;
                    } else {
                        // Si ya existe un refresh_token guardado, conservarlo
                        $old = get_option(G2WPI_TOKEN_OPTION);
                        $data_to_save = $data;
                        if (isset($old['refresh_token'])) {
                            $data_to_save['refresh_token'] = $old['refresh_token'];
                        }
                    }
                    update_option(G2WPI_TOKEN_OPTION, $data_to_save);
                    G2WPI_Logger::log('OAuth exitoso, token guardado.', 'INFO');
                    wp_redirect(admin_url('admin.php?page=g2wpi-ajustes&auth=success'));
                    exit;
                } else {
                    G2WPI_Logger::log('OAuth error: respuesta sin access_token. Respuesta: ' . print_r($data, true), 'ERROR');
                }
            } else {
                G2WPI_Logger::log('OAuth error: ' . print_r($response, true), 'ERROR');
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en handle_oauth_callback: ' . $e->getMessage(), 'ERROR');
        }
        wp_redirect(admin_url('admin.php?page=g2wpi-ajustes&auth=error'));
        exit;
    }

    /**
     * Refresca el access_token usando el refresh_token guardado.
     * Devuelve el nuevo array de tokens o false si falla.
     */
    public static function refresh_access_token() {
        $settings = get_option(G2WPI_OPTION_NAME);
        $tokens = get_option(G2WPI_TOKEN_OPTION);
        try {
            if (!isset($tokens['refresh_token'])) {
                G2WPI_Logger::log('No hay refresh_token disponible para refrescar access_token.', 'ERROR');
                return false;
            }
            G2WPI_Logger::log('Intentando refrescar access_token con refresh_token.', 'DEBUG');
            $response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => http_build_query([
                    'client_id' => $settings['client_id'],
                    'client_secret' => $settings['client_secret'],
                    'refresh_token' => $tokens['refresh_token'],
                    'grant_type' => 'refresh_token'
                ])
            ]);
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['access_token'])) {
                    // Mantener el refresh_token original
                    $data['refresh_token'] = $tokens['refresh_token'];
                    update_option(G2WPI_TOKEN_OPTION, $data);
                    G2WPI_Logger::log('Access_token refrescado correctamente.', 'INFO');
                    return $data;
                } else {
                    G2WPI_Logger::log('Error al refrescar access_token: respuesta sin access_token. Respuesta: ' . print_r($data, true), 'ERROR');
                }
            } else {
                G2WPI_Logger::log('Error en wp_remote_post al refrescar access_token: ' . print_r($response, true), 'ERROR');
            }
        } catch (Throwable $e) {
            G2WPI_Logger::log('Excepción en refresh_access_token: ' . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}
