<?php
/**
 * Gift voucher config form — legacy darkovy-poukaz markup/classes.
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
$has_inv   = trim(($d_inv['company_name'] ?? '') . ($d_inv['street'] ?? '') . ($d_inv['ic'] ?? '')) !== '';
$d_method  = isset($draft['payment_method']) ? sanitize_key($draft['payment_method']) : ($methods[0] ?? 'bank_transfer');
if (!in_array($d_method, $methods, true)) {
    $d_method = $methods[0] ?? 'bank_transfer';
}
$tiers_json = wp_json_encode($tiers);
$gift_open  = $d_gift !== '';
?>
<div class="lkba-vouchers lkba-voucher-form signup-form--wrapper vouchers-form--wrapper mar-T mar-B pad-B"
     data-tiers="<?php echo esc_attr($tiers_json); ?>"
     data-bundle-base="<?php echo esc_attr((string) $bundle['base']); ?>"
     data-bundle-min="<?php echo esc_attr((string) $bundle['min']); ?>"
     data-bundle-discount="<?php echo esc_attr((string) $bundle['discount']); ?>">

    <?php if ($error_msg !== '') : ?>
        <div class="signup-form--submit_message pad-B lkba-voucher-form__error" role="alert">
            <h2 class="color-red message" style="color:var(--LKBA-red,#c00)"><?php echo esc_html($error_msg); ?></h2>
        </div>
    <?php endif; ?>

    <div class="signup-form-container">
        <form method="post" action="" id="signup-formID" class="signup-form lkba-voucher-form__form" accept-charset="UTF-8">
            <?php wp_nonce_field('lkba_voucher_order', 'lkba_voucher_nonce'); ?>
            <div class="signup-form--inner">

                <div class="signup-form--inner_row mar-B full-W">
                    <h5 class="plain value-label taC"><?php echo esc_html__('hodnota jedné lekce', 'lkba-vouchers'); ?></h5>
                </div>

                <div class="signup-form--inner_row flex-row lesson-value--items full-W lkba-voucher-form__value">
                    <div class="lesson-value--items_row">
                        <?php foreach ($tiers as $tier) : ?>
                            <div class="lesson-value--item">
                                <label class="nice-input radio-input workshop-terms--item_label lkba-voucher-form__tier">
                                    <input class="workshop-id-input" type="radio" name="lkba_value"
                                           value="<?php echo esc_attr((string) $tier['value']); ?>"
                                           <?php checked($d_value, (int) $tier['value']); ?>
                                           data-entries="<?php echo esc_attr(wp_json_encode($tier['entries'])); ?>">
                                    <span class="checkmark"></span>
                                    <div class="workshop-terms--item_caption">
                                        <h2 class="workshop-terms--item_price vouchers-value">
                                            <?php echo esc_html(number_format_i18n((int) $tier['value'], 0)); ?> Kč
                                        </h2>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="padded-content pad-B">
                        <div class="signup-form--inner_row mar-B-0">
                            <p class="plain taC">
                                <?php echo esc_html__('Počet lekcí', 'lkba-vouchers'); ?>
                                (<span id="lkba_entries_hint" class="lkba-voucher-form__allowed"></span>
                                (<span id="lkba_total_display" class="lkba-voucher-form__total">—</span>
                            </p>
                        </div>
                        <div class="signup-form--inner_row mar-B lkba-voucher-form__qty">
                            <div class="quantity">
                                <input type="number" name="lkba_entries" id="lkba_entries"
                                       class="input-text qty text"
                                       value="<?php echo esc_attr((string) $d_entries); ?>"
                                       min="1" max="20" required
                                       aria-label="<?php echo esc_attr__('Počet lekcí', 'lkba-vouchers'); ?>"
                                       inputmode="numeric" autocomplete="off">
                                <div class="quantity-nav">
                                    <div class="quantity-button quantity-down lkba-qty-down" role="button" tabindex="0" aria-label="−">-</div>
                                    <div class="quantity-button quantity-up lkba-qty-up" role="button" tabindex="0" aria-label="+">+</div>
                                </div>
                            </div>
                        </div>
                        <div class="signup-form--inner_row mar-B show-and-hide slideUp lkba-voucher-form__all-lessons"
                             id="lkba_all_lessons_row" <?php echo $d_all ? '' : ''; ?>>
                            <div class="form-group taC">
                                <input type="checkbox" name="lkba_all_lessons" id="lkba_all_lessons"
                                       class="custom-checkbox" value="1" <?php checked($d_all); ?>>
                                <label for="lkba_all_lessons"><?php echo esc_html__('vč. 45 min. lekce s Lenkou a Terezou M.', 'lkba-vouchers'); ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vouchers-form--content mar-T-0">
                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_name"><?php echo esc_html__('Jméno a příjmení', 'lkba-vouchers'); ?> <span style="color: red;">*</span></label>
                        <input type="text" name="lkba_name" id="lkba_name" class="required"
                               placeholder="<?php echo esc_attr__('Jméno Příjmení', 'lkba-vouchers'); ?>"
                               required value="<?php echo esc_attr($d_name); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_email"><?php echo esc_html__('E-mail', 'lkba-vouchers'); ?> <span style="color: red;">*</span></label>
                        <input type="email" name="lkba_email" id="lkba_email" class="required email"
                               placeholder="email@email.cz"
                               required value="<?php echo esc_attr($d_email); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col">
                        <label for="lkba_phone"><?php echo esc_html__('Telefon', 'lkba-vouchers'); ?> <span style="color: red;">*</span></label>
                        <input type="text" name="lkba_phone" id="lkba_phone" class="required"
                               placeholder="+420…"
                               required value="<?php echo esc_attr($d_phone); ?>">
                    </div>

                    <div class="signup-form--inner_row flex-col">
                        <div class="form-group">
                            <input type="checkbox" class="custom-checkbox" id="lkba_is_gift" data-group="gift"
                                   value="1" <?php checked($gift_open); ?>>
                            <label for="lkba_is_gift"><?php echo esc_html__('Jedná se o dárek', 'lkba-vouchers'); ?></label>
                        </div>
                    </div>
                    <div class="signup-form--inner_row flex-col optional-gift <?php echo $gift_open ? '' : 'rolled-up'; ?>">
                        <label for="lkba_gift_name"><?php echo esc_html__('Jméno obdarovaného', 'lkba-vouchers'); ?></label>
                        <input type="text" name="lkba_gift_name" id="lkba_gift_name" value="<?php echo esc_attr($d_gift); ?>">
                    </div>

                    <div class="signup-form--inner_row flex-col">
                        <div class="form-group">
                            <input type="checkbox" class="custom-checkbox" id="lkba_want_invoice" data-group="invoice"
                                   value="1" <?php checked($has_inv); ?>>
                            <label for="lkba_want_invoice"><?php echo esc_html__('Chci fakturační údaje', 'lkba-vouchers'); ?></label>
                        </div>
                    </div>
                    <div class="signup-form--inner_row flex-col optional lkba-voucher-form__invoice <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_company"><?php echo esc_html__('Firma / jméno', 'lkba-vouchers'); ?></label>
                        <input type="text" name="lkba_inv_company" id="lkba_inv_company" value="<?php echo esc_attr($d_inv['company_name'] ?? ''); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col optional <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_street"><?php echo esc_html__('Ulice', 'lkba-vouchers'); ?></label>
                        <input type="text" name="lkba_inv_street" id="lkba_inv_street" value="<?php echo esc_attr($d_inv['street'] ?? ''); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col optional <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_zip"><?php echo esc_html__('PSČ / město', 'lkba-vouchers'); ?></label>
                        <input type="text" name="lkba_inv_zip" id="lkba_inv_zip" placeholder="PSČ" value="<?php echo esc_attr($d_inv['zip'] ?? ''); ?>">
                        <input type="text" name="lkba_inv_city" id="lkba_inv_city" placeholder="<?php echo esc_attr__('Město', 'lkba-vouchers'); ?>" value="<?php echo esc_attr($d_inv['city'] ?? ''); ?>">
                    </div>
                    <div class="signup-form--inner_row flex-col optional <?php echo $has_inv ? '' : 'rolled-up'; ?>">
                        <label for="lkba_inv_ic">IČO / DIČ</label>
                        <input type="text" name="lkba_inv_ic" id="lkba_inv_ic" placeholder="IČO" value="<?php echo esc_attr($d_inv['ic'] ?? ''); ?>">
                        <input type="text" name="lkba_inv_dic" id="lkba_inv_dic" placeholder="DIČ" value="<?php echo esc_attr($d_inv['dic'] ?? ''); ?>">
                    </div>

                    <?php if (count($methods) > 1) : ?>
                        <div class="signup-form--inner_row flex-col lkba-voucher-form__payment">
                            <label><?php echo esc_html__('Způsob platby', 'lkba-vouchers'); ?> <span style="color: red;">*</span></label>
                            <?php if (in_array('bank_transfer', $methods, true)) : ?>
                                <div class="form-group">
                                    <input type="radio" name="lkba_payment_method" id="lkba_pay_bank" value="bank_transfer" <?php checked($d_method, 'bank_transfer'); ?>>
                                    <label for="lkba_pay_bank"><?php echo esc_html__('Bankovní převod (QR / proforma)', 'lkba-vouchers'); ?></label>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array('comgate', $methods, true)) : ?>
                                <div class="form-group">
                                    <input type="radio" name="lkba_payment_method" id="lkba_pay_card" value="comgate" <?php checked($d_method, 'comgate'); ?>>
                                    <label for="lkba_pay_card"><?php echo esc_html__('Platební karta (Comgate)', 'lkba-vouchers'); ?></label>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <input type="hidden" name="lkba_payment_method" value="<?php echo esc_attr($methods[0] ?? 'bank_transfer'); ?>">
                    <?php endif; ?>

                    <div class="signup-form--inner_row flex-col lkba-voucher-form__legal">
                        <div class="form-group">
                            <input type="checkbox" name="lkba_legal" id="lkba_legal" class="custom-checkbox" value="1" required>
                            <label for="lkba_legal">
                                <?php echo esc_html__('Souhlasím s podmínkami a zpracováním osobních údajů', 'lkba-vouchers'); ?>
                                <?php if ($terms_p) : ?> · <a target="_blank" rel="noopener" class="strong" href="<?php echo esc_url(get_permalink($terms_p)); ?>"><?php echo esc_html__('podmínky', 'lkba-vouchers'); ?></a><?php endif; ?>
                                <?php if ($privacy) : ?> · <a target="_blank" rel="noopener" class="strong" href="<?php echo esc_url(get_permalink($privacy)); ?>"><?php echo esc_html__('soukromí', 'lkba-vouchers'); ?></a><?php endif; ?>
                                <?php if ($storno) : ?> · <a target="_blank" rel="noopener" class="strong" href="<?php echo esc_url(get_permalink($storno)); ?>"><?php echo esc_html__('storno', 'lkba-vouchers'); ?></a><?php endif; ?>
                                <span style="color: red;">*</span>
                            </label>
                        </div>
                    </div>

                    <div class="signup-form--inner_row flex-col">
                        <button type="submit" id="submit-btnID" class="btn submit-btn form-submit btn-major lkba-voucher-form__submit">
                            <?php echo esc_html__('Objednat poukaz', 'lkba-vouchers'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <p class="lkba-voucher-form__support plain taC">
        <?php echo esc_html__('Podpora:', 'lkba-vouchers'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a>
    </p>
</div>
