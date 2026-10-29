<?php
/**
 * Voucher product + legal settings stubs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Settings {

    const OPTION_DEFAULT_COMPANY = 'lkba_vouchers_default_company_id';
    const OPTION_PRICE_TIERS     = 'lkba_vouchers_price_tiers';
    const OPTION_SUPPORT_EMAIL   = 'lkba_vouchers_support_email';
    const OPTION_SENDER_NAME     = 'lkba_vouchers_email_sender_name';
    const OPTION_SENDER_EMAIL    = 'lkba_vouchers_email_sender_email';
    const OPTION_TERMS_PAGE      = 'lkba_vouchers_terms_page';
    const OPTION_PRIVACY_PAGE    = 'lkba_vouchers_privacy_page';
    const OPTION_STORNO_PAGE     = 'lkba_vouchers_storno_page';

    public function __construct() {
        // Admin UI registers fields.
    }

    public static function ensure_defaults() {
        if (false === get_option(self::OPTION_PRICE_TIERS, false)) {
            update_option(self::OPTION_PRICE_TIERS, self::default_price_tiers());
        }

        $defaults = array(
            self::OPTION_DEFAULT_COMPANY => 'company-1',
            self::OPTION_SUPPORT_EMAIL   => '',
            self::OPTION_SENDER_NAME     => 'Lenka Krejčová Barre Academy',
            self::OPTION_SENDER_EMAIL    => '',
            self::OPTION_TERMS_PAGE      => 0,
            self::OPTION_PRIVACY_PAGE    => 0,
            self::OPTION_STORNO_PAGE     => 0,
        );

        foreach ($defaults as $key => $value) {
            if (false === get_option($key, false)) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Mirrors legacy form: 450 → 1/5/10/20 entries; 500 → 1/5/10.
     *
     * @return array<int, array{value:int,entries:int[]}>
     */
    public static function default_price_tiers() {
        return array(
            array(
                'value'   => 450,
                'entries' => array(1, 5, 10, 20),
            ),
            array(
                'value'   => 500,
                'entries' => array(1, 5, 10),
            ),
        );
    }

    /**
     * @return array<int, array{value:int,entries:int[]}>
     */
    public static function get_price_tiers() {
        $tiers = get_option(self::OPTION_PRICE_TIERS, array());
        return is_array($tiers) && $tiers !== array() ? $tiers : self::default_price_tiers();
    }
}
