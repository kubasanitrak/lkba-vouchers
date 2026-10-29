<?php
/**
 * Front voucher submit — bank or Comgate.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Checkout {

    public function __construct() {
        add_action('init', array($this, 'maybe_handle_submit'), 20);
        add_action('init', array($this, 'maybe_download'), 5);
    }

    public function maybe_handle_submit() {
        if (empty($_POST['lkba_voucher_nonce'])) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_voucher_nonce'])), 'lkba_voucher_order')) {
            return;
        }

        $name     = isset($_POST['lkba_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_name'])) : '';
        $email    = isset($_POST['lkba_email']) ? sanitize_email(wp_unslash($_POST['lkba_email'])) : '';
        $phone    = isset($_POST['lkba_phone']) ? sanitize_text_field(wp_unslash($_POST['lkba_phone'])) : '';
        $gift     = isset($_POST['lkba_gift_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_gift_name'])) : '';
        $value    = isset($_POST['lkba_value']) ? absint($_POST['lkba_value']) : 0;
        $entries  = isset($_POST['lkba_entries']) ? absint($_POST['lkba_entries']) : 0;
        $all_less = !empty($_POST['lkba_all_lessons']);
        $legal    = !empty($_POST['lkba_legal']);
        $pay_raw  = isset($_POST['lkba_payment_method']) ? sanitize_key(wp_unslash($_POST['lkba_payment_method'])) : 'bank_transfer';

        $invoice = array(
            'company_name' => isset($_POST['lkba_inv_company']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_company'])) : '',
            'street'       => isset($_POST['lkba_inv_street']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_street'])) : '',
            'city'         => isset($_POST['lkba_inv_city']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_city'])) : '',
            'zip'          => isset($_POST['lkba_inv_zip']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_zip'])) : '',
            'ic'           => isset($_POST['lkba_inv_ic']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_ic'])) : '',
            'dic'          => isset($_POST['lkba_inv_dic']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_dic'])) : '',
        );

        LKBA_Vouchers_Session::save_draft(array(
            'name'           => $name,
            'email'          => $email,
            'phone'          => $phone,
            'gift_name'      => $gift,
            'value'          => $value,
            'entries'        => $entries,
            'all_lessons'    => $all_less,
            'invoice'        => $invoice,
            'payment_method' => $pay_raw,
        ));

        $redirect_base = wp_get_referer() ? wp_get_referer() : home_url('/');

        if ($name === '' || $email === '' || !is_email($email)) {
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => 'invalid_contact'));
        }
        if (!$legal) {
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => 'legal'));
        }
        if (!class_exists('LKBA_Payments_Gateway')) {
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => 'payments'));
        }

        $priced = LKBA_Vouchers_Settings::price_for($value, $entries, $all_less);
        if (empty($priced['ok'])) {
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => $priced['error'] ?? 'invalid_value'));
        }

        $allowed = LKBA_Vouchers_Settings::get_allowed_payment_methods();
        if (!$allowed) {
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => 'payments'));
        }
        $method = in_array($pay_raw, array('comgate', 'bank_transfer'), true) ? $pay_raw : 'bank_transfer';
        if (!in_array($method, $allowed, true)) {
            $method = $allowed[0];
        }

        $company = LKBA_Vouchers_Settings::get_default_company();

        $order_id = LKBA_Vouchers_Orders::create(array(
            'company_id'     => $company,
            'class_value'    => (int) $priced['unit'],
            'entries'        => (int) $priced['entries'],
            'sum_total'      => (float) $priced['total'],
            'payment_method' => $method,
            'user_id'        => is_user_logged_in() ? get_current_user_id() : null,
            'buyer_name'     => $name,
            'buyer_email'    => $email,
            'buyer_phone'    => $phone,
            'gift_name'      => $gift,
            'invoice_data'   => array_filter($invoice),
        ));

        if (is_wp_error($order_id)) {
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => 'create'));
        }

        $order   = LKBA_Vouchers_Orders::get($order_id);
        $context = LKBA_Vouchers_Orders::to_payment_context($order);
        $context['payment_method'] = $method;
        $context['return_url_paid'] = LKBA_Vouchers_Settings::get_payment_success_url(array(
            'ref' => $order->order_number,
        ));
        $context['return_url_cancelled'] = LKBA_Vouchers_Settings::get_payment_failed_url(array(
            'ref' => $order->order_number,
        ));

        if ($method === 'comgate') {
            $card = LKBA_Payments_Gateway::start_comgate($context);
            if (!empty($card['error'])) {
                $this->redirect_with($redirect_base, array(
                    'lkba_voucher' => 'error',
                    'msg'          => 'comgate',
                    'ref'          => $order->order_number,
                ));
            }
            if (!empty($card['trans_id'])) {
                LKBA_Vouchers_Orders::update($order_id, array(
                    'transaction_id' => (string) $card['trans_id'],
                    'payment_method' => 'comgate',
                ));
            }
            $order = LKBA_Vouchers_Orders::get($order_id);
            LKBA_Vouchers_Emails::send_order($order, '');
            LKBA_Vouchers_Session::clear_draft();
            if (!empty($card['redirect'])) {
                wp_redirect($card['redirect']); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
                exit;
            }
            $this->redirect_with($redirect_base, array('lkba_voucher' => 'error', 'msg' => 'comgate'));
        }

        $bank = LKBA_Payments_Gateway::start_bank_transfer($context);
        if (is_wp_error($bank)) {
            $this->redirect_with($redirect_base, array(
                'lkba_voucher' => 'error',
                'msg'          => 'fakturoid',
                'ref'          => $order->order_number,
            ));
        }

        if (!empty($bank['invoice']) && is_array($bank['invoice'])) {
            LKBA_Vouchers_Orders::update($order_id, array(
                'fakturoid_invoice_id'      => (string) ($bank['invoice']['invoice_id'] ?? ''),
                'fakturoid_invoice_number'  => (string) ($bank['invoice']['number'] ?? ''),
                'fakturoid_variable_symbol' => (string) ($bank['invoice']['variable_symbol'] ?? ''),
                'fakturoid_pdf'             => (string) ($bank['invoice']['pdf'] ?? ''),
                'payment_method'            => 'bank_transfer',
            ));
            $order = LKBA_Vouchers_Orders::get($order_id);
        }

        $instructions = isset($bank['instructions']) ? (string) $bank['instructions'] : '';
        LKBA_Vouchers_Emails::send_order($order, $instructions);
        LKBA_Vouchers_Session::clear_draft();

        $token = wp_generate_password(16, false, false);
        set_transient('lkba_voucher_recap_' . $token, array(
            'order_id'     => $order_id,
            'instructions' => $instructions,
            'qr_url'       => $bank['qr_url'] ?? '',
        ), HOUR_IN_SECONDS);

        $this->redirect_with($redirect_base, array(
            'lkba_voucher' => 'ok',
            'recap'        => $token,
        ));
    }

    public function maybe_download() {
        $invoice_id = isset($_GET['lkba_voucher_invoice']) ? absint($_GET['lkba_voucher_invoice']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $voucher_id = isset($_GET['lkba_voucher_pdf']) ? absint($_GET['lkba_voucher_pdf']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ($invoice_id) {
            $this->serve_file($invoice_id, 'invoice');
        }
        if ($voucher_id) {
            $this->serve_file($voucher_id, 'voucher');
        }
    }

    /**
     * @param int    $order_id
     * @param string $type invoice|voucher
     */
    private function serve_file($order_id, $type) {
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action = $type === 'voucher' ? 'lkba_voucher_pdf_' . $order_id : 'lkba_voucher_invoice_' . $order_id;
        if (!$order_id || !wp_verify_nonce($nonce, $action)) {
            wp_die(esc_html__('Neplatný odkaz.', 'lkba-vouchers'), 403);
        }
        $order = LKBA_Vouchers_Orders::get($order_id);
        if (!$order) {
            wp_die(esc_html__('Objednávka nenalezena.', 'lkba-vouchers'), 404);
        }
        $uid = get_current_user_id();
        $ok  = current_user_can('edit_posts') || ($uid && (int) $order->user_id === $uid);
        if (!$ok) {
            wp_die(esc_html__('Nemáte oprávnění. Přihlaste se.', 'lkba-vouchers'), 403);
        }

        if ($type === 'voucher') {
            if (empty($order->voucher_pdf)) {
                wp_die(esc_html__('PDF poukazu není k dispozici.', 'lkba-vouchers'), 404);
            }
            $path = LKBA_Vouchers_PDF::absolute_path($order->voucher_pdf);
            $name = sanitize_file_name($order->order_number . '-voucher.pdf');
        } else {
            if (empty($order->fakturoid_pdf) || !class_exists('LKBA_Payments_Fakturoid')) {
                wp_die(esc_html__('Faktura není k dispozici.', 'lkba-vouchers'), 404);
            }
            $path = LKBA_Payments_Fakturoid::absolute_path($order->fakturoid_pdf);
            $name = sanitize_file_name($order->order_number . '-invoice.pdf');
        }

        if (!is_readable($path)) {
            wp_die(esc_html__('Soubor chybí.', 'lkba-vouchers'), 404);
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (string) filesize($path));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        readfile($path);
        exit;
    }

    /**
     * @param string $url
     * @param array  $args
     */
    private function redirect_with($url, array $args) {
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }
}
