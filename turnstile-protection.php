<?php
/**
 * Plugin Name: Turnstile Registration Protection
 * Description: Protects user registration with Cloudflare Turnstile
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: turnstile-protection
 */

defined('ABSPATH') || exit;

define('TURNSTILE_PROTECTION_VERSION', '1.0.0');

add_action('admin_menu', 'turnstile_protection_add_admin_menu');
add_action('admin_init', 'turnstile_protection_register_settings');
add_action('login_enqueue_scripts', 'turnstile_protection_enqueue_script');
add_action('register_form', 'turnstile_protection_add_field');
add_filter('registration_errors', 'turnstile_protection_verify', 10, 3);

function turnstile_protection_add_admin_menu() {
    add_options_page(
        __('Turnstile Schutz', 'turnstile-protection'),
        __('Turnstile Schutz', 'turnstile-protection'),
        'manage_options',
        'turnstile-protection',
        'turnstile_protection_settings_page'
    );
}

function turnstile_protection_register_settings() {
    register_setting('turnstile_protection_settings', 'turnstile_protection_site_key');
    register_setting('turnstile_protection_settings', 'turnstile_protection_secret_key');

    add_settings_section(
        'turnstile_protection_main',
        __('Cloudflare Turnstile Konfiguration', 'turnstile-protection'),
        null,
        'turnstile-protection'
    );

    add_settings_field(
        'turnstile_protection_site_key',
        __('Site Key', 'turnstile-protection'),
        'turnstile_protection_site_key_render',
        'turnstile-protection',
        'turnstile_protection_main'
    );

    add_settings_field(
        'turnstile_protection_secret_key',
        __('Secret Key', 'turnstile-protection'),
        'turnstile_protection_secret_key_render',
        'turnstile-protection',
        'turnstile_protection_main'
    );
}

function turnstile_protection_site_key_render() {
    $value = get_option('turnstile_protection_site_key', '');
    printf(
        '<input type="text" name="turnstile_protection_site_key" value="%s" class="regular-text">',
        esc_attr($value)
    );
}

function turnstile_protection_secret_key_render() {
    $value = get_option('turnstile_protection_secret_key', '');
    printf(
        '<input type="password" name="turnstile_protection_secret_key" value="%s" class="regular-text">',
        esc_attr($value)
    );
}

function turnstile_protection_settings_page() {
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
            <?php _e('Keys erhalten Sie unter:', 'turnstile-protection'); ?>
            <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">Cloudflare Turnstile</a>
        </p>
    </div>
    <?php
}

function turnstile_protection_enqueue_script() {
    if (!get_option('turnstile_protection_site_key')) {
        return;
    }

    wp_enqueue_script(
        'turnstile-protection',
        'https://challenges.cloudflare.com/turnstile/v0/api.js',
        array(),
        null,
        true
    );
}

function turnstile_protection_add_field() {
    $site_key = get_option('turnstile_protection_site_key');
    if (!$site_key) {
        return;
    }
    ?>
    <div class="turnstile-container" style="margin: 10px 0;">
        <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
    </div>
    <?php
}

function turnstile_protection_verify($errors, $sanitized_user_login, $user_email) {
    $secret_key = get_option('turnstile_protection_secret_key');
    
    if (!$secret_key) {
        $errors->add(
            'turnstile_not_configured',
            __('Turnstile ist nicht konfiguriert. Bitte kontaktieren Sie den Administrator.', 'turnstile-protection')
        );
        return $errors;
    }

    if (empty($_POST['cf-turnstile-response'])) {
        $errors->add(
            'turnstile_missing',
            __('Bitte bestätigen Sie, dass Sie kein Roboter sind.', 'turnstile-protection')
        );
        return $errors;
    }

    $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
        'body' => array(
            'secret'   => $secret_key,
            'response' => sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])),
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ),
    ));

    if (is_wp_error($response)) {
        $errors->add(
            'turnstile_error',
            __('Verifizierung fehlgeschlagen. Bitte versuchen Sie es später erneut.', 'turnstile-protection')
        );
        return $errors;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['success'])) {
        $errors->add(
            'turnstile_failed',
            __('Verifizierung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'turnstile-protection')
        );
    }

    return $errors;
}
