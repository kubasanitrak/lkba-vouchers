<?php
/**
 * Voucher transactional emails.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Emails {

    /**
     * Order placed + payment instructions.
     *
     * @param object $order
     * @param string $instructions_html
     * @return bool
     */
    public static function send_order($order, $instructions_html = '') {
        $email = (string) $order->buyer_email;
        if ($email === '' || !is_email($email)) {
            return false;
        }

        $support = LKBA_Vouchers_Settings::get_support_email();
        $unit    = self::effective_unit($order);

        $inner  = '<p>' . esc_html(sprintf(__('Dobrý den %s,', 'lkba-vouchers'), $order->buyer_name)) . '</p>';
        $inner .= '<p>' . esc_html__('děkujeme za objednávku dárkového poukazu. Níže je rekapitulace a platební údaje.', 'lkba-vouchers') . '</p>';
        $inner .= '<ul>';
        $inner .= '<li><strong>' . esc_html__('Objednávka:', 'lkba-vouchers') . '</strong> ' . esc_html($order->order_number) . '</li>';
        $inner .= '<li><strong>' . esc_html__('Cena za lekci:', 'lkba-vouchers') . '</strong> ' . esc_html(LKBA_Vouchers_PDF::pretty_price($unit)) . '</li>';
        $inner .= '<li><strong>' . esc_html__('Počet lekcí:', 'lkba-vouchers') . '</strong> ' . esc_html((string) $order->entries) . '</li>';
        $inner .= '<li><strong>' . esc_html__('Celkem:', 'lkba-vouchers') . '</strong> ' . esc_html(LKBA_Vouchers_PDF::pretty_price($order->sum_total)) . '</li>';
        if (!empty($order->gift_name)) {
            $inner .= '<li><strong>' . esc_html__('Pro:', 'lkba-vouchers') . '</strong> ' . esc_html($order->gift_name) . '</li>';
        }
        $inner .= '</ul>';

        if ($instructions_html !== '') {
            $inner .= $instructions_html;
        } elseif (($order->payment_method ?? '') === 'comgate') {
            $inner .= '<p>' . esc_html__('Platbu kartou dokončete v platební bráně Comgate.', 'lkba-vouchers') . '</p>';
        }

        $inner .= '<p>' . esc_html__('Podpora:', 'lkba-vouchers')
            . ' <a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a>.</p>';

        $subject = sprintf(
            __('[%1$s] Objednávka poukazu %2$s — platební údaje', 'lkba-vouchers'),
            'LKBA',
            $order->order_number
        );

        return self::send($email, $subject, __('Objednávka poukazu', 'lkba-vouchers'), $inner);
    }

    /**
     * Paid → voucher PDF + invoice PDF.
     *
     * @param object $order
     * @return bool
     */
    public static function send_paid($order) {
        $email = (string) $order->buyer_email;
        if ($email === '' || !is_email($email)) {
            return false;
        }

        $support = LKBA_Vouchers_Settings::get_support_email();
        $inner   = '<p>' . esc_html(sprintf(__('Dobrý den %s,', 'lkba-vouchers'), $order->buyer_name)) . '</p>';
        $inner  .= '<p>' . esc_html__('platba byla přijata. V příloze najdete dárkový poukaz a fakturu.', 'lkba-vouchers') . '</p>';
        $inner  .= '<ul>';
        $inner  .= '<li><strong>' . esc_html__('Objednávka:', 'lkba-vouchers') . '</strong> ' . esc_html($order->order_number) . '</li>';
        if (!empty($order->voucher_code)) {
            $inner .= '<li><strong>' . esc_html__('Kód poukazu:', 'lkba-vouchers') . '</strong> ' . esc_html($order->voucher_code) . '</li>';
        }
        $inner .= '<li><strong>' . esc_html__('Lekce:', 'lkba-vouchers') . '</strong> '
            . esc_html((string) $order->entries) . ' × ' . esc_html(LKBA_Vouchers_PDF::pretty_price(self::effective_unit($order)))
            . '</li>';
        $inner .= '</ul>';
        $inner .= '<p>' . esc_html__('Podpora:', 'lkba-vouchers')
            . ' <a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a>.</p>';

        $attachments = array();
        if (!empty($order->voucher_pdf)) {
            $path = LKBA_Vouchers_PDF::absolute_path($order->voucher_pdf);
            if (is_readable($path)) {
                $attachments[] = $path;
            }
        }
        if (!empty($order->fakturoid_pdf) && class_exists('LKBA_Payments_Fakturoid')) {
            $path = LKBA_Payments_Fakturoid::absolute_path($order->fakturoid_pdf);
            if (is_readable($path)) {
                $attachments[] = $path;
            }
        }

        $subject = sprintf(
            __('[%1$s] Poukaz %2$s — potvrzení platby', 'lkba-vouchers'),
            'LKBA',
            $order->order_number
        );

        return self::send($email, $subject, __('Platba potvrzena', 'lkba-vouchers'), $inner, $attachments);
    }

    /**
     * @param object $order
     * @return int
     */
    private static function effective_unit($order) {
        $entries = (int) $order->entries;
        if ($entries > 0) {
            return (int) round((float) $order->sum_total / $entries);
        }
        return (int) $order->class_value;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $title
     * @param string $inner_html
     * @param array  $attachments
     * @return bool
     */
    private static function send($to, $subject, $title, $inner_html, array $attachments = array()) {
        if (class_exists('LKBA_Payments_Emails')) {
            return LKBA_Payments_Emails::send($to, $subject, $title, $inner_html, $attachments);
        }
        $from_name  = get_option(LKBA_Vouchers_Settings::OPTION_SENDER_NAME, 'Lenka Krejčová Barre Academy');
        $from_email = LKBA_Vouchers_Settings::get_sender_email();
        $headers    = array('Content-Type: text/html; charset=UTF-8');
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        return wp_mail($to, $subject, $inner_html, $headers, $attachments);
    }
}
