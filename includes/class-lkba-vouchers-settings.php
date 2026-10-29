<?php
/**
 * Voucher product + legal settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Vouchers_Settings {

    const DEFAULT_SUPPORT_EMAIL = 'rezervace@barreacademy.cz';

    const OPTION_DEFAULT_COMPANY = 'lkba_vouchers_default_company_id';
    const OPTION_PRICE_TIERS     = 'lkba_vouchers_price_tiers';
    const OPTION_SUPPORT_EMAIL   = 'lkba_vouchers_support_email';
    const OPTION_SENDER_NAME     = 'lkba_vouchers_email_sender_name';
    const OPTION_SENDER_EMAIL    = 'lkba_vouchers_email_sender_email';
    const OPTION_TERMS_PAGE      = 'lkba_vouchers_terms_page';
    const OPTION_PRIVACY_PAGE    = 'lkba_vouchers_privacy_page';
    const OPTION_STORNO_PAGE     = 'lkba_vouchers_storno_page';
    const OPTION_SUCCESS_PAGE    = 'lkba_vouchers_payment_success_page';
    const OPTION_FAILED_PAGE     = 'lkba_vouchers_payment_failed_page';
    const OPTION_VALIDATE_PAGE   = 'lkba_vouchers_validate_page';
    /** When class 450 + entries ≥ N and “all lessons” unchecked → this unit price. */
    const OPTION_BUNDLE_DISCOUNT = 'lkba_vouchers_bundle_discount_value';
    const OPTION_BUNDLE_MIN      = 'lkba_vouchers_bundle_discount_min_entries';
    const OPTION_BUNDLE_BASE     = 'lkba_vouchers_bundle_discount_base_value';

    public function __construct() {
        // Admin UI registers fields.
    }

    public static function ensure_defaults() {
        if (false === get_option(self::OPTION_PRICE_TIERS, false)) {
            update_option(self::OPTION_PRICE_TIERS, self::default_price_tiers());
        }

        $defaults = array(
            self::OPTION_DEFAULT_COMPANY => 'company-1',
            self::OPTION_SUPPORT_EMAIL   => self::DEFAULT_SUPPORT_EMAIL,
            self::OPTION_SENDER_NAME     => 'Lenka Krejčová Barre Academy',
            self::OPTION_SENDER_EMAIL    => self::DEFAULT_SUPPORT_EMAIL,
            self::OPTION_TERMS_PAGE      => 0,
            self::OPTION_PRIVACY_PAGE    => 0,
            self::OPTION_STORNO_PAGE     => 0,
            self::OPTION_SUCCESS_PAGE    => 0,
            self::OPTION_FAILED_PAGE     => 0,
            self::OPTION_VALIDATE_PAGE   => 0,
            self::OPTION_BUNDLE_DISCOUNT => 430,
            self::OPTION_BUNDLE_MIN      => 10,
            self::OPTION_BUNDLE_BASE     => 450,
        );

        foreach ($defaults as $key => $value) {
            if (false === get_option($key, false)) {
                add_option($key, $value);
            }
        }

        if ((string) get_option(self::OPTION_SUPPORT_EMAIL, '') === '') {
            update_option(self::OPTION_SUPPORT_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        }
        if ((string) get_option(self::OPTION_SENDER_EMAIL, '') === '') {
            update_option(self::OPTION_SENDER_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        }
    }

    /**
     * Legacy: 450 → 1/5/10/20; 500 → 1/5/10.
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
        if (!is_array($tiers) || !$tiers) {
            return self::default_price_tiers();
        }
        $out = array();
        foreach ($tiers as $tier) {
            if (!is_array($tier) || empty($tier['value'])) {
                continue;
            }
            $entries = array();
            if (!empty($tier['entries']) && is_array($tier['entries'])) {
                foreach ($tier['entries'] as $e) {
                    $entries[] = (int) $e;
                }
            }
            $entries = array_values(array_unique(array_filter($entries)));
            sort($entries);
            $out[] = array(
                'value'   => (int) $tier['value'],
                'entries' => $entries ?: array(1),
            );
        }
        return $out ?: self::default_price_tiers();
    }

    /**
     * @return string
     */
    public static function get_default_company() {
        $id = (string) get_option(self::OPTION_DEFAULT_COMPANY, 'company-1');
        return $id !== '' ? $id : 'company-1';
    }

    /**
     * @return string
     */
    public static function get_support_email() {
        $email = (string) get_option(self::OPTION_SUPPORT_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        return $email !== '' ? $email : self::DEFAULT_SUPPORT_EMAIL;
    }

    /**
     * @return string
     */
    public static function get_sender_email() {
        $email = (string) get_option(self::OPTION_SENDER_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        return $email !== '' ? $email : self::DEFAULT_SUPPORT_EMAIL;
    }

    /**
     * Effective unit price (legacy: 450+≥10 entries without “all lessons” → 430).
     *
     * @param int  $class_value Selected tier (450/500).
     * @param int  $entries
     * @param bool $all_lessons
     * @return int
     */
    public static function resolve_unit_price($class_value, $entries, $all_lessons = false) {
        $class_value = (int) $class_value;
        $entries     = (int) $entries;
        $base        = (int) get_option(self::OPTION_BUNDLE_BASE, 450);
        $min         = (int) get_option(self::OPTION_BUNDLE_MIN, 10);
        $discount    = (int) get_option(self::OPTION_BUNDLE_DISCOUNT, 430);

        if ($class_value === $base && $entries >= $min && !$all_lessons) {
            return $discount;
        }
        return $class_value;
    }

    /**
     * Validate class_value + entries against matrix; return computed totals.
     *
     * @param int  $class_value
     * @param int  $entries
     * @param bool $all_lessons
     * @return array{ok:bool,unit:int,entries:int,total:float,error?:string}|array
     */
    public static function price_for($class_value, $entries, $all_lessons = false) {
        $class_value = (int) $class_value;
        $entries     = (int) $entries;
        $tier        = null;
        foreach (self::get_price_tiers() as $t) {
            if ((int) $t['value'] === $class_value) {
                $tier = $t;
                break;
            }
        }
        if (!$tier) {
            return array('ok' => false, 'error' => 'invalid_value');
        }
        if (!in_array($entries, $tier['entries'], true)) {
            return array('ok' => false, 'error' => 'invalid_entries');
        }
        $unit  = self::resolve_unit_price($class_value, $entries, $all_lessons);
        $total = (float) ($unit * $entries);
        return array(
            'ok'      => true,
            'unit'    => $unit,
            'entries' => $entries,
            'total'   => $total,
        );
    }

    /**
     * @param array $args
     * @return string
     */
    public static function get_payment_success_url(array $args = array()) {
        $page_id = (int) get_option(self::OPTION_SUCCESS_PAGE, 0);
        $url     = $page_id ? get_permalink($page_id) : '';
        if (!$url) {
            $url = home_url('/');
        }
        return $args ? add_query_arg($args, $url) : $url;
    }

    /**
     * @param array $args
     * @return string
     */
    public static function get_payment_failed_url(array $args = array()) {
        $page_id = (int) get_option(self::OPTION_FAILED_PAGE, 0);
        $url     = $page_id ? get_permalink($page_id) : '';
        if (!$url) {
            $url = home_url('/');
        }
        return $args ? add_query_arg($args, $url) : $url;
    }

    /**
     * Public validation landing (QR target). Falls back to home with ?voucher=.
     *
     * @param string $code
     * @return string
     */
    public static function get_validation_url($code = '') {
        $page_id = (int) get_option(self::OPTION_VALIDATE_PAGE, 0);
        $url     = $page_id ? get_permalink($page_id) : home_url('/voucher-verification/');
        if ($code !== '') {
            $url = add_query_arg('voucher', $code, $url);
        }
        return $url;
    }

    /**
     * Payment methods allowed for default company (and configured gateways).
     *
     * @return string[] bank_transfer, comgate
     */
    public static function get_allowed_payment_methods() {
        $company = self::get_default_company();
        $methods = array('bank_transfer');
        if (class_exists('LKBA_Payments_Gateway')) {
            $methods = LKBA_Payments_Gateway::allowed_methods($company);
        }
        if (!$methods) {
            $methods = array('bank_transfer');
        }
        if (in_array('comgate', $methods, true) && class_exists('LKBA_Payments_Comgate')) {
            if (!LKBA_Payments_Comgate::for_company($company)->is_configured()) {
                $methods = array_values(array_diff($methods, array('comgate')));
            }
        }
        return $methods;
    }
}
