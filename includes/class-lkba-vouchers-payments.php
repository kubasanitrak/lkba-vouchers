<?php
/**
 * Wire V- refs into lkba-payments registry.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Payments {

    public static function boot() {
        add_action('lkba_payments_register', array(__CLASS__, 'register'));
        if (did_action('lkba_payments_register')) {
            self::register();
        }
        add_filter('lkba_payments_resolve_fakturoid', array(__CLASS__, 'resolve_fakturoid'), 10, 3);
        add_action('lkba_payments_fakturoid_proforma_created', array(__CLASS__, 'on_proforma_created'), 10, 2);
        add_action('lkba_payments_fakturoid_document_synced', array(__CLASS__, 'on_document_synced'), 10, 2);
        add_action('lkba_payments_fakturoid_invoice_after_card', array(__CLASS__, 'on_invoice_after_card'), 10, 2);
    }

    public static function register() {
        if (!class_exists('LKBA_Payments_Registry')) {
            return;
        }
        LKBA_Payments_Registry::register_resolver('V', array(__CLASS__, 'resolve_ref'));
        LKBA_Payments_Registry::register_paid_handler('V', array(__CLASS__, 'handle_paid'));
        LKBA_Payments_Registry::register_failed_handler('V', array(__CLASS__, 'handle_failed'));
    }

    /**
     * @param string $ref_id
     * @return array|null
     */
    public static function resolve_ref($ref_id) {
        $order = LKBA_Vouchers_Orders::get_by_number($ref_id);
        return $order ? LKBA_Vouchers_Orders::to_payment_context($order) : null;
    }

    /**
     * @param array|null $context
     * @param array      $invoice
     * @param array      $payment
     * @return array|null
     */
    public static function resolve_fakturoid($context, $invoice, $payment) {
        if (is_array($context)) {
            return $context;
        }
        if (!empty($invoice['id'])) {
            $order = LKBA_Vouchers_Orders::get_by_fakturoid_invoice_id((string) $invoice['id']);
            if ($order) {
                return LKBA_Vouchers_Orders::to_payment_context($order);
            }
        }
        if (!empty($invoice['custom_id'])) {
            $order = LKBA_Vouchers_Orders::get_by_number((string) $invoice['custom_id']);
            if ($order) {
                return LKBA_Vouchers_Orders::to_payment_context($order);
            }
        }
        $vs = '';
        if (!empty($payment['variable_symbol'])) {
            $vs = (string) $payment['variable_symbol'];
        } elseif (!empty($invoice['variable_symbol'])) {
            $vs = (string) $invoice['variable_symbol'];
        }
        if ($vs !== '') {
            $order = LKBA_Vouchers_Orders::get_by_variable_symbol($vs);
            if ($order) {
                return LKBA_Vouchers_Orders::to_payment_context($order);
            }
        }
        return null;
    }

    /**
     * @param array  $context
     * @param string $transaction_id
     * @param string $source
     * @param array  $extra
     * @return bool
     */
    public static function handle_paid($context, $transaction_id, $source, $extra = array()) {
        $order = LKBA_Vouchers_Orders::get_by_number($context['ref_id'] ?? '');
        if (!$order) {
            return false;
        }
        if ($order->payment_status === 'paid' && in_array($order->status, array('valid', 'used'), true)) {
            return true;
        }

        $user_id = self::ensure_user_for_order($order);
        $code    = LKBA_Vouchers_Orders::ensure_voucher_code((int) $order->id);

        $fields = array(
            'status'         => 'valid',
            'payment_status' => 'paid',
            'paid_at'        => current_time('mysql'),
            'transaction_id' => $transaction_id,
            'voucher_code'   => $code,
        );
        if ($user_id) {
            $fields['user_id'] = $user_id;
        }
        if (!empty($extra['invoice']['id'])) {
            $fields['fakturoid_invoice_id'] = (string) $extra['invoice']['id'];
        }
        if (!empty($extra['invoice']['number'])) {
            $fields['fakturoid_invoice_number'] = (string) $extra['invoice']['number'];
        }
        if (!empty($extra['invoice']['variable_symbol'])) {
            $fields['fakturoid_variable_symbol'] = preg_replace('/\D/', '', (string) $extra['invoice']['variable_symbol']);
        }

        LKBA_Vouchers_Orders::update((int) $order->id, $fields);
        $order = LKBA_Vouchers_Orders::get((int) $order->id);

        // Generate gift voucher PDF after paid.
        $pdf_rel = LKBA_Vouchers_PDF::generate_voucher($order);
        if ($pdf_rel) {
            LKBA_Vouchers_Orders::update((int) $order->id, array('voucher_pdf' => $pdf_rel));
            $order = LKBA_Vouchers_Orders::get((int) $order->id);
        }

        LKBA_Vouchers_Emails::send_paid($order);
        return true;
    }

    /**
     * @param array  $context
     * @param string $reason
     * @param string $source
     * @return bool
     */
    public static function handle_failed($context, $reason, $source) {
        $order = LKBA_Vouchers_Orders::get_by_number($context['ref_id'] ?? '');
        if (!$order) {
            return false;
        }
        LKBA_Vouchers_Orders::update((int) $order->id, array(
            'payment_status' => 'failed',
            'status'         => 'cancelled',
        ));
        return true;
    }

    /**
     * @param array $context
     * @param array $result
     */
    public static function on_proforma_created($context, $result) {
        $order = LKBA_Vouchers_Orders::get_by_number($context['ref_id'] ?? '');
        if (!$order || !is_array($result)) {
            return;
        }
        // Only touch V- refs.
        if (strpos((string) ($context['ref_id'] ?? ''), 'V-') !== 0) {
            return;
        }
        LKBA_Vouchers_Orders::update((int) $order->id, array(
            'fakturoid_invoice_id'      => (string) ($result['invoice_id'] ?? ''),
            'fakturoid_invoice_number'  => (string) ($result['number'] ?? ''),
            'fakturoid_variable_symbol' => (string) ($result['variable_symbol'] ?? ''),
            'fakturoid_pdf'             => (string) ($result['pdf'] ?? ''),
        ));
    }

    /**
     * @param array $context
     * @param array $synced
     */
    public static function on_document_synced($context, $synced) {
        if (strpos((string) ($context['ref_id'] ?? ''), 'V-') !== 0) {
            return;
        }
        $order = LKBA_Vouchers_Orders::get_by_number($context['ref_id'] ?? '');
        if (!$order || !is_array($synced)) {
            return;
        }
        $fields = array();
        if (!empty($synced['invoice_id'])) {
            $fields['fakturoid_invoice_id'] = (string) $synced['invoice_id'];
        }
        if (!empty($synced['number'])) {
            $fields['fakturoid_invoice_number'] = (string) $synced['number'];
        }
        if (!empty($synced['variable_symbol'])) {
            $fields['fakturoid_variable_symbol'] = (string) $synced['variable_symbol'];
        }
        if (!empty($synced['pdf'])) {
            $fields['fakturoid_pdf'] = (string) $synced['pdf'];
        }
        if ($fields) {
            LKBA_Vouchers_Orders::update((int) $order->id, $fields);
        }
    }

    /**
     * @param array $context
     * @param array $invoice
     */
    public static function on_invoice_after_card($context, $invoice) {
        if (strpos((string) ($context['ref_id'] ?? ''), 'V-') !== 0) {
            return;
        }
        $order = LKBA_Vouchers_Orders::get_by_number($context['ref_id'] ?? '');
        if (!$order || !is_array($invoice)) {
            return;
        }
        $fields = array(
            'fakturoid_invoice_id'      => (string) ($invoice['invoice_id'] ?? $invoice['id'] ?? ''),
            'fakturoid_invoice_number'  => (string) ($invoice['number'] ?? ''),
            'fakturoid_variable_symbol' => preg_replace('/\D/', '', (string) ($invoice['variable_symbol'] ?? '')),
            'fakturoid_pdf'             => (string) ($invoice['pdf'] ?? ''),
        );
        LKBA_Vouchers_Orders::update((int) $order->id, array_filter($fields));
    }

    /**
     * @param object $order
     * @return int|null
     */
    public static function ensure_user_for_order($order) {
        if (!empty($order->user_id)) {
            return (int) $order->user_id;
        }
        $email = (string) $order->buyer_email;
        $name  = (string) $order->buyer_name;
        if ($email === '' || !is_email($email)) {
            return null;
        }
        $existing = get_user_by('email', $email);
        if ($existing) {
            return (int) $existing->ID;
        }
        $login = sanitize_user(current(explode('@', $email)), true);
        if ($login === '' || username_exists($login)) {
            $login = 'lkba_' . wp_generate_password(8, false, false);
        }
        $user_id = wp_insert_user(array(
            'user_login'   => $login,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password(24),
            'display_name' => $name !== '' ? $name : $login,
            'role'         => 'subscriber',
        ));
        return is_wp_error($user_id) ? null : (int) $user_id;
    }
}
