<?php
/**
 * Front assets (Phase 0 stub).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        // Phase 4+: scoped CSS/JS for voucher form.
    }
}
