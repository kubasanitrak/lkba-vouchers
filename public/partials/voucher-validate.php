<?php
/**
 * Staff validate UI.
 *
 * @var string      $code
 * @var object|null $order
 * @var string      $notice
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lkba-vouchers lkba-voucher-validate">
    <h2><?php echo esc_html__('Validace poukazu', 'lkba-vouchers'); ?></h2>

    <?php if ($notice !== '') : ?>
        <p class="lkba-voucher-validate__notice"><?php echo esc_html($notice); ?></p>
    <?php endif; ?>

    <form method="get" action="">
        <p>
            <label for="voucher"><?php echo esc_html__('Kód poukazu', 'lkba-vouchers'); ?></label><br>
            <input type="text" name="voucher" id="voucher" value="<?php echo esc_attr($code); ?>" required>
        </p>
        <p><button type="submit"><?php echo esc_html__('Vyhledat', 'lkba-vouchers'); ?></button></p>
    </form>

    <?php if ($code !== '' && !$order) : ?>
        <p><?php echo esc_html__('Poukaz nenalezen.', 'lkba-vouchers'); ?></p>
    <?php elseif ($order) : ?>
        <dl class="lkba-voucher-validate__meta">
            <dt><?php echo esc_html__('Objednávka', 'lkba-vouchers'); ?></dt>
            <dd><?php echo esc_html($order->order_number); ?></dd>
            <dt><?php echo esc_html__('Kód', 'lkba-vouchers'); ?></dt>
            <dd><code><?php echo esc_html($order->voucher_code); ?></code></dd>
            <dt><?php echo esc_html__('Zákazník', 'lkba-vouchers'); ?></dt>
            <dd><?php echo esc_html($order->buyer_name . ' / ' . $order->buyer_email); ?></dd>
            <dt><?php echo esc_html__('Lekce', 'lkba-vouchers'); ?></dt>
            <dd><?php echo esc_html((string) $order->entries . ' × ' . LKBA_Vouchers_PDF::pretty_price($order->class_value)); ?></dd>
            <dt><?php echo esc_html__('Platba', 'lkba-vouchers'); ?></dt>
            <dd><?php echo esc_html($order->payment_status); ?></dd>
            <dt><?php echo esc_html__('Stav', 'lkba-vouchers'); ?></dt>
            <dd><?php echo esc_html($order->status); ?></dd>
        </dl>
        <form method="post" action="">
            <?php wp_nonce_field('lkba_voucher_validate', 'lkba_validate_nonce'); ?>
            <input type="hidden" name="lkba_order_id" value="<?php echo esc_attr((string) $order->id); ?>">
            <p>
                <label for="lkba_status"><?php echo esc_html__('Nový stav', 'lkba-vouchers'); ?></label>
                <select name="lkba_status" id="lkba_status">
                    <option value="payment_pending" <?php selected($order->status, 'payment_pending'); ?>><?php echo esc_html__('payment_pending', 'lkba-vouchers'); ?></option>
                    <option value="valid" <?php selected($order->status, 'valid'); ?>><?php echo esc_html__('valid', 'lkba-vouchers'); ?></option>
                    <option value="used" <?php selected($order->status, 'used'); ?>><?php echo esc_html__('used', 'lkba-vouchers'); ?></option>
                    <option value="cancelled" <?php selected($order->status, 'cancelled'); ?>><?php echo esc_html__('cancelled', 'lkba-vouchers'); ?></option>
                </select>
            </p>
            <p><button type="submit"><?php echo esc_html__('Uložit stav', 'lkba-vouchers'); ?></button></p>
        </form>
    <?php endif; ?>
</div>
