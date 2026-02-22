<?php
/**
 * Plugin Name: Turnstile Registration Protection
 * Description: Protects registration and login with Cloudflare Turnstile against bots
 * Version: 1.1.0
 * Author: Your Name
 * License: MIT
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Text Domain: turnstile-protection
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;


class Turnstile_Protection {

    /** @var self|null */
    private static $instance = null;

    private function __construct() {
        add_action('plugins_loaded',        [$this, 'load_textdomain']);
        add_action('admin_menu',            [$this, 'add_admin_menu']);
        add_action('admin_init',            [$this, 'register_settings']);
        add_action('admin_notices',         [$this, 'activation_notice']);
        add_action('login_enqueue_scripts', [$this, 'enqueue_script']);
        add_action('register_form',         [$this, 'render_widget']);
        add_action('login_form',            [$this, 'render_widget']);
        add_action('lostpassword_form',     [$this, 'render_widget']);
        add_filter('registration_errors',   [$this, 'verify_registration'], 10, 3);
        add_filter('authenticate',          [$this, 'verify_login'], 20, 3);
        add_action('lostpassword_post',     [$this, 'verify_lostpassword']);
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
        if (!$this->is_configured() || !in_array($action, ['login', 'register', 'lostpassword'], true)) {
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
                __('Turnstile is not configured. Please contact the administrator.', 'turnstile-protection')
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

        // Bypass for Application Passwords (HTTP Basic Auth without form login)
        if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) && empty( $_POST['log'] ) ) {
            return $user;
        }

        if (!$this->is_configured()) {
            return $user;
        }

        $result = $this->verify_token( fail_open: true );
        if (is_wp_error($result)) {
            return $result;
        }

        return $user;
    }

    public function verify_lostpassword(WP_Error $errors): void {
        if (!$this->is_configured()) {
            $errors->add(
                'turnstile_not_configured',
                __('Turnstile is not configured. Please contact the administrator.', 'turnstile-protection')
            );
            return;
        }

        $result = $this->verify_token();
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }
    }

    // --- Admin -----------------------------------------------------------------

    public function add_admin_menu(): void {
        add_options_page(
            __('Turnstile Protection', 'turnstile-protection'),
            __('Turnstile Protection', 'turnstile-protection'),
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
            __('Cloudflare Turnstile Configuration', 'turnstile-protection'),
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
                <?php esc_html_e('Get your keys at:', 'turnstile-protection'); ?>
                <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener noreferrer">Cloudflare Turnstile</a>
            </p>
        </div>
        <?php
    }

    // --- Core verification -----------------------------------------------------

    /**
     * @param bool $fail_open Whether to allow through on network errors (used for login).
     * @return true|WP_Error
     */
    private function verify_token( bool $fail_open = false ) {
        if (empty(wp_unslash($_POST['cf-turnstile-response'] ?? ''))) {
            return new WP_Error(
                'turnstile_missing',
                __('Please confirm that you are not a robot.', 'turnstile-protection')
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
            if ( $fail_open ) {
                return true;
            }
            return new WP_Error(
                'turnstile_error',
                __('Verification failed. Please try again later.', 'turnstile-protection')
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body) || empty($body['success'])) {
            return new WP_Error(
                'turnstile_failed',
                __('Verification failed. Please try again.', 'turnstile-protection')
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

    public function activation_notice(): void {
        if (!get_transient('turnstile_protection_activated') || $this->is_configured()) {
            return;
        }
        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
            esc_html__('Turnstile Protection is activated but not yet configured.', 'turnstile-protection'),
            esc_url(admin_url('options-general.php?page=turnstile-protection')),
            esc_html__('Configure now', 'turnstile-protection')
        );
    }

    // --- Lifecycle -------------------------------------------------------------

    public static function activate(): void {
        set_transient('turnstile_protection_activated', true, 30);
    }

    public static function deactivate(): void {
        delete_transient('turnstile_protection_activated');
    }

    public static function uninstall(): void {
        delete_option('turnstile_protection_site_key');
        delete_option('turnstile_protection_secret_key');
    }
}

Turnstile_Protection::get_instance();
register_activation_hook(__FILE__, ['Turnstile_Protection', 'activate']);
register_deactivation_hook(__FILE__, ['Turnstile_Protection', 'deactivate']);
register_uninstall_hook(__FILE__, ['Turnstile_Protection', 'uninstall']);
