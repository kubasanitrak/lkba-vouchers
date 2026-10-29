# Changelog

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
