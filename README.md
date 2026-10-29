# lkba-vouchers

Gift vouchers for Lenka Krejčová Barre Academy.

Requires [`lkba-payments`](https://github.com/kubasanitrak/lkba-payments).

Repository: https://github.com/kubasanitrak/lkba-vouchers

## Status

Phase 0 scaffold — schema, settings and shortcode shells only. PDF/validation arrive later.

## Shortcodes (stubs)

| Shortcode | Purpose |
|-----------|---------|
| `[lkba_voucher_form]` | Gift voucher config / checkout form |
| `[lkba_voucher_validate]` | Staff validation UI (capability-gated later) |

## Releasing

1. Bump `Version` and `LKBA_VOUCHERS_VERSION` in `lkba-vouchers.php`.
2. Add a `## [x.y.z]` section to `CHANGELOG.md`.
3. Push tag `v0.1.0` matching the header.
