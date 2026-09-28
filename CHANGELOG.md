# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-09-28

### Added
- Person type selector (Pessoa Física / Pessoa Jurídica) with conditional CPF / CNPJ fields on the classic checkout.
- Framework-free CPF and CNPJ validators with modulo-11 check digits, including the alphanumeric CNPJ format (2026).
- CPF / CNPJ stored on the order (`_billing_persontype`, `_billing_cpf`, `_billing_cnpj`), editable in the admin order screen and included in order emails.
- CEP validation and formatting, with an optional CEP ↔ state (UF) consistency check.
- Brazilian phone validation (allocated DDDs, landline vs. mobile) and canonical formatting.
- Minimum order amount per state, configured in a WooCommerce settings tab and enforced on the classic cart/checkout and through the Store API (Cart/Checkout Blocks).
- "CPF or CNPJ" field for the Checkout block via the Additional Checkout Fields API.
- Dependency-free JavaScript input masks.
- HPOS (custom order tables) and Cart/Checkout Blocks compatibility declarations.
- Developer hooks: `wcbce_state_minimum_amount`, `wcbce_minimum_order_subtotal`, `wcbce_document_is_valid`, `wcbce_checkout_document_fields`, `wcbce_require_company_for_legal_entity`, `wcbce_blocks_document_required`, `wcbce_settings_fields`, `wcbce_minimum_order_not_met`.
- `uninstall.php` removing all plugin options (multisite aware).
- Translation template (`languages/wc-brazil-checkout-essentials.pot`).
- PHPUnit suite (no WordPress required), WPCS + PHPCompatibility ruleset and GitHub Actions CI on PHP 7.4, 8.1 and 8.3.

[Unreleased]: https://github.com/lucasthobias/wc-brazil-checkout-essentials/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/lucasthobias/wc-brazil-checkout-essentials/releases/tag/v1.0.0
