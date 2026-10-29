<?php
/**
 * Plugin Name: LKBA Vouchers
 * Plugin URI: https://github.com/kubasanitrak/lkba-vouchers
 * Description: Gift vouchers for Lenka Krejčová Barre Academy. Requires LKBA Payments.
 * Version: 0.1.0
 * Author: kubasanitrak
 * Author URI: https://github.com/kubasanitrak
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lkba-vouchers
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: lkba-payments
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LKBA_VOUCHERS_VERSION', '0.1.0');
define('LKBA_VOUCHERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LKBA_VOUCHERS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LKBA_VOUCHERS_PLUGIN_BASENAME', plugin_basename(__FILE__));

$lkba_vouchers_autoload = LKBA_VOUCHERS_PLUGIN_DIR . 'vendor/autoload.php';
if (is_readable($lkba_vouchers_autoload)) {
    require_once $lkba_vouchers_autoload;
}

require_once LKBA_VOUCHERS_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$lkba_vouchers_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/kubasanitrak/lkba-vouchers/',
    __FILE__,
    'lkba-vouchers'
);
$lkba_vouchers_update_checker->getVcsApi()->enableReleaseAssets('/lkba-vouchers\.zip($|[?&#])/i');

function lkba_vouchers_activate() {
    require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-dependency.php';
    if (!LKBA_Vouchers_Dependency::payments_available()) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(plugin_basename(__FILE__), true);
        wp_die(
            esc_html__('LKBA Vouchers requires the LKBA Payments plugin to be installed and active.', 'lkba-vouchers'),
            esc_html__('Plugin dependency missing', 'lkba-vouchers'),
            array('back_link' => true)
        );
    }

    require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-activator.php';
    LKBA_Vouchers_Activator::activate();
}
register_activation_hook(__FILE__, 'lkba_vouchers_activate');

function lkba_vouchers_deactivate() {
    require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-deactivator.php';
    LKBA_Vouchers_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'lkba_vouchers_deactivate');

require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-dependency.php';
require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-loader.php';

function lkba_vouchers_init() {
    if (!LKBA_Vouchers_Dependency::payments_available()) {
        add_action('admin_notices', array('LKBA_Vouchers_Dependency', 'admin_notice'));
        return;
    }

    $loader = new LKBA_Vouchers_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'lkba_vouchers_init', 20);

function lkba_vouchers_load_textdomain() {
    load_plugin_textdomain(
        'lkba-vouchers',
        false,
        dirname(LKBA_VOUCHERS_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'lkba_vouchers_load_textdomain');
