<?php
/**
 * Front shortcodes — form, validate, payment returns.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Shortcodes {

    public function __construct() {
        add_shortcode('lkba_voucher_form', array($this, 'render_form'));
        add_shortcode('lkba_voucher_validate', array($this, 'render_validate'));
        add_shortcode('lkba_voucher_payment_success', array($this, 'render_payment_success'));
        add_shortcode('lkba_voucher_payment_failed', array($this, 'render_payment_failed'));
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_form($atts = array()) {
        $flash = isset($_GET['lkba_voucher']) ? sanitize_key(wp_unslash($_GET['lkba_voucher'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $recap_token = isset($_GET['recap']) ? sanitize_text_field(wp_unslash($_GET['recap'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ($flash === 'ok' && $recap_token !== '') {
            $recap = get_transient('lkba_voucher_recap_' . $recap_token);
            if (is_array($recap) && !empty($recap['order_id'])) {
                $order = LKBA_Vouchers_Orders::get((int) $recap['order_id']);
                ob_start();
                include LKBA_VOUCHERS_PLUGIN_DIR . 'public/partials/order-recap.php';
                return (string) ob_get_clean();
            }
        }

        $draft   = LKBA_Vouchers_Session::get_draft();
        $tiers   = LKBA_Vouchers_Settings::get_price_tiers();
        $methods = LKBA_Vouchers_Settings::get_allowed_payment_methods();
        $support = LKBA_Vouchers_Settings::get_support_email();
        $privacy = (int) get_option(LKBA_Vouchers_Settings::OPTION_PRIVACY_PAGE, 0);
        $terms_p = (int) get_option(LKBA_Vouchers_Settings::OPTION_TERMS_PAGE, 0);
        $storno  = (int) get_option(LKBA_Vouchers_Settings::OPTION_STORNO_PAGE, 0);
        $bundle  = array(
            'base'     => (int) get_option(LKBA_Vouchers_Settings::OPTION_BUNDLE_BASE, 450),
            'min'      => (int) get_option(LKBA_Vouchers_Settings::OPTION_BUNDLE_MIN, 10),
            'discount' => (int) get_option(LKBA_Vouchers_Settings::OPTION_BUNDLE_DISCOUNT, 430),
        );

        $error_msg = '';
        if ($flash === 'error') {
            $code = isset($_GET['msg']) ? sanitize_key(wp_unslash($_GET['msg'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $error_msg = self::error_message($code);
        }

        ob_start();
        include LKBA_VOUCHERS_PLUGIN_DIR . 'public/partials/voucher-form.php';
        return (string) ob_get_clean();
    }

    /**
     * Staff validation UI (decision §11).
     *
     * @param array|string $atts
     * @return string
     */
    public function render_validate($atts = array()) {
        if (!current_user_can('edit_posts')) {
            return '<div class="lkba-vouchers"><p>' . esc_html__('Validace je dostupná pouze pro personál.', 'lkba-vouchers') . '</p></div>';
        }

        $code   = isset($_GET['voucher']) ? sanitize_text_field(wp_unslash($_GET['voucher'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order  = $code !== '' ? LKBA_Vouchers_Orders::get_by_code($code) : null;
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

        ob_start();
        include LKBA_VOUCHERS_PLUGIN_DIR . 'public/partials/voucher-validate.php';
        return (string) ob_get_clean();
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_payment_success($atts = array()) {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($ref === '' && isset($_GET['refId'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $ref = sanitize_text_field(wp_unslash($_GET['refId']));
        }
        $order = $ref !== '' ? LKBA_Vouchers_Orders::get_by_number($ref) : null;
        if ($order && $order->payment_status !== 'paid' && class_exists('LKBA_Payments_Comgate')) {
            $context = LKBA_Vouchers_Orders::to_payment_context($order);
            LKBA_Payments_Comgate::for_company((string) $order->company_id)
                ->complete_from_return($context, (string) $order->transaction_id);
            $order = LKBA_Vouchers_Orders::get((int) $order->id);
        }
        $support = LKBA_Vouchers_Settings::get_support_email();
        $paid    = $order && $order->payment_status === 'paid';
        ob_start();
        include LKBA_VOUCHERS_PLUGIN_DIR . 'public/partials/payment-success.php';
        return (string) ob_get_clean();
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_payment_failed($atts = array()) {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order = $ref !== '' ? LKBA_Vouchers_Orders::get_by_number($ref) : null;
        if ($order && $order->payment_status === 'awaiting' && class_exists('LKBA_Payments_Comgate')) {
            $context = LKBA_Vouchers_Orders::to_payment_context($order);
            LKBA_Payments_Comgate::for_company((string) $order->company_id)
                ->complete_from_return($context, (string) $order->transaction_id);
            $order = LKBA_Vouchers_Orders::get((int) $order->id);
        }
        $support = LKBA_Vouchers_Settings::get_support_email();
        ob_start();
        include LKBA_VOUCHERS_PLUGIN_DIR . 'public/partials/payment-failed.php';
        return (string) ob_get_clean();
    }

    /**
     * @param string $code
     * @return string
     */
    private static function error_message($code) {
        $map = array(
            'invalid_contact' => __('Vyplňte jméno a platný e-mail.', 'lkba-vouchers'),
            'legal'           => __('Musíte souhlasit s podmínkami.', 'lkba-vouchers'),
            'payments'        => __('Platební modul není dostupný.', 'lkba-vouchers'),
            'invalid_value'   => __('Neplatná hodnota lekce.', 'lkba-vouchers'),
            'invalid_entries' => __('Neplatný počet lekcí pro zvolenou hodnotu.', 'lkba-vouchers'),
            'fakturoid'       => __('Objednávka vznikla, ale proforma se nepodařila. Napište na podporu.', 'lkba-vouchers'),
            'comgate'         => __('Objednávka vznikla, ale přesměrování na platební bránu selhalo.', 'lkba-vouchers'),
            'create'          => __('Objednávku se nepodařilo vytvořit.', 'lkba-vouchers'),
        );
        return $map[$code] ?? __('Objednávku se nepodařilo odeslat.', 'lkba-vouchers');
    }
}
