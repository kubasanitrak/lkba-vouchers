<?php
/**
 * Front assets.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
    }

    public function enqueue() {
        if (!is_singular() && !is_page()) {
            return;
        }
        global $post;
        if (!$post || !is_a($post, 'WP_Post')) {
            return;
        }
        $content = (string) $post->post_content;
        $needs   = has_shortcode($content, 'lkba_voucher_form')
            || has_shortcode($content, 'lkba_voucher_validate')
            || has_shortcode($content, 'lkba_voucher_payment_success')
            || has_shortcode($content, 'lkba_voucher_payment_failed');
        if (!$needs) {
            return;
        }

        wp_enqueue_style(
            'lkba-vouchers',
            LKBA_VOUCHERS_PLUGIN_URL . 'public/css/lkba-vouchers.css',
            array(),
            LKBA_VOUCHERS_VERSION
        );

        if (has_shortcode($content, 'lkba_voucher_form')) {
            wp_enqueue_script(
                'lkba-vouchers-form',
                LKBA_VOUCHERS_PLUGIN_URL . 'public/js/voucher-form.js',
                array(),
                LKBA_VOUCHERS_VERSION,
                true
            );
        }
    }
}
