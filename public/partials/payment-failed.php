<?php
/**
 * @var object|null $order
 * @var string      $support
 * @var string      $ref
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lkba-vouchers lkba-payment-return lkba-payment-return--failed">
    <h2><?php echo esc_html__('Platba se nepodařila', 'lkba-vouchers'); ?></h2>
    <p><?php echo esc_html__('Platba kartou byla zrušena nebo selhala. Napište nám, pokud potřebujete pomoci.', 'lkba-vouchers'); ?></p>
    <?php if ($order) : ?>
        <p><strong><?php echo esc_html($order->order_number); ?></strong>
            — <?php echo esc_html($order->status . ' / ' . $order->payment_status); ?></p>
    <?php endif; ?>
    <p><?php echo esc_html__('Kontakt:', 'lkba-vouchers'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a></p>
</div>
