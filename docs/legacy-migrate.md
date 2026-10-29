# Legacy `lkba_goods` migration

Standalone app: `/darkovy-poukaz` → MySQL table **`lkba_goods`** (usually unprefixed).

## Import

1. Ensure the WP database user can `SELECT` from `lkba_goods` (same DB as WordPress, or copy table in).
2. WP admin → **LKBA Poukazy → Migrace** → Import.
3. Rows map into `{$wpdb->prefix}lkba_voucher_orders`.
4. `unique_id` → `voucher_code` (duplicates skipped).
5. PDFs are **not** regenerated on import; use **Potvrdit** on an order if you need a fresh voucher PDF for a paid row.

## State map

| Legacy `state` | Plugin `status` | `payment_status` |
|----------------|-----------------|------------------|
| pending | payment_pending | awaiting |
| paid | valid | paid |
| used | used | paid |
| canceled | cancelled | failed |

## Phase 5

Keep serving legacy PHP until the WP shortcode page is trusted, then redirect `/darkovy-poukaz` and retire the standalone folder.
