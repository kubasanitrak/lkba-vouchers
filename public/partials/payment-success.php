<?php
/**
 * @var object|null $order
 * @var bool        $paid
 * @var string      $support
 * @var string      $ref
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lkba-vouchers lkba-payment-return">
    <?php if ($paid) : ?>
        <h2><?php echo esc_html__('Platba úspěšná', 'lkba-vouchers'); ?></h2>
        <p><?php echo esc_html__('Dárkový poukaz a fakturu najdete v e-mailu.', 'lkba-vouchers'); ?></p>
    <?php else : ?>
        <h2><?php echo esc_html__('Platba se zpracovává', 'lkba-vouchers'); ?></h2>
        <p><?php echo esc_html__('Potvrzení přijde e-mailem během chvíle.', 'lkba-vouchers'); ?></p>
    <?php endif; ?>
    <?php if ($order) : ?>
        <p><strong><?php echo esc_html($order->order_number); ?></strong>
            — <?php echo esc_html($order->payment_status); ?></p>
        <?php if ($paid && is_user_logged_in()) : ?>
            <?php if (!empty($order->voucher_pdf)) : ?>
                <p><a href="<?php echo esc_url(LKBA_Vouchers_Orders::voucher_download_url((int) $order->id)); ?>"><?php echo esc_html__('Stáhnout poukaz', 'lkba-vouchers'); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($order->fakturoid_pdf)) : ?>
                <p><a href="<?php echo esc_url(LKBA_Vouchers_Orders::invoice_download_url((int) $order->id)); ?>"><?php echo esc_html__('Stáhnout fakturu', 'lkba-vouchers'); ?></a></p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
    <p><?php echo esc_html__('Podpora:', 'lkba-vouchers'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a></p>
</div>
