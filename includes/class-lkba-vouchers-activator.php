<?php
/**
 * Plugin activation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Activator {

    public static function activate() {
        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-settings.php';
        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-db.php';

        LKBA_Vouchers_Settings::ensure_defaults();
        LKBA_Vouchers_DB::create_tables();

        update_option('lkba_vouchers_db_version', LKBA_VOUCHERS_VERSION);
        update_option('lkba_vouchers_plugin_activated', true);
    }
}
