<?php
/**
 * Requires lkba-payments.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Dependency {

    /**
     * @return bool
     */
    public static function payments_available() {
        return defined('LKBA_PAYMENTS_VERSION');
    }

    public static function admin_notice() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('LKBA Vouchers requires the LKBA Payments plugin. Please install and activate it.', 'lkba-vouchers');
        echo '</p></div>';
    }
}
