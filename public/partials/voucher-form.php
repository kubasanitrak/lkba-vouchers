<?php
/**
 * Gift voucher config form.
 *
 * @var array  $draft
 * @var array  $tiers
 * @var array  $methods
 * @var array  $bundle
 * @var string $support
 * @var string $error_msg
 * @var int    $privacy
 * @var int    $terms_p
 * @var int    $storno
 */

if (!defined('ABSPATH')) {
    exit;
}

$d_name    = $draft['name'] ?? '';
$d_email   = $draft['email'] ?? '';
$d_phone   = $draft['phone'] ?? '';
$d_gift    = $draft['gift_name'] ?? '';
$d_value   = isset($draft['value']) ? (int) $draft['value'] : (int) ($tiers[1]['value'] ?? $tiers[0]['value'] ?? 500);
$d_entries = isset($draft['entries']) ? (int) $draft['entries'] : 1;
$d_all     = !empty($draft['all_lessons']);
$d_inv     = isset($draft['invoice']) && is_array($draft['invoice']) ? $draft['invoice'] : array();
$d_method  = isset($draft['payment_method']) ? sanitize_key($draft['payment_method']) : ($methods[0] ?? 'bank_transfer');
if (!in_array($d_method, $methods, true)) {
    $d_method = $methods[0] ?? 'bank_transfer';
}
$tiers_json = wp_json_encode($tiers);
?>
<div class="lkba-vouchers lkba-voucher-form" data-tiers="<?php echo esc_attr($tiers_json); ?>"
     data-bundle-base="<?php echo esc_attr((string) $bundle['base']); ?>"
     data-bundle-min="<?php echo esc_attr((string) $bundle['min']); ?>"
     data-bundle-discount="<?php echo esc_attr((string) $bundle['discount']); ?>">
    <h2><?php echo esc_html__('Dárkový poukaz', 'lkba-vouchers'); ?></h2>

    <?php if ($error_msg !== '') : ?>
        <div class="lkba-voucher-form__error" role="alert"><?php echo esc_html($error_msg); ?></div>
    <?php endif; ?>

    <form method="post" action="" class="lkba-voucher-form__form">
        <?php wp_nonce_field('lkba_voucher_order', 'lkba_voucher_nonce'); ?>

        <fieldset class="lkba-voucher-form__value">
            <legend><?php echo esc_html__('Hodnota jedné lekce', 'lkba-vouchers'); ?></legend>
            <?php foreach ($tiers as $tier) : ?>
                <label class="lkba-voucher-form__tier">
                    <input type="radio" name="lkba_value" value="<?php echo esc_attr((string) $tier['value']); ?>"
                        <?php checked($d_value, (int) $tier['value']); ?>
                        data-entries="<?php echo esc_attr(wp_json_encode($tier['entries'])); ?>">
                    <?php echo esc_html(number_format_i18n((int) $tier['value'], 0)); ?> Kč
                </label>
            <?php endforeach; ?>
        </fieldset>

        <p>
            <label for="lkba_entries"><?php echo esc_html__('Počet lekcí', 'lkba-vouchers'); ?></label><br>
            <span class="lkba-voucher-form__qty">
                <button type="button" class="lkba-qty-down" aria-label="-">−</button>
                <input type="number" name="lkba_entries" id="lkba_entries" value="<?php echo esc_attr((string) $d_entries); ?>" min="1" max="20" required>
                <button type="button" class="lkba-qty-up" aria-label="+">+</button>
            </span>
            <span class="lkba-voucher-form__allowed" id="lkba_entries_hint"></span>
        </p>

        <p class="lkba-voucher-form__all-lessons" id="lkba_all_lessons_row" hidden>
            <label>
                <input type="checkbox" name="lkba_all_lessons" id="lkba_all_lessons" value="1" <?php checked($d_all); ?>>
                <?php echo esc_html__('vč. 45 min. lekce s Lenkou a Terezou M.', 'lkba-vouchers'); ?>
            </label>
        </p>

        <p class="lkba-voucher-form__total">
            <?php echo esc_html__('Celkem:', 'lkba-vouchers'); ?>
            <strong id="lkba_total_display">—</strong>
        </p>

        <p>
            <label for="lkba_name"><?php echo esc_html__('Jméno a příjmení', 'lkba-vouchers'); ?></label><br>
            <input type="text" name="lkba_name" id="lkba_name" required value="<?php echo esc_attr($d_name); ?>">
        </p>
        <p>
            <label for="lkba_email"><?php echo esc_html__('E-mail', 'lkba-vouchers'); ?></label><br>
            <input type="email" name="lkba_email" id="lkba_email" required value="<?php echo esc_attr($d_email); ?>">
        </p>
        <p>
            <label for="lkba_phone"><?php echo esc_html__('Telefon', 'lkba-vouchers'); ?></label><br>
            <input type="text" name="lkba_phone" id="lkba_phone" required value="<?php echo esc_attr($d_phone); ?>">
        </p>
        <p>
            <label for="lkba_gift_name"><?php echo esc_html__('Jméno obdarovaného (volitelné)', 'lkba-vouchers'); ?></label><br>
            <input type="text" name="lkba_gift_name" id="lkba_gift_name" value="<?php echo esc_attr($d_gift); ?>">
        </p>

        <fieldset class="lkba-voucher-form__invoice">
            <legend><?php echo esc_html__('Fakturační údaje (volitelné)', 'lkba-vouchers'); ?></legend>
            <p><input type="text" name="lkba_inv_company" placeholder="<?php echo esc_attr__('Firma / jméno', 'lkba-vouchers'); ?>" value="<?php echo esc_attr($d_inv['company_name'] ?? ''); ?>"></p>
            <p><input type="text" name="lkba_inv_street" placeholder="<?php echo esc_attr__('Ulice', 'lkba-vouchers'); ?>" value="<?php echo esc_attr($d_inv['street'] ?? ''); ?>"></p>
            <p>
                <input type="text" name="lkba_inv_zip" placeholder="PSČ" value="<?php echo esc_attr($d_inv['zip'] ?? ''); ?>">
                <input type="text" name="lkba_inv_city" placeholder="<?php echo esc_attr__('Město', 'lkba-vouchers'); ?>" value="<?php echo esc_attr($d_inv['city'] ?? ''); ?>">
            </p>
            <p>
                <input type="text" name="lkba_inv_ic" placeholder="IČO" value="<?php echo esc_attr($d_inv['ic'] ?? ''); ?>">
                <input type="text" name="lkba_inv_dic" placeholder="DIČ" value="<?php echo esc_attr($d_inv['dic'] ?? ''); ?>">
            </p>
        </fieldset>

        <?php if (count($methods) > 1) : ?>
            <fieldset class="lkba-voucher-form__payment">
                <legend><?php echo esc_html__('Způsob platby', 'lkba-vouchers'); ?></legend>
                <?php if (in_array('bank_transfer', $methods, true)) : ?>
                    <p><label><input type="radio" name="lkba_payment_method" value="bank_transfer" <?php checked($d_method, 'bank_transfer'); ?>>
                        <?php echo esc_html__('Bankovní převod (QR / proforma)', 'lkba-vouchers'); ?></label></p>
                <?php endif; ?>
                <?php if (in_array('comgate', $methods, true)) : ?>
                    <p><label><input type="radio" name="lkba_payment_method" value="comgate" <?php checked($d_method, 'comgate'); ?>>
                        <?php echo esc_html__('Platební karta (Comgate)', 'lkba-vouchers'); ?></label></p>
                <?php endif; ?>
            </fieldset>
        <?php else : ?>
            <input type="hidden" name="lkba_payment_method" value="<?php echo esc_attr($methods[0] ?? 'bank_transfer'); ?>">
        <?php endif; ?>

        <p class="lkba-voucher-form__legal">
            <label>
                <input type="checkbox" name="lkba_legal" value="1" required>
                <?php echo esc_html__('Souhlasím s podmínkami a zpracováním osobních údajů', 'lkba-vouchers'); ?>
                <?php if ($terms_p) : ?> · <a href="<?php echo esc_url(get_permalink($terms_p)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('podmínky', 'lkba-vouchers'); ?></a><?php endif; ?>
                <?php if ($privacy) : ?> · <a href="<?php echo esc_url(get_permalink($privacy)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('soukromí', 'lkba-vouchers'); ?></a><?php endif; ?>
                <?php if ($storno) : ?> · <a href="<?php echo esc_url(get_permalink($storno)); ?>" target="_blank" rel="noopener"><?php echo esc_html__('storno', 'lkba-vouchers'); ?></a><?php endif; ?>
            </label>
        </p>

        <p>
            <button type="submit" class="lkba-voucher-form__submit"><?php echo esc_html__('Objednat poukaz', 'lkba-vouchers'); ?></button>
        </p>
    </form>

    <p class="lkba-voucher-form__support">
        <?php echo esc_html__('Podpora:', 'lkba-vouchers'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
