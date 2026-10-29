<?php
/**
 * Import helpers for legacy lkba_goods table.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Migrate {

    /**
     * @return bool
     */
    public static function legacy_table_exists() {
        global $wpdb;
        $table = 'lkba_goods';
        // Unprefixed legacy table (standalone PHP app).
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists === $table) {
            return true;
        }
        $prefixed = $wpdb->prefix . 'lkba_goods';
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $prefixed)) === $prefixed;
    }

    /**
     * @return string
     */
    public static function legacy_table_name() {
        global $wpdb;
        $table = 'lkba_goods';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
            return $table;
        }
        return $wpdb->prefix . 'lkba_goods';
    }

    /**
     * Import rows not already present (matched by legacy unique_id → voucher_code, or email+date+entries).
     *
     * @param int $limit
     * @return array{imported:int,skipped:int,errors:int,message:string}
     */
    public static function import_legacy($limit = 500) {
        global $wpdb;

        if (!self::legacy_table_exists()) {
            return array(
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => 0,
                'message'  => __('Legacy table lkba_goods not found on this database.', 'lkba-vouchers'),
            );
        }

        if (!LKBA_Vouchers_DB::table_exists('lkba_voucher_orders')) {
            LKBA_Vouchers_DB::create_tables();
        }

        $table = self::legacy_table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` ORDER BY id ASC LIMIT %d", max(1, (int) $limit)));
        if (!is_array($rows)) {
            return array('imported' => 0, 'skipped' => 0, 'errors' => 1, 'message' => __('Read failed.', 'lkba-vouchers'));
        }

        $imported = $skipped = $errors = 0;
        foreach ($rows as $row) {
            $code = '';
            if (!empty($row->unique_id)) {
                $code = (string) $row->unique_id;
            }
            if ($code !== '' && LKBA_Vouchers_Orders::get_by_code($code)) {
                $skipped++;
                continue;
            }

            $state = strtolower((string) ($row->state ?? 'pending'));
            $status_map = array(
                'pending'   => array('payment_pending', 'awaiting'),
                'paid'      => array('valid', 'paid'),
                'used'      => array('used', 'paid'),
                'canceled'  => array('cancelled', 'failed'),
                'cancelled' => array('cancelled', 'failed'),
            );
            $pair = $status_map[$state] ?? array('payment_pending', 'awaiting');

            $invoice = array_filter(array(
                'company_name' => (string) ($row->invoice_name ?? ''),
                'street'       => (string) ($row->invoice_address ?? ''),
                'ic'           => (string) ($row->invoice_ico ?? ''),
                'dic'          => (string) ($row->invoice_dic ?? ''),
            ));

            $id = LKBA_Vouchers_Orders::create(array(
                'class_value'    => (int) ($row->class_value ?? 0),
                'entries'        => (int) ($row->entries ?? 1),
                'sum_total'      => (float) ($row->sum_total ?? ((int) ($row->class_value ?? 0) * (int) ($row->entries ?? 1))),
                'payment_method' => 'bank_transfer',
                'buyer_name'     => (string) ($row->name ?? ''),
                'buyer_email'    => (string) ($row->email ?? ''),
                'buyer_phone'    => (string) ($row->phone ?? ''),
                'gift_name'      => (string) ($row->gift_name ?? ''),
                'invoice_data'   => $invoice,
                'company_id'     => LKBA_Vouchers_Settings::get_default_company(),
            ));

            if (is_wp_error($id)) {
                $errors++;
                continue;
            }

            $fields = array(
                'status'         => $pair[0],
                'payment_status' => $pair[1],
            );
            if ($code !== '') {
                $fields['voucher_code'] = $code;
            }
            if (!empty($row->date_validated) && $row->date_validated !== '0000-00-00') {
                $fields['date_validated'] = $row->date_validated . (strlen((string) $row->date_validated) === 10 ? ' 00:00:00' : '');
            }
            if ($pair[1] === 'paid') {
                $fields['paid_at'] = current_time('mysql');
            }
            LKBA_Vouchers_Orders::update((int) $id, $fields);
            $imported++;
        }

        return array(
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'message'  => sprintf(
                /* translators: 1: imported 2: skipped 3: errors */
                __('Import hotov: %1$d nových, %2$d přeskočeno, %3$d chyb.', 'lkba-vouchers'),
                $imported,
                $skipped,
                $errors
            ),
        );
    }
}
