<?php
/**
 * Gift voucher PDF via mPDF (ported from darkovy-poukaz).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_PDF {

    /**
     * @param object $order
     * @return string|null Relative path under uploads, or null on failure.
     */
    public static function generate_voucher($order) {
        if (!class_exists('\Mpdf\Mpdf')) {
            return null;
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return null;
        }
        $dir = trailingslashit($uploads['basedir']) . 'lkba-vouchers/pdfs';
        if (!wp_mkdir_p($dir)) {
            return null;
        }

        $code = !empty($order->voucher_code)
            ? (string) $order->voucher_code
            : LKBA_Vouchers_Orders::ensure_voucher_code((int) $order->id);

        $font_dir = LKBA_VOUCHERS_PLUGIN_DIR . 'assets/fonts';
        $img_dir  = LKBA_VOUCHERS_PLUGIN_DIR . 'assets/img';

        try {
            $default_config = (new \Mpdf\Config\ConfigVariables())->getDefaults();
            $font_dirs      = $default_config['fontDir'];
            $default_font   = (new \Mpdf\Config\FontVariables())->getDefaults();
            $font_data      = $default_font['fontdata'];

            $mpdf = new \Mpdf\Mpdf(array(
                'fontDir'  => array_merge($font_dirs, array($font_dir)),
                'fontdata' => $font_data + array(
                    'suisse' => array('R' => 'SuisseWorks-Regular-WebS.ttf'),
                    'inter'  => array('R' => 'Inter-roman.ttf'),
                ),
                'default_font'  => 'suisse',
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 0,
                'margin_right'  => 0,
                'margin_top'    => 38.392,
                'margin_bottom' => 0,
                'tempDir'       => $dir,
            ));

            $entries = (int) $order->entries;
            $unit    = (int) $order->class_value;
            // Display the effective unit used for total if discount applied.
            if ($entries > 0) {
                $eff = (int) round((float) $order->sum_total / $entries);
                if ($eff > 0) {
                    $unit = $eff;
                }
            }
            $total = (float) $order->sum_total;

            $entry_word = ' vstupy/';
            if ($entries === 1) {
                $entry_word = ' vstup/';
            } elseif ($entries > 4) {
                $entry_word = ' vstupů/';
            }

            $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data='
                . rawurlencode(LKBA_Vouchers_Settings::get_validation_url($code));

            $logo   = $img_dir . '/LKBA_logo_2.png';
            $footer = $img_dir . '/LKBA-voucher-footer-A4.jpg';

            $css = 'body{font-family:suisse,serif;}div,p,img{margin:0;padding:0;}.col{float:left;}.col-50{width:90mm;}'
                . '.QR{width:17mm;height:17mm;}.QR img{width:100%;height:auto;}.sans{font-family:inter,sans-serif;}'
                . '.serif{font-family:suisse,serif;}.serif,.sans{font-size:14.832pt;line-height:18pt;}'
                . '.logo{width:44.125mm;height:36.59mm;}.row{width:180mm;margin-left:15mm;margin-right:15mm;}'
                . '.row-1st{margin-bottom:73.518mm;}.ta-R{text-align:right;}.label-top{padding-top:11.839mm;}'
                . '.footer{position:absolute;left:0;bottom:0;right:0;width:210mm;height:90mm;}.clear{clear:both;}';

            $html  = '<div class="row row-1st"><div class="col col-50"><div class="logo">';
            if (is_readable($logo)) {
                $html .= '<img src="' . esc_attr($logo) . '" width="44.125mm" alt="LKBA"/>';
            }
            $html .= '</div></div><div class="col col-50"><div class="label-top ta-R">';
            $html .= '<p class="serif ta-R">' . esc_html__('Dárkový poukaz', 'lkba-vouchers') . '</p>';
            $html .= '<p class="sans ta-R">' . esc_html($code) . '</p>';
            $html .= '</div></div><div class="clear"></div></div>';
            $html .= '<div class="row"><div class="col col-50"><div class="QR"><img src="' . esc_url($qr_url) . '" width="17mm" height="17mm" alt="QR"/></div></div>';
            $html .= '<div class="col col-50"><div class="ta-R">';
            $html .= '<p class="serif ta-R">Barre</p>';
            $html .= '<p class="serif ta-R">' . esc_html((string) $entries . $entry_word) . self::pretty_price($unit) . '</p>';
            $html .= '<p class="serif ta-R">' . self::pretty_price($total) . '</p>';
            $html .= '</div></div></div>';
            if (is_readable($footer)) {
                $html .= '<div class="footer"><img src="' . esc_attr($footer) . '" width="210mm" height="90mm" alt=""/></div>';
            }

            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

            $filename = 'LKBA_voucher_' . sanitize_file_name($order->order_number) . '.pdf';
            $full     = trailingslashit($dir) . $filename;
            $mpdf->Output($full, \Mpdf\Output\Destination::FILE);

            return 'lkba-vouchers/pdfs/' . $filename;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $relative
     * @return string
     */
    public static function absolute_path($relative) {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . ltrim((string) $relative, '/');
    }

    /**
     * @param float|int $amount
     * @return string
     */
    public static function pretty_price($amount) {
        return number_format_i18n((float) $amount, 0) . ' Kč';
    }
}
