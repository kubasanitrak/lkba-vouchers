<?php
/**
 * Admin menu: orders, validate, settings, migrate.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Admin {

    const MENU_SLUG = 'lkba-vouchers';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_save'));
        add_action('admin_init', array($this, 'handle_order_actions'));
        add_action('admin_init', array($this, 'handle_migrate'));
    }

    public function register_menu() {
        add_menu_page(
            __('LKBA Poukazy', 'lkba-vouchers'),
            __('LKBA Poukazy', 'lkba-vouchers'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_orders'),
            'dashicons-tickets-alt',
            27
        );

        add_submenu_page(self::MENU_SLUG, __('Objednávky', 'lkba-vouchers'), __('Objednávky', 'lkba-vouchers'), 'edit_posts', self::MENU_SLUG, array($this, 'render_orders'));
        add_submenu_page(self::MENU_SLUG, __('Validace', 'lkba-vouchers'), __('Validace', 'lkba-vouchers'), 'edit_posts', 'lkba-vouchers-validate', array($this, 'render_validate'));
        add_submenu_page(self::MENU_SLUG, __('Nastavení', 'lkba-vouchers'), __('Nastavení', 'lkba-vouchers'), 'manage_options', 'lkba-vouchers-settings', array($this, 'render_settings'));
        add_submenu_page(self::MENU_SLUG, __('Migrace', 'lkba-vouchers'), __('Migrace', 'lkba-vouchers'), 'manage_options', 'lkba-vouchers-migrate', array($this, 'render_migrate'));
    }

    public function render_orders() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $args   = array('limit' => 200);
        if ($status !== '') {
            $args['status'] = $status;
        }
        if ($search !== '') {
            $args['search'] = $search;
        }
        $orders = LKBA_Vouchers_Orders::query($args);
        include LKBA_VOUCHERS_PLUGIN_DIR . 'admin/partials/orders-page.php';
    }

    public function render_validate() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        $code  = isset($_GET['voucher']) ? sanitize_text_field(wp_unslash($_GET['voucher'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order = $code !== '' ? LKBA_Vouchers_Orders::get_by_code($code) : null;
        $notice = '';

        if (isset($_POST['lkba_validate_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_validate_nonce'])), 'lkba_voucher_validate')) {
            $id     = isset($_POST['lkba_order_id']) ? absint($_POST['lkba_order_id']) : 0;
            $status = isset($_POST['lkba_status']) ? sanitize_key(wp_unslash($_POST['lkba_status'])) : '';
            if ($id && in_array($status, array('valid', 'used', 'payment_pending', 'cancelled'), true)) {
                $fields = array('status' => $status);
                if ($status === 'used') {
                    $fields['date_validated'] = current_time('mysql');
                }
                LKBA_Vouchers_Orders::update($id, $fields);
                $order  = LKBA_Vouchers_Orders::get($id);
                $notice = __('Stav uložen.', 'lkba-vouchers');
            }
        }

        echo '<div class="wrap"><h1>' . esc_html__('Validace poukazů', 'lkba-vouchers') . '</h1>';
        include LKBA_VOUCHERS_PLUGIN_DIR . 'public/partials/voucher-validate.php';
        echo '</div>';
    }

    public function handle_order_actions() {
        if (!isset($_GET['lkba_voucher_action'], $_GET['order_id'], $_GET['_wpnonce'])) {
            return;
        }
        if (!current_user_can('edit_posts')) {
            return;
        }
        $id = absint($_GET['order_id']);
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'lkba_voucher_order_' . $id)) {
            return;
        }
        $action = sanitize_key(wp_unslash($_GET['lkba_voucher_action']));
        if ($action === 'confirm') {
            $order = LKBA_Vouchers_Orders::get($id);
            if ($order) {
                LKBA_Vouchers_Payments::handle_paid(
                    LKBA_Vouchers_Orders::to_payment_context($order),
                    'admin-manual',
                    'admin'
                );
            }
        } elseif ($action === 'cancel') {
            LKBA_Vouchers_Orders::update($id, array(
                'status'         => 'cancelled',
                'payment_status' => 'failed',
            ));
        }
        wp_safe_redirect(admin_url('admin.php?page=lkba-vouchers'));
        exit;
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
            isset($_POST['lkba_support_email']) ? sanitize_email(wp_unslash($_POST['lkba_support_email'])) : LKBA_Vouchers_Settings::DEFAULT_SUPPORT_EMAIL
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_SENDER_NAME,
            isset($_POST['lkba_sender_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_sender_name'])) : ''
        );
        update_option(
            LKBA_Vouchers_Settings::OPTION_SENDER_EMAIL,
            isset($_POST['lkba_sender_email']) ? sanitize_email(wp_unslash($_POST['lkba_sender_email'])) : LKBA_Vouchers_Settings::DEFAULT_SUPPORT_EMAIL
        );
        update_option(LKBA_Vouchers_Settings::OPTION_TERMS_PAGE, isset($_POST['lkba_terms_page']) ? absint($_POST['lkba_terms_page']) : 0);
        update_option(LKBA_Vouchers_Settings::OPTION_PRIVACY_PAGE, isset($_POST['lkba_privacy_page']) ? absint($_POST['lkba_privacy_page']) : 0);
        update_option(LKBA_Vouchers_Settings::OPTION_STORNO_PAGE, isset($_POST['lkba_storno_page']) ? absint($_POST['lkba_storno_page']) : 0);
        update_option(LKBA_Vouchers_Settings::OPTION_SUCCESS_PAGE, isset($_POST['lkba_success_page']) ? absint($_POST['lkba_success_page']) : 0);
        update_option(LKBA_Vouchers_Settings::OPTION_FAILED_PAGE, isset($_POST['lkba_failed_page']) ? absint($_POST['lkba_failed_page']) : 0);
        update_option(LKBA_Vouchers_Settings::OPTION_VALIDATE_PAGE, isset($_POST['lkba_validate_page']) ? absint($_POST['lkba_validate_page']) : 0);
        update_option(LKBA_Vouchers_Settings::OPTION_BUNDLE_DISCOUNT, isset($_POST['lkba_bundle_discount']) ? absint($_POST['lkba_bundle_discount']) : 430);
        update_option(LKBA_Vouchers_Settings::OPTION_BUNDLE_MIN, isset($_POST['lkba_bundle_min']) ? absint($_POST['lkba_bundle_min']) : 10);
        update_option(LKBA_Vouchers_Settings::OPTION_BUNDLE_BASE, isset($_POST['lkba_bundle_base']) ? absint($_POST['lkba_bundle_base']) : 450);

        if (isset($_POST['lkba_price_tiers_json'])) {
            $raw     = wp_unslash($_POST['lkba_price_tiers_json']);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                update_option(LKBA_Vouchers_Settings::OPTION_PRICE_TIERS, $decoded);
            }
        }

        add_settings_error('lkba_vouchers', 'saved', __('Nastavení uloženo.', 'lkba-vouchers'), 'updated');
    }

    public function handle_migrate() {
        if (!isset($_POST['lkba_vouchers_migrate_nonce'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_vouchers_migrate_nonce'])), 'lkba_vouchers_migrate')) {
            return;
        }
        $result = LKBA_Vouchers_Migrate::import_legacy(1000);
        set_transient('lkba_vouchers_migrate_result', $result, 120);
        wp_safe_redirect(admin_url('admin.php?page=lkba-vouchers-migrate'));
        exit;
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        include LKBA_VOUCHERS_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    public function render_migrate() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $result = get_transient('lkba_vouchers_migrate_result');
        if ($result) {
            delete_transient('lkba_vouchers_migrate_result');
        }
        $exists = LKBA_Vouchers_Migrate::legacy_table_exists();
        include LKBA_VOUCHERS_PLUGIN_DIR . 'admin/partials/migrate-page.php';
    }
}
