<?php
// Clase para la autenticación OAuth con Google
class G2WPI_OAuth {
    public function __construct() {
        add_action('admin_post_g2wpi_oauth_callback', [$this, 'handle_oauth_callback']);
    }

    public function handle_oauth_callback() {
        $settings = get_option(G2WPI_OPTION_NAME);
        if (!isset($_GET['code'])) wp_die('Falta el código de autorización.');
        $code = sanitize_text_field($_GET['code']);
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
                wp_redirect(admin_url('admin.php?page=g2wpi-ajustes&auth=success'));
                exit;
            }
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
        if (!isset($tokens['refresh_token'])) return false;
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
                return $data;
            }
        }
        return false;
    }
}
