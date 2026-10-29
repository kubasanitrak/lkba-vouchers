# lkba-vouchers

Gift vouchers for Lenka Krejčová Barre Academy.

Requires [`lkba-payments`](https://github.com/kubasanitrak/lkba-payments) ≥ 0.2.0 and Composer dependency **mpdf/mpdf** (installed on release zip).

Repository: https://github.com/kubasanitrak/lkba-vouchers

## Status

Phase 4 — voucher form, bank + Comgate, mPDF on paid, staff validation. Legacy `/darkovy-poukaz` PHP app stays until Phase 5.

## Shortcodes

| Shortcode | Purpose |
|-----------|---------|
| `[lkba_voucher_form]` | Config form + bank recap / Comgate redirect |
| `[lkba_voucher_payment_success]` | Comgate return paid |
| `[lkba_voucher_payment_failed]` | Comgate return fail |
| `[lkba_voucher_validate]` | Staff validation (`edit_posts`) |

Put `[lkba_voucher_form]` on the WP page that replaces `/darkovy-poukaz` content (theme page), without deleting the standalone app yet.

## Local setup

```bash
composer install --no-dev
```

## Releasing

1. Bump `Version` and `LKBA_VOUCHERS_VERSION`.
2. Update `CHANGELOG.md`.
3. Tag `v0.2.0`.
