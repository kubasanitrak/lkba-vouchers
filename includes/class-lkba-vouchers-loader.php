<?php
/**
 * Plugin loader.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Loader {

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-activator.php';
        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-db.php';
        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-settings.php';
        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'includes/class-lkba-vouchers-shortcodes.php';

        if (is_admin()) {
            require_once LKBA_VOUCHERS_PLUGIN_DIR . 'admin/class-lkba-vouchers-admin.php';
        }

        require_once LKBA_VOUCHERS_PLUGIN_DIR . 'public/class-lkba-vouchers-public.php';
    }

    public function run() {
        LKBA_Vouchers_Settings::ensure_defaults();
        LKBA_Vouchers_DB::maybe_upgrade();

        new LKBA_Vouchers_Settings();
        new LKBA_Vouchers_Shortcodes();
        new LKBA_Vouchers_Public();

        if (is_admin()) {
            new LKBA_Vouchers_Admin();
        }
    }
}
