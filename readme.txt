=== Brazil Checkout Essentials for WooCommerce ===
Contributors: lucascampos-dev
Tags: woocommerce, brazil, cpf, cnpj, checkout
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

CPF/CNPJ with check-digit validation, Brazilian CEP and phone validation, and minimum order amounts per state for WooCommerce.

== Description ==

* Person type selector (Pessoa Física / Pessoa Jurídica) with CPF or CNPJ field.
* Server-side check-digit validation, including the alphanumeric CNPJ format.
* CPF/CNPJ saved on the order, shown in the admin order screen and in emails.
* CEP and phone validation/formatting, with an optional CEP ↔ state check.
* Minimum order amount per Brazilian state (UF).
* HPOS compatible; Cart/Checkout Blocks aware (see the FAQ for limitations).
* No external API calls: customer data never leaves your store.

Developer documentation, hooks and source code: https://github.com/lucascampos-dev/wc-brazil-checkout-essentials

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin (WooCommerce must be active).
3. Configure it under WooCommerce → Settings → Brazil Checkout.

== Frequently Asked Questions ==

= Does it work with the Checkout block? =

Yes, with limitations: the block checkout shows a single "CPF or CNPJ" field (type detected automatically) without input masks, and the minimum per state is enforced through the Store API. The person type selector and CEP/phone checks are currently available on the classic checkout only.

= Is order data removed on uninstall? =

No. Plugin options are removed, but CPF/CNPJ stored on orders are kept because they are part of the store's fiscal records.

== Changelog ==

= 1.0.0 =
* Initial release.
