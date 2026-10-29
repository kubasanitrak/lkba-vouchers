<?php
/**
 * Voucher settings stub.
 */

if (!defined('ABSPATH')) {
    exit;
}

settings_errors('lkba_vouchers');

$default_company = get_option(LKBA_Vouchers_Settings::OPTION_DEFAULT_COMPANY, 'company-1');
$support_email   = get_option(LKBA_Vouchers_Settings::OPTION_SUPPORT_EMAIL, '');
$sender_name     = get_option(LKBA_Vouchers_Settings::OPTION_SENDER_NAME, '');
$sender_email    = get_option(LKBA_Vouchers_Settings::OPTION_SENDER_EMAIL, '');
$terms_page      = (int) get_option(LKBA_Vouchers_Settings::OPTION_TERMS_PAGE, 0);
$privacy_page    = (int) get_option(LKBA_Vouchers_Settings::OPTION_PRIVACY_PAGE, 0);
$storno_page     = (int) get_option(LKBA_Vouchers_Settings::OPTION_STORNO_PAGE, 0);
$tiers_json      = wp_json_encode(LKBA_Vouchers_Settings::get_price_tiers(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$company_choices = array();
if (class_exists('LKBA_Payments_Companies')) {
    $company_choices = LKBA_Payments_Companies::get_choices();
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Nastavení poukazů', 'lkba-vouchers'); ?></h1>
    <p><?php echo esc_html__('Phase 0 settings stubs. Prices are VAT-inclusive (decision §5).', 'lkba-vouchers'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('lkba_vouchers_save_settings', 'lkba_vouchers_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="lkba_default_company"><?php echo esc_html__('Default invoice company', 'lkba-vouchers'); ?></label></th>
                <td>
                    <select id="lkba_default_company" name="lkba_default_company">
                        <?php if ($company_choices === array()) : ?>
                            <option value="company-1" <?php selected($default_company, 'company-1'); ?>>company-1</option>
                            <option value="company-2" <?php selected($default_company, 'company-2'); ?>>company-2</option>
                        <?php else : ?>
                            <?php foreach ($company_choices as $choice) : ?>
                                <option value="<?php echo esc_attr($choice['id']); ?>" <?php selected($default_company, $choice['id']); ?>>
                                    <?php echo esc_html($choice['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_price_tiers_json"><?php echo esc_html__('Price tiers (JSON stub)', 'lkba-vouchers'); ?></label></th>
                <td>
                    <textarea class="large-text code" rows="10" id="lkba_price_tiers_json" name="lkba_price_tiers_json"><?php echo esc_textarea($tiers_json); ?></textarea>
                    <p class="description"><?php echo esc_html__('Legacy defaults: 450 → 1/5/10/20; 500 → 1/5/10. UI editor in Phase 4.', 'lkba-vouchers'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_support_email"><?php echo esc_html__('Support e-mail', 'lkba-vouchers'); ?></label></th>
                <td><input type="email" class="regular-text" id="lkba_support_email" name="lkba_support_email" value="<?php echo esc_attr($support_email); ?>" placeholder="<?php echo esc_attr__('TBD', 'lkba-vouchers'); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_sender_name"><?php echo esc_html__('Sender name', 'lkba-vouchers'); ?></label></th>
                <td><input type="text" class="regular-text" id="lkba_sender_name" name="lkba_sender_name" value="<?php echo esc_attr($sender_name); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="lkba_sender_email"><?php echo esc_html__('Sender e-mail', 'lkba-vouchers'); ?></label></th>
                <td><input type="email" class="regular-text" id="lkba_sender_email" name="lkba_sender_email" value="<?php echo esc_attr($sender_email); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Terms page', 'lkba-vouchers'); ?></th>
                <td><?php wp_dropdown_pages(array(
                    'name'              => 'lkba_terms_page',
                    'selected'          => $terms_page,
                    'show_option_none'  => __('— Select —', 'lkba-vouchers'),
                    'option_none_value' => '0',
                )); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Privacy page', 'lkba-vouchers'); ?></th>
                <td><?php wp_dropdown_pages(array(
                    'name'              => 'lkba_privacy_page',
                    'selected'          => $privacy_page,
                    'show_option_none'  => __('— Select —', 'lkba-vouchers'),
                    'option_none_value' => '0',
                )); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Storno page', 'lkba-vouchers'); ?></th>
                <td><?php wp_dropdown_pages(array(
                    'name'              => 'lkba_storno_page',
                    'selected'          => $storno_page,
                    'show_option_none'  => __('— Select —', 'lkba-vouchers'),
                    'option_none_value' => '0',
                )); ?></td>
            </tr>
        </table>

        <?php submit_button(__('Uložit', 'lkba-vouchers')); ?>
    </form>
</div>
