<?php
/**
 * Plugin Name: Turnstile Registration Protection
 * Description: Schützt Registrierung und Login mit Cloudflare Turnstile vor Bots
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: turnstile-protection
 */

defined('ABSPATH') || exit;


class Turnstile_Protection {

    /** @var self|null */
    private static $instance = null;

    private function __construct() {
        add_action('plugins_loaded',        [$this, 'load_textdomain']);
        add_action('admin_menu',            [$this, 'add_admin_menu']);
        add_action('admin_init',            [$this, 'register_settings']);
        add_action('login_enqueue_scripts', [$this, 'enqueue_script']);
        add_action('register_form',         [$this, 'render_widget']);
        add_action('login_form',            [$this, 'render_widget']);
        add_filter('registration_errors',   [$this, 'verify_registration'], 10, 3);
        add_filter('authenticate',          [$this, 'verify_login'], 20, 3);
    }

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // --- Hooks -----------------------------------------------------------------

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'turnstile-protection',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function enqueue_script(): void {
        $action = sanitize_key(wp_unslash($_GET['action'] ?? 'login'));
        if (!$this->is_configured() || !in_array($action, ['login', 'register'], true)) {
            return;
        }
        wp_enqueue_script(
            'cf-turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            [],
            null,
            true
        );
    }

    public function render_widget(): void {
        if (!$this->is_configured()) {
            return;
        }
        printf(
            '<div class="turnstile-container" style="margin: 10px 0;"><div class="cf-turnstile" data-sitekey="%s"></div></div>',
            esc_attr($this->get_site_key())
        );
    }

    public function verify_registration(WP_Error $errors, string $user_login, string $user_email): WP_Error {
        // Intentional: fail-closed when unconfigured (blocks all registration).
        // verify_login passes through when unconfigured to prevent admin lockout.
        if (!$this->is_configured()) {
            $errors->add(
                'turnstile_not_configured',
                __('Turnstile ist nicht konfiguriert. Bitte kontaktieren Sie den Administrator.', 'turnstile-protection')
            );
            return $errors;
        }

        $result = $this->verify_token();
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }

        return $errors;
    }

    /**
     * @param WP_User|WP_Error|null $user
     * @return WP_User|WP_Error|null
     */
    public function verify_login($user, string $username, string $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        if (empty($username) || empty($password)) {
            return $user;
        }

        if (
            (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ||
            (defined('REST_REQUEST') && REST_REQUEST) ||
            (defined('WP_CLI') && WP_CLI)
        ) {
            return $user;
        }

        if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
            return $user;
        }

        if (!$this->is_configured()) {
            return $user;
        }

        $result = $this->verify_token();
        if (is_wp_error($result)) {
            return $result;
        }

        return $user;
    }

    // --- Admin -----------------------------------------------------------------

    public function add_admin_menu(): void {
        add_options_page(
            __('Turnstile Schutz', 'turnstile-protection'),
            __('Turnstile Schutz', 'turnstile-protection'),
            'manage_options',
            'turnstile-protection',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting('turnstile_protection_settings', 'turnstile_protection_site_key', [
            'sanitize_callback' => static function( string $value ): string {
                return trim( $value );
            },
        ]);
        register_setting('turnstile_protection_settings', 'turnstile_protection_secret_key', [
            'sanitize_callback' => static function( string $value ): string {
                return trim( $value );
            },
        ]);

        add_settings_section(
            'turnstile_protection_main',
            __('Cloudflare Turnstile Konfiguration', 'turnstile-protection'),
            null,
            'turnstile-protection'
        );

        add_settings_field(
            'turnstile_protection_site_key',
            __('Site Key', 'turnstile-protection'),
            [$this, 'render_site_key_field'],
            'turnstile-protection',
            'turnstile_protection_main'
        );

        add_settings_field(
            'turnstile_protection_secret_key',
            __('Secret Key', 'turnstile-protection'),
            [$this, 'render_secret_key_field'],
            'turnstile-protection',
            'turnstile_protection_main'
        );
    }

    public function render_site_key_field(): void {
        printf(
            '<input type="text" name="turnstile_protection_site_key" value="%s" class="regular-text">',
            esc_attr($this->get_site_key())
        );
    }

    public function render_secret_key_field(): void {
        printf(
            '<input type="password" name="turnstile_protection_secret_key" value="%s" class="regular-text">',
            esc_attr($this->get_secret_key())
        );
    }

    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('turnstile_protection_settings');
                do_settings_sections('turnstile-protection');
                submit_button();
                ?>
            </form>
            <p>
                <?php esc_html_e('Keys erhalten Sie unter:', 'turnstile-protection'); ?>
                <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener noreferrer">Cloudflare Turnstile</a>
            </p>
        </div>
        <?php
    }

    // --- Core verification -----------------------------------------------------

    /**
     * @return true|WP_Error
     */
    private function verify_token() {
        if (empty(wp_unslash($_POST['cf-turnstile-response'] ?? ''))) {
            return new WP_Error(
                'turnstile_missing',
                __('Bitte bestätigen Sie, dass Sie kein Roboter sind.', 'turnstile-protection')
            );
        }

        $remote_ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);

        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'timeout' => 10,
            'body'    => [
                'secret'   => $this->get_secret_key(),
                'response' => sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])),
                'remoteip' => $remote_ip ?: '',
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error(
                'turnstile_error',
                __('Verifizierung fehlgeschlagen. Bitte versuchen Sie es später erneut.', 'turnstile-protection')
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body) || empty($body['success'])) {
            return new WP_Error(
                'turnstile_failed',
                __('Verifizierung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'turnstile-protection')
            );
        }

        return true;
    }

    // --- Helpers ---------------------------------------------------------------

    private function get_site_key(): string {
        return (string) get_option('turnstile_protection_site_key', '');
    }

    private function get_secret_key(): string {
        return (string) get_option('turnstile_protection_secret_key', '');
    }

    private function is_configured(): bool {
        return !empty($this->get_site_key()) && !empty($this->get_secret_key());
    }

    // --- Lifecycle -------------------------------------------------------------

    public static function uninstall(): void {
        delete_option('turnstile_protection_site_key');
        delete_option('turnstile_protection_secret_key');
    }
}

Turnstile_Protection::get_instance();
register_uninstall_hook(__FILE__, ['Turnstile_Protection', 'uninstall']);
