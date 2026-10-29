<?php
/**
 * Front shortcode stubs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Shortcodes {

    public function __construct() {
        add_shortcode('lkba_voucher_form', array($this, 'render_form'));
        add_shortcode('lkba_voucher_validate', array($this, 'render_validate'));
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_form($atts = array()) {
        return $this->stub_notice(
            __('Voucher form shortcode — Phase 0 stub (guest checkout later).', 'lkba-vouchers')
        );
    }

    /**
     * Staff-only validation UI (decision §11) — capability check in Phase 4.
     *
     * @param array|string $atts
     * @return string
     */
    public function render_validate($atts = array()) {
        if (!current_user_can('edit_posts')) {
            return '';
        }
        return $this->stub_notice(
            __('Voucher validation shortcode — Phase 0 stub (WP staff only).', 'lkba-vouchers')
        );
    }

    /**
     * @param string $message
     * @return string
     */
    private function stub_notice($message) {
        if (!current_user_can('edit_posts')) {
            return '';
        }
        return '<div class="lkba-vouchers-stub"><p>' . esc_html($message) . '</p></div>';
    }
}
