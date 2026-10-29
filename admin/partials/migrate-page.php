<?php
/**
 * @var bool       $exists
 * @var array|null $result
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Migrace legacy lkba_goods', 'lkba-vouchers'); ?></h1>

    <?php if (is_array($result)) : ?>
        <div class="notice notice-info"><p><?php echo esc_html($result['message'] ?? ''); ?></p></div>
    <?php endif; ?>

    <p><?php echo esc_html__('Standalone darkovy-poukaz ukládá objednávky do tabulky lkba_goods (bez WP prefixu). Tento nástroj je naimportuje do wp_lkba_voucher_orders. Legacy PHP app zatím nemažte (Phase 5).', 'lkba-vouchers'); ?></p>

    <p>
        <strong><?php echo esc_html__('Stav tabulky:', 'lkba-vouchers'); ?></strong>
        <?php echo $exists
            ? esc_html__('nalezena — lze importovat', 'lkba-vouchers')
            : esc_html__('nenalezena na této DB', 'lkba-vouchers'); ?>
    </p>

    <h2><?php echo esc_html__('Mapování stavů', 'lkba-vouchers'); ?></h2>
    <ul>
        <li><code>pending</code> → payment_pending / awaiting</li>
        <li><code>paid</code> → valid / paid</li>
        <li><code>used</code> → used / paid</li>
        <li><code>canceled</code> → cancelled / failed</li>
    </ul>
    <p><?php echo esc_html__('unique_id (pokud existuje) → voucher_code. Duplikáty podle kódu se přeskočí. PDF se při importu negenerují — u zaplacených lze znovu potvrdit v seznamu objednávek.', 'lkba-vouchers'); ?></p>

    <form method="post">
        <?php wp_nonce_field('lkba_vouchers_migrate', 'lkba_vouchers_migrate_nonce'); ?>
        <?php submit_button(__('Importovat (max 1000 řádků)', 'lkba-vouchers'), 'primary', 'submit', true, $exists ? array() : array('disabled' => 'disabled')); ?>
    </form>

    <h2><?php echo esc_html__('Ruční SQL kontrola', 'lkba-vouchers'); ?></h2>
    <pre>SHOW TABLES LIKE 'lkba_goods';
SELECT id, name, email, class_value, entries, state, unique_id FROM lkba_goods ORDER BY id DESC LIMIT 20;</pre>
</div>
