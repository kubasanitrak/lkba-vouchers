<?php
/**
 * @var object[] $orders
 * @var string   $status
 * @var string   $search
 */

if (!defined('ABSPATH')) {
    exit;
}
$base    = admin_url('admin.php?page=lkba-vouchers');
$support = LKBA_Vouchers_Settings::get_support_email();
?>
<div class="wrap">
    <h1><?php echo esc_html__('Objednávky poukazů', 'lkba-vouchers'); ?></h1>
    <p>
        <a href="<?php echo esc_url($base); ?>"><?php echo esc_html__('Vše', 'lkba-vouchers'); ?></a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'payment_pending', $base)); ?>">payment_pending</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'valid', $base)); ?>">valid</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'used', $base)); ?>">used</a> |
        <a href="<?php echo esc_url(add_query_arg('status', 'cancelled', $base)); ?>">cancelled</a>
    </p>
    <form method="get">
        <input type="hidden" name="page" value="lkba-vouchers">
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Hledat…', 'lkba-vouchers'); ?>">
        <?php submit_button(__('Hledat', 'lkba-vouchers'), 'secondary', '', false); ?>
    </form>
    <p><?php echo esc_html__('Podpora:', 'lkba-vouchers'); ?>
        <a href="mailto:<?php echo esc_attr($support); ?>"><?php echo esc_html($support); ?></a></p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Číslo', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Kód', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Zákazník', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Lekce', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Stav', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Platba', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Částka', 'lkba-vouchers'); ?></th>
                <th><?php echo esc_html__('Akce', 'lkba-vouchers'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$orders) : ?>
                <tr><td colspan="8"><?php echo esc_html__('Žádné objednávky.', 'lkba-vouchers'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($orders as $o) : ?>
                    <?php $nonce = wp_create_nonce('lkba_voucher_order_' . $o->id); ?>
                    <tr>
                        <td><code><?php echo esc_html($o->order_number); ?></code></td>
                        <td><?php echo esc_html((string) $o->voucher_code); ?></td>
                        <td>
                            <?php echo esc_html($o->buyer_name); ?><br>
                            <a href="mailto:<?php echo esc_attr($o->buyer_email); ?>"><?php echo esc_html($o->buyer_email); ?></a>
                            <?php if ($o->gift_name) : ?><br><small><?php echo esc_html__('Pro:', 'lkba-vouchers') . ' ' . esc_html($o->gift_name); ?></small><?php endif; ?>
                        </td>
                        <td><?php echo esc_html((string) $o->entries . ' × ' . (int) $o->class_value . ' Kč'); ?></td>
                        <td><?php echo esc_html($o->status); ?></td>
                        <td><?php echo esc_html($o->payment_status . ' / ' . $o->payment_method); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float) $o->sum_total, 0)); ?> Kč</td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(array('lkba_voucher_action' => 'confirm', 'order_id' => $o->id, '_wpnonce' => $nonce), $base)); ?>"><?php echo esc_html__('Potvrdit', 'lkba-vouchers'); ?></a>
                            |
                            <a href="<?php echo esc_url(add_query_arg(array('lkba_voucher_action' => 'cancel', 'order_id' => $o->id, '_wpnonce' => $nonce), $base)); ?>"><?php echo esc_html__('Zrušit', 'lkba-vouchers'); ?></a>
                            <?php if (!empty($o->voucher_pdf)) : ?>
                                | <a href="<?php echo esc_url(LKBA_Vouchers_Orders::voucher_download_url((int) $o->id)); ?>"><?php echo esc_html__('Poukaz', 'lkba-vouchers'); ?></a>
                            <?php endif; ?>
                            <?php if (!empty($o->fakturoid_pdf)) : ?>
                                | <a href="<?php echo esc_url(LKBA_Vouchers_Orders::invoice_download_url((int) $o->id)); ?>"><?php echo esc_html__('Faktura', 'lkba-vouchers'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
