<?php
/**
 * @var array       $recap
 * @var object|null $order
 */

if (!defined('ABSPATH')) {
    exit;
}
$support = LKBA_Vouchers_Settings::get_support_email();
?>
<div class="lkba-vouchers lkba-voucher-recap">
    <h2><?php echo esc_html__('Objednávka přijata', 'lkba-vouchers'); ?></h2>
    <?php if ($order) : ?>
        <p><?php echo esc_html__('Číslo:', 'lkba-vouchers'); ?> <strong><?php echo esc_html($order->order_number); ?></strong></p>
        <p><?php echo esc_html(sprintf(
            /* translators: 1: entries 2: unit 3: total */
            __('%1$d lekcí × %2$s = %3$s', 'lkba-vouchers'),
            (int) $order->entries,
            LKBA_Vouchers_PDF::pretty_price($order->class_value),
            LKBA_Vouchers_PDF::pretty_price($order->sum_total)
        )); ?></p>
        <?php if (!empty($order->gift_name)) : ?>
            <p><?php echo esc_html__('Pro:', 'lkba-vouchers'); ?> <?php echo esc_html($order->gift_name); ?></p>
        <?php endif; ?>
        <p><?php echo esc_html__('Platební údaje jsme poslali e-mailem.', 'lkba-vouchers'); ?></p>
        <?php if (!empty($recap['instructions'])) : ?>
            <div class="lkba-voucher-recap__instructions">
                <?php echo $recap['instructions']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php elseif (!empty($recap['qr_url'])) : ?>
            <p><img src="<?php echo esc_url($recap['qr_url']); ?>" alt="QR" width="200" height="200"></p>
        <?php endif; ?>
    <?php endif; ?>
    <p><?php echo esc_html__('Podpora:', 'lkba-vouchers'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a></p>
</div>
