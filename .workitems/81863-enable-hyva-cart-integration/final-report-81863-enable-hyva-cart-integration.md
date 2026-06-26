# Final Report — ADO #81863: Enable LS-integrated cart behavior in Hyvä

**Status**: COMPLETE  
**Date**: 2026-06-24  
**Module**: Ls_Omni  
**Constraint**: All Luma files are untouched. Hyvä-specific code is isolated under `hyva_*` layout handles and `cart/hyva/` template paths.

---

## Deliverables

### New Files

| File | Purpose |
|---|---|
| `src/Omni/view/frontend/layout/hyva_checkout_cart_index.xml` | Layout: removes Luma loyalty/giftcard blocks, overrides coupon template, adds Hyvä loyalty+giftcard+totals blocks |
| `src/Omni/view/frontend/layout/hyva_default.xml` (appended) | Layout: overrides mini-cart drawer globally on Hyvä |
| `src/Omni/Controller/Ajax/HyvaCoupons.php` | GET controller for Hyvä coupon recommendations; uses `Block\Cart\Coupons` and `cart/hyva/coupons-listing.phtml` |
| `src/Omni/view/frontend/templates/cart/hyva/coupons.phtml` | Alpine `initCouponForm` — discount code form + lazy-loaded LS coupon recommendations |
| `src/Omni/view/frontend/templates/cart/hyva/coupons-listing.phtml` | Server-rendered coupon card list; clicking "Apply" dispatches `apply-coupon-code` window event |
| `src/Omni/view/frontend/templates/cart/hyva/loyalty-points.phtml` | Alpine `initLsLoyaltyPoints` — redeem/cancel points via `hyva.postCart()`, sparkles icon |
| `src/Omni/view/frontend/templates/cart/hyva/gift-card.phtml` | Alpine `initLsGiftcard` — apply/remove gift card via `hyva.postCart()`, sparkles icon |
| `src/Omni/view/frontend/templates/cart/hyva/item/default.phtml` | Cart item renderer: LS strike-through pricing, "Save X" badge, falls back to Magento for non-LS items |
| `src/Omni/view/frontend/templates/cart/hyva/cart-drawer.phtml` | Mini-cart drawer: LS `lsPriceOriginal` strike-through and `lsDiscountAmount` badge in price column |
| `src/Omni/view/frontend/templates/cart/hyva/totals/ls-discount.phtml` | Totals segment for `ls_discount_amount` |
| `src/Omni/view/frontend/templates/cart/hyva/totals/loyalty-discount.phtml` | Totals segment for `ls_points_discount` |
| `src/Omni/view/frontend/templates/cart/hyva/totals/giftcard-discount.phtml` | Totals segment for `ls_gift_card_amount_used` |
| `src/Omni/view/frontend/templates/cart/hyva/totals/js/ls-discount-js.phtml` | Alpine.data registration for `initLsDiscountSegment` |
| `src/Omni/view/frontend/templates/cart/hyva/totals/js/loyalty-discount-js.phtml` | Alpine.data registration for `initLoyaltyDiscountSegment` |
| `src/Omni/view/frontend/templates/cart/hyva/totals/js/giftcard-discount-js.phtml` | Alpine.data registration for `initGiftcardDiscountSegment` |

### No PHP model changes
All observers, plugins, blocks, and ViewModels were already theme-agnostic. Only `HyvaCoupons.php` is new PHP — it is a thin GET controller (mirrors `HyvaProactiveDiscountsAndCoupons` structure) that routes to the correct block and Hyvä template.

---

## Requirements Coverage

| # | Requirement | Status |
|---|---|---|
| 1 | Strike-through discount price on cart items | ✅ `item/default.phtml` + `CartViewModel` |
| 2 | "Save X" discount badge on cart items | ✅ `item/default.phtml` |
| 3 | LS coupon recommendations on cart page | ✅ `coupons.phtml` + `HyvaCoupons` controller + `coupons-listing.phtml` |
| 4 | Loyalty points redemption form on cart page | ✅ `loyalty-points.phtml` |
| 5 | Gift card application form on cart page | ✅ `gift-card.phtml` |
| 6 | LS order totals (discount, points, giftcard) on cart | ✅ Three totals segment phtmls + JS registrations |
| 7 | Strike-through discount price in mini-cart drawer | ✅ `cart-drawer.phtml` + CustomerData plugin (pre-existing) |
| 8 | Luma files unchanged | ✅ All changes are in `hyva_*` layout handles and `cart/hyva/` paths |

---

## Key Design Decisions

### Controller split: `HyvaCoupons` vs `HyvaProactiveDiscountsAndCoupons`
The existing `HyvaProactiveDiscountsAndCoupons` controller is for **product-page proactive discounts** (uses `Block\Product\View\Discount\Proactive`). Cart coupon recommendations require `Block\Cart\Coupons` with its `getAvailableCoupons()` method. A dedicated `HyvaCoupons` GET controller was created to avoid contaminating the product-page controller with cart context.

### Coupon recommendations flow
1. `coupons.phtml` mounts `initLsCouponRecommendations` Alpine component, fetches `omni/ajax/HyvaCoupons` on init
2. `HyvaCoupons::execute()` creates `Block\Cart\Coupons`, renders `coupons-listing.phtml`, returns `{output: html}`
3. `coupons-listing.phtml` renders clickable coupon cards using `$block->getFormattedDescription()`
4. Clicking "Apply" dispatches `window.CustomEvent('apply-coupon-code', {detail: {code}})` (inline onclick — safe in `x-html` context)
5. `coupons.phtml`'s `@apply-coupon-code.window="applyRecommendedCode($event.detail.code)"` fills the coupon input and auto-submits via `hyva.postCart()`

### Alpine method call fix (code review finding)
All three totals segment templates had `x-if="isXSegment"` (function reference, always truthy) and `x-text="formatXValue"` (renders function source code). Fixed to `x-if="isXSegment()"` and `x-text="formatXValue()"` in `ls-discount.phtml`, `loyalty-discount.phtml`, and `giftcard-discount.phtml`.

### Sparkles icon consistency
All three discount/loyalty sections (`coupons.phtml`, `loyalty-points.phtml`, `gift-card.phtml`) use `LucideIcons::sparklesHtml('text-primary', 20, 20, ['aria-hidden' => 'true'])`. The button/summary chevron in loyalty and gift card uses an inline SVG (not `chevronDownHtml`) to allow the Alpine `:class="open ? 'rotate-180' : ''"` binding.

---

## Testing Checklist

- [ ] Cart page — add item, verify LS discount price + "Save X" badge render correctly
- [ ] Cart page — verify three LS totals rows appear (when applicable): LS Discount, Loyalty Discount, Gift Card
- [ ] Cart page coupon form — enter valid LS coupon code and verify `omni/cart/couponPost` applies it
- [ ] Cart page coupon recommendations — verify cards load, clicking "Apply" fills and submits form
- [ ] Cart page loyalty points — verify redeem/cancel flow via `omni/cart/RedeemPoints`
- [ ] Cart page gift card — verify apply/remove flow via `omni/cart/GiftCardUsed`
- [ ] Mini-cart drawer — add item with LS discount, verify strike-through and badge in drawer
- [ ] Luma cart page — verify no regression (no Hyvä blocks appear on Luma theme)
- [ ] Non-LS item — verify cart item renderer falls back to standard Magento price rendering
- [ ] Guest / non-logged-in — verify coupon recommendations section is not shown (Block guards this)