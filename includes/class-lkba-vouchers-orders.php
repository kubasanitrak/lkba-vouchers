<?php
/**
 * Voucher orders CRUD.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Orders {

    /**
     * @return string
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'lkba_voucher_orders';
    }

    /**
     * @param array $data
     * @return int|WP_Error
     */
    public static function create(array $data) {
        global $wpdb;

        if (!LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return new WP_Error('no_table', __('Orders table missing.', 'lkba-vouchers'));
        }

        $invoice = isset($data['invoice_data']) && is_array($data['invoice_data']) ? $data['invoice_data'] : array();
        $row     = array(
            'order_number'   => 'V-temp',
            'status'         => 'payment_pending',
            'payment_status' => 'awaiting',
            'payment_method' => !empty($data['payment_method']) ? sanitize_key($data['payment_method']) : 'bank_transfer',
            'class_value'    => (int) ($data['class_value'] ?? 0),
            'entries'        => (int) ($data['entries'] ?? 1),
            'sum_total'      => (float) ($data['sum_total'] ?? 0),
            'currency'       => 'CZK',
            'company_id'     => !empty($data['company_id']) ? (string) $data['company_id'] : LKBA_Vouchers_Settings::get_default_company(),
            'buyer_name'     => (string) ($data['buyer_name'] ?? ''),
            'buyer_email'    => (string) ($data['buyer_email'] ?? ''),
            'buyer_phone'    => (string) ($data['buyer_phone'] ?? ''),
            'gift_name'      => (string) ($data['gift_name'] ?? ''),
            'invoice_data'   => wp_json_encode($invoice),
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        );
        $formats = array('%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

        if (!empty($data['user_id'])) {
            $row['user_id'] = (int) $data['user_id'];
            $formats[]      = '%d';
        }

        $inserted = $wpdb->insert(self::table(), $row, $formats);
        if (!$inserted) {
            return new WP_Error('insert_failed', __('Could not create order.', 'lkba-vouchers'));
        }

        $id     = (int) $wpdb->insert_id;
        $number = 'V-' . $id;
        $wpdb->update(
            self::table(),
            array('order_number' => $number),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        return $id;
    }

    /**
     * @param int $id
     * @return object|null
     */
    public static function get($id) {
        global $wpdb;
        if (!LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', $id));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $order_number
     * @return object|null
     */
    public static function get_by_number($order_number) {
        global $wpdb;
        if (!LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE order_number = %s LIMIT 1',
            $order_number
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $code
     * @return object|null
     */
    public static function get_by_code($code) {
        global $wpdb;
        $code = sanitize_text_field($code);
        if ($code === '' || !LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE voucher_code = %s LIMIT 1',
            $code
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $invoice_id
     * @return object|null
     */
    public static function get_by_fakturoid_invoice_id($invoice_id) {
        global $wpdb;
        if ($invoice_id === '' || !LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE fakturoid_invoice_id = %s LIMIT 1',
            (string) $invoice_id
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $vs
     * @return object|null
     */
    public static function get_by_variable_symbol($vs) {
        global $wpdb;
        $vs = preg_replace('/\D/', '', (string) $vs);
        if ($vs === '' || !LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE fakturoid_variable_symbol = %s LIMIT 1',
            $vs
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param object $row
     * @return object
     */
    public static function hydrate($row) {
        $row->invoice_data = json_decode((string) $row->invoice_data, true);
        if (!is_array($row->invoice_data)) {
            $row->invoice_data = array();
        }
        return $row;
    }

    /**
     * @param int   $id
     * @param array $fields
     * @return bool
     */
    public static function update($id, array $fields) {
        global $wpdb;
        if (!$fields) {
            return false;
        }
        if (isset($fields['invoice_data']) && is_array($fields['invoice_data'])) {
            $fields['invoice_data'] = wp_json_encode($fields['invoice_data']);
        }
        $fields['updated_at'] = current_time('mysql');
        $formats = array_fill(0, count($fields), '%s');
        return false !== $wpdb->update(self::table(), $fields, array('id' => (int) $id), $formats, array('%d'));
    }

    /**
     * @param array $args
     * @return object[]
     */
    public static function query(array $args = array()) {
        global $wpdb;
        if (!LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            return array();
        }

        $table  = self::table();
        $where  = array('1=1');
        $params = array();

        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }
        if (!empty($args['payment_status'])) {
            $where[]  = 'payment_status = %s';
            $params[] = $args['payment_status'];
        }
        if (!empty($args['search'])) {
            $where[]  = '(order_number LIKE %s OR voucher_code LIKE %s OR buyer_email LIKE %s OR buyer_name LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $limit    = isset($args['limit']) ? max(1, (int) $args['limit']) : 100;
        $params[] = $limit;
        $sql      = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT %d';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        if (!is_array($rows)) {
            return array();
        }
        return array_map(array(__CLASS__, 'hydrate'), $rows);
    }

    /**
     * Ensure unique voucher_code.
     *
     * @param int $order_id
     * @return string
     */
    public static function ensure_voucher_code($order_id) {
        $order = self::get($order_id);
        if (!$order) {
            return '';
        }
        if (!empty($order->voucher_code)) {
            return (string) $order->voucher_code;
        }
        $code = strtoupper(str_replace('-', '', $order->order_number) . wp_generate_password(6, false, false));
        self::update($order_id, array('voucher_code' => $code));
        return $code;
    }

    /**
     * @param object $order
     * @return array
     */
    public static function to_payment_context($order) {
        $label = sprintf(
            /* translators: 1: entries 2: unit price */
            __('Dárkový poukaz — %1$d× %2$d Kč', 'lkba-vouchers'),
            (int) $order->entries,
            (int) $order->class_value
        );
        if (!empty($order->gift_name)) {
            $label .= ' (' . $order->gift_name . ')';
        }

        return array(
            'ref_id'                    => $order->order_number,
            'company_id'                => (string) $order->company_id,
            'amount'                    => (float) $order->sum_total,
            'currency'                  => $order->currency ?: 'CZK',
            'customer_email'            => (string) $order->buyer_email,
            'customer_name'             => (string) $order->buyer_name,
            'label'                     => $label,
            'line_items'                => array(
                array(
                    'name'       => $label,
                    'quantity'   => 1,
                    'unit_price' => (float) $order->sum_total,
                ),
            ),
            'invoice_data'              => $order->invoice_data,
            'fakturoid_invoice_id'      => (string) $order->fakturoid_invoice_id,
            'fakturoid_invoice_number'  => (string) $order->fakturoid_invoice_number,
            'fakturoid_variable_symbol' => (string) $order->fakturoid_variable_symbol,
            'fakturoid_pdf'             => (string) $order->fakturoid_pdf,
            'payment_method'            => (string) $order->payment_method,
            'transaction_id'            => (string) $order->transaction_id,
            'meta'                      => array(
                'order_id'    => (int) $order->id,
                'class_value' => (int) $order->class_value,
                'entries'     => (int) $order->entries,
            ),
        );
    }

    /**
     * @param int $order_id
     * @return string
     */
    public static function invoice_download_url($order_id) {
        return add_query_arg(array(
            'lkba_voucher_invoice' => (int) $order_id,
            '_wpnonce'             => wp_create_nonce('lkba_voucher_invoice_' . (int) $order_id),
        ), home_url('/'));
    }

    /**
     * @param int $order_id
     * @return string
     */
    public static function voucher_download_url($order_id) {
        return add_query_arg(array(
            'lkba_voucher_pdf' => (int) $order_id,
            '_wpnonce'         => wp_create_nonce('lkba_voucher_pdf_' . (int) $order_id),
        ), home_url('/'));
    }
}
