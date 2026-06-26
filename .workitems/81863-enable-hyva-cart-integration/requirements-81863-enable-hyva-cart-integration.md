# Requirements: Enable LS-integrated cart behavior in Hyvä

## Summary

ADO #81863. Cart interactions in LS Retail's Magento 2 integration (lsmag-two) are fully functional on the Luma theme but have no Hyvä-compatible rendering. Hyvä replaces RequireJS/KnockoutJS with Alpine.js and PHP-rendered templates (`php-cart/`), so all Luma jsLayout components and jQuery `data-mage-init` widgets are inert on Hyvä. This story delivers Hyvä-compatible rendering for: LS-calculated item prices (cart page + mini cart), loyalty points redemption, gift card application, coupon recommendations, and LS-sourced order totals — without touching existing Luma files.

## Scope

- **In scope**:
  - Hyvä cart page: LS item prices with strike-through discount, loyalty points form, gift card form, coupon recommendations (LS proactive coupons), LS totals (ls-discount, loyalty discount, gift card discount)
  - Hyvä mini cart (cart-drawer): item price as discounted price with strike-through original + "Save X" label
  - All layout/template changes under `hyva_checkout_cart_index.xml` or `hyva_default.xml` handles so they only activate on Hyvä

- **Out of scope**:
  - Checkout page (separate story)
  - Any Luma template or layout changes — must remain untouched
  - Backend/Admin changes
  - New PHP model/observer/plugin logic (the data layer is already fully functional)

## Functional Requirements

1. On the Hyvä cart page, each line item shows the LS-calculated row total; if a discount applies, the original price is shown struck-through with a "Save X" badge below it
2. On the Hyvä cart page, a collapsible "Apply Discount Code" section shows LS proactive coupon recommendations (radio-selectable) and allows applying/cancelling a coupon code
3. On the Hyvä cart page, a collapsible "Apply Loyalty Points" section shows the customer's available point balance, exchange rate, and a points input field; supports apply, update, and cancel
4. On the Hyvä cart page, a collapsible "Apply Gift Card" section supports gift card number, PIN (if configured), and amount inputs; supports apply, update, and cancel
5. On the Hyvä cart page, the order totals block shows LS-specific segments: line discount (ls_discount), loyalty points discount, and gift card discount amount
6. The Hyvä mini cart (cart-drawer) shows the LS discounted price per item; when a discount exists, the original price is struck-through and a "Save X" label appears
7. All sections listed above activate only when the relevant config flag is enabled (consistent with the existing `ifconfig` guards and ViewModel checks)
8. All four Luma cart blocks (loyalty-points.phtml, gift-card.phtml, coupons.phtml, cart/item/default.phtml) and their layout XML remain unchanged

## Technical Constraints

- Magento version: 2.4.7
- PHP version: 8.2
- Affected modules: `Ls_Omni`
- Hyvä pattern: `hyva_checkout_cart_index.xml` layout handle for cart page additions; `hyva_default.xml` for global/drawer additions
- The PHP model layer (observers, plugins, blocks, ViewModels) is complete and already theme-agnostic — no PHP changes expected
- The `Cart` CustomerData plugin already sets `product_price` (rendered HTML), `lsPriceOriginal` (original price string), and `lsDiscountAmount` ("Save X" string) in mini cart item data — templates only need to consume them
- Must not break: existing Luma cart flow, existing `checkout_cart_index.xml`, checkout flow, admin order management

## Acceptance Criteria

- [ ] On Hyvä: adding/removing/updating cart items triggers LS basket recalculation and updates prices
- [ ] On Hyvä cart page: line items show strike-through original price and "Save X" badge when LS discount applies
- [ ] On Hyvä cart page: coupon recommendations section renders with LS coupons and allows apply/cancel
- [ ] On Hyvä cart page: loyalty points section renders with balance/rate info and allows apply/update/cancel
- [ ] On Hyvä cart page: gift card section renders and allows apply/update/cancel
- [ ] On Hyvä cart page: totals reflect LS calculations (ls-discount, loyalty points discount, gift card)
- [ ] On Hyvä mini cart: item shows discounted price; if discounted, original price is struck-through with "Save X"
- [ ] On Luma: all existing cart tests pass and behavior is identical to before this change