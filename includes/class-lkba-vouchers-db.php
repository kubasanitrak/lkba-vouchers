<?php
/**
 * Voucher orders schema.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_DB {

    public static function maybe_upgrade() {
        $installed = get_option('lkba_vouchers_db_version', '');
        if (version_compare((string) $installed, LKBA_VOUCHERS_VERSION, '>=')) {
            return;
        }
        self::create_tables();
        update_option('lkba_vouchers_db_version', LKBA_VOUCHERS_VERSION);
    }

    public static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $orders  = $wpdb->prefix . 'lkba_voucher_orders';

        dbDelta("CREATE TABLE $orders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number varchar(32) NOT NULL,
            voucher_code varchar(64) DEFAULT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'payment_pending',
            payment_status varchar(20) NOT NULL DEFAULT 'awaiting',
            payment_method varchar(50) NOT NULL DEFAULT '',
            class_value int(11) NOT NULL DEFAULT 0,
            entries int(11) NOT NULL DEFAULT 1,
            sum_total decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'CZK',
            company_id varchar(64) DEFAULT NULL,
            buyer_name varchar(255) DEFAULT NULL,
            buyer_email varchar(255) DEFAULT NULL,
            buyer_phone varchar(64) DEFAULT NULL,
            gift_name varchar(255) DEFAULT NULL,
            invoice_data longtext DEFAULT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            fakturoid_invoice_id varchar(64) DEFAULT NULL,
            fakturoid_invoice_number varchar(32) DEFAULT NULL,
            fakturoid_variable_symbol varchar(32) DEFAULT NULL,
            fakturoid_pdf varchar(255) DEFAULT NULL,
            voucher_pdf varchar(255) DEFAULT NULL,
            date_validated datetime DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_number (order_number),
            KEY voucher_code (voucher_code),
            KEY user_id (user_id),
            KEY status (status),
            KEY payment_status (payment_status),
            KEY company_id (company_id),
            KEY fakturoid_invoice_id (fakturoid_invoice_id),
            KEY fakturoid_variable_symbol (fakturoid_variable_symbol)
        ) $charset;");
    }

    /**
     * @param string $table_suffix
     * @return bool
     */
    public static function table_exists($table_suffix) {
        global $wpdb;
        $table = $wpdb->prefix . $table_suffix;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
}
