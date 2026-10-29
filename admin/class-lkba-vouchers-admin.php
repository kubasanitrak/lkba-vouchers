<?php
/**
 * Admin menu and settings stub.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Admin {

    const MENU_SLUG = 'lkba-vouchers';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_save'));
    }

    public function register_menu() {
        add_menu_page(
            __('LKBA Poukazy', 'lkba-vouchers'),
            __('LKBA Poukazy', 'lkba-vouchers'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_orders_stub'),
            'dashicons-tickets-alt',
            27
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Objednávky', 'lkba-vouchers'),
            __('Objednávky', 'lkba-vouchers'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_orders_stub')
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Validace', 'lkba-vouchers'),
            __('Validace', 'lkba-vouchers'),
            'edit_posts',
            'lkba-vouchers-validate',
            array($this, 'render_validate_stub')
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Nastavení', 'lkba-vouchers'),
            __('Nastavení', 'lkba-vouchers'),
            'manage_options',
            'lkba-vouchers-settings',
            array($this, 'render_settings')
        );
    }

    public function render_orders_stub() {
        echo '<div class="wrap"><h1>' . esc_html__('Objednávky poukazů', 'lkba-vouchers') . '</h1>';
        echo '<p>' . esc_html__('Phase 0 stub. Table: lkba_voucher_orders.', 'lkba-vouchers') . '</p></div>';
    }

    public function render_validate_stub() {
        echo '<div class="wrap"><h1>' . esc_html__('Validace poukazů', 'lkba-vouchers') . '</h1>';
        echo '<p>' . esc_html__('Staff-only validation UI arrives in Phase 4.', 'lkba-vouchers') . '</p></div>';
    }

    public function handle_save() {
        if (!isset($_POST['lkba_vouchers_settings_nonce'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_vouchers_settings_nonce'])), 'lkba_vouchers_save_settings')) {
            return;
        }

        update_option(
            LKBA_Vouchers_Settings::OPTION_DEFAULT_COMPANY,
            isset($_POST['lkba_default_company']) ? sanitize_text_field(wp_unslash($_POST['lkba_default_company'])) : 'company-1'
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_SUPPORT_EMAIL,
            isset($_POST['lkba_support_email']) ? sanitize_email(wp_unslash($_POST['lkba_support_email'])) : ''
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_SENDER_NAME,
            isset($_POST['lkba_sender_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_sender_name'])) : ''
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_SENDER_EMAIL,
            isset($_POST['lkba_sender_email']) ? sanitize_email(wp_unslash($_POST['lkba_sender_email'])) : ''
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_TERMS_PAGE,
            isset($_POST['lkba_terms_page']) ? absint($_POST['lkba_terms_page']) : 0
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_PRIVACY_PAGE,
            isset($_POST['lkba_privacy_page']) ? absint($_POST['lkba_privacy_page']) : 0
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_STORNO_PAGE,
            isset($_POST['lkba_storno_page']) ? absint($_POST['lkba_storno_page']) : 0
        );

        // Price tiers kept as option JSON for Phase 4 editor; preserve defaults unless posted.
        if (isset($_POST['lkba_price_tiers_json'])) {
            $raw = wp_unslash($_POST['lkba_price_tiers_json']);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                update_option(LKBA_Vouchers_Settings::OPTION_PRICE_TIERS, $decoded);
            }
        }

        add_settings_error('lkba_vouchers', 'saved', __('Nastavení uloženo.', 'lkba-vouchers'), 'updated');
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        include LKBA_VOUCHERS_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }
}
