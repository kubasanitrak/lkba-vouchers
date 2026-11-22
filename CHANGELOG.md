# Changelog

## [0.2.8] — 2026-11-22

### Changed

- Voucher form: `#lkba_entries_hint` shows allowed lesson counts on load; after first value/qty/all-lessons interaction it shows the calculated price (from `#lkba_total_display`, which stays hidden as calc source).

## [0.2.7] — 2026-11-15

### Fixed

- Payment tiles: `display:flex` no longer overrides `[hidden]`.
- Offer Comgate in chooser when company has it enabled (credentials still required at gateway start).
- Tile click explicitly checks radio + moves `.is-selected`.

## [0.2.6] — 2026-11-15

### Fixed

- Payment tiles: selected state sticks after click (`.is-selected` + `:has(:checked)` / `:checked` styles; JS syncs class on change).

## [0.2.5] — 2026-11-15

### Changed

- Voucher payment chooser: swipe slider replaced with tile radios (Bankovní převod / Platební karta); still POSTs `lkba_payment_method` (`bank_transfer` / `comgate`). Icons in `public/assets/payment/`.

## [0.2.4] — 2026-11-11

### Fixed

- Payment swipe: reliably update `p.lkba-voucher-form__pay-status` on change/input/init.

## [0.2.3] — 2026-11-11

### Fixed

- Payment swipe: labels get `.active` / `.inactive` on change + init; status text updates.

## [0.2.2] — 2026-11-11

### Changed

- Payment chooser: legacy swipe slider (`Převodem` / `Kartou`) when both bank + card allowed; still POSTs `lkba_payment_method`.

## [0.2.1] — 2026-10-30

### Changed

- `[lkba_voucher_form]` markup aligned with legacy `darkovy-poukaz` (`signup-form` / `lesson-value` / `quantity` classes). POST names + payment/recap unchanged.

## [0.2.0] — 2026-10-29

### Added

- Phase 4 vouchers MVP: price/entry matrix settings (legacy 450/500 + 430 bundle rule), default company, legal pages, support `rezervace@barreacademy.cz`.
- `[lkba_voucher_form]` — config form, dynamic price, session draft, bank / Comgate checkout (no cart).
- `V-` payments registry (resolver / paid / failed); order + payment emails; paid → mPDF gift voucher + Fakturoid invoice PDF.
- Guest checkout with WP user on payment confirmation.
- Staff validation: admin screen + `[lkba_voucher_validate]` (`edit_posts`).
- Legacy `lkba_goods` import UI + docs; legacy PHP app left in place (Phase 5 cutover).

## [0.1.0] — 2026-10-29

### Added

- Phase 0 scaffold: bootstrap with `lkba-payments` dependency, orders table stub, shortcode and admin menu stubs, release pipeline.
