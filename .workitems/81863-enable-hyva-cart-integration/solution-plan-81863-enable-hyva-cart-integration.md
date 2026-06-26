# Solution Plan: Enable LS-integrated cart behavior in Hyvä (ADO #81863)

## Approach summary

Add Hyvä-only layout handles (`hyva_checkout_cart_index.xml`, `hyva_default.xml`) and Alpine.js/PHP-rendered templates under `view/frontend/templates/cart/hyva/` that consume the already-complete LS data layer (CustomerData plugin, totals collectors, blocks, ViewModels, AJAX controller). No PHP, no DI, no Luma file changes. All work is in the `Ls_Omni` module under `src/artifacts/lsretail/lsmag-two/src/Omni/`.

This mirrors the precedent already set by the product-page Hyvä work: `hyva_catalog_product_view.xml` removes the Luma blocks and re-adds Hyvä equivalents pointing at `product/hyva/*.phtml` (Alpine.js). We replicate that pattern for the cart.

---

## Evidence from the codebase

All paths below are relative to the module root `src/artifacts/lsretail/lsmag-two/src/Omni/` unless noted.

### Data layer is complete and theme-agnostic (no changes)
- **Mini-cart item data**: `Plugin/Checkout/CustomerData/Cart.php::afterGetSectionData()` sets, per item: `product_price` (rendered discounted-price HTML, lines 85), `lsPriceOriginal` (formatted original price string, lines 81-82), `lsDiscountAmount` (`"(Save X)"` string, lines 83-84). When LSR is disabled both extra fields are set to `""` (lines 92-99).
- **Totals segment codes** (the `code` keys that land in `window.checkoutConfig.totalsData.total_segments`), from `etc/sales.xml`:
  - `ls_discount_amount` → `Model/Total/Quote/LsDiscount.php` (title "Discount")
  - `ls_points_discount` → `Model/Total/Quote/PointsDiscount.php` (title "Loyalty Points Redeemed")
  - `ls_gift_card_amount_used` → `Model/Total/Quote/GiftCardAmountUsed.php` (title "Gift Card Redeemed")
  - (also present but not required by this story: `ls_points_spent`, `ls_points_earn`, `ls_gift_card_no`, `ls_gift_card_pin`)
- **Loyalty rate/balance in REST totals**: `Plugin/Quote/CartTotalRepository.php::aroundGet()` adds `loyalty_points => ['rateLabel', 'balance']` as a totals extension attribute. Not needed for the cart-page phtml (the `LoyaltyPoints` block already exposes balance/rate directly), but confirms the API path is live.
- **Blocks usable from phtml**: `Block/Cart/LoyaltyPoints.php` (`getMemberPoints`, `getPointsRate`, `getLsPointsSpent`, `getFormattedPrice`, `formatValue`), `Block/Cart/Giftcard.php` (`getGiftCardActive`, `getGiftCardNo`, `getGiftCardPin`, `getGiftCardAmountUsed`, `isPinCodeFieldEnable`), `Block/Cart/Coupons.php` (`isCouponEnabled`, `getAjaxUrl` → `omni/ajax/coupons`).
- **ViewModels**: `ViewModel/CartViewModel.php` (`getItemRowTotal`, `getItemRowDiscount`, `getItemPriceIncludeCustomOptions`, `getOneListCalculateData`, `getPriceCurrency`), `ViewModel/CouponsViewModel.php` (`isCouponsEnabled`, `isModuleEnabled`).
- **Hyvä coupon AJAX controller already exists**: `Controller/Ajax/HyvaProactiveDiscountsAndCoupons.php` renders `Ls_Omni::product/hyva/view/proactive.phtml` (Alpine `lsDiscountDropdown`) at route `omni/ajax/HyvaProactiveDiscountsAndCoupons`.

### Luma layout/templates that must stay untouched
- `view/frontend/layout/checkout_cart_index.xml` (jsLayout KO components, coupon override, loyalty/giftcard blocks).
- `view/frontend/layout/checkout_cart_item_renderers.xml`, `view/frontend/layout/checkout_cart_sidebar_item_renderers.xml`.
- `view/frontend/templates/cart/coupons.phtml`, `cart/loyalty-points.phtml`, `cart/gift-card.phtml`, `cart/item/default.phtml`, `cart/coupons-loader.phtml`.

### Hyvä theme structures we hook into (vendor, read-only — model targets)
- `vendor/hyva-themes/magento2-default-theme-csp/Magento_Checkout/layout/checkout_cart_index.xml`:
  - `cart.discount` container holds `checkout.cart.coupon` (block class `Magento\Checkout\Block\Cart\Coupon`, template `Magento_Checkout::php-cart/coupon.phtml`) and `checkout.cart.shipping`.
  - `checkout.cart.totals` (block class `Magento\Checkout\Block\Cart\Totals`, template `Magento_Checkout::php-cart/totals.phtml`) holds totals child blocks (`discount`, `grand_total`, `shipping`, `subtotal`) and a sibling `checkout.cart.totals.scripts` container holding the matching `*-js.phtml` blocks.
  - Cart item renderer block names: `checkout.cart.item.renderers.default` / `.simple` / `.configurable` / `.bundle` (via `checkout.cart.item.renderers` RendererList), template `Magento_Checkout::php-cart/item/default.phtml`.
- `vendor/.../Magento_Checkout/templates/php-cart/totals.phtml`: Alpine `initCartTotals` reads `window.checkoutConfig.totalsData.total_segments` and loops `<template x-for="(segment, index) in getSortedSegments">`; each child renders inside that scope where `segment` is in scope.
- `vendor/.../Magento_Tax/templates/php-cart/totals/discount.phtml` + `.../totals/js/discount-js.phtml`: the canonical totals-segment pattern — a `<template x-if="isSegment">` div plus a companion JS block defining an `Alpine.data('initXSegment', ...)` whose `isXSegment()` checks `this.segment.code === 'discount'` and `formatValue()` returns `hyva.formatPrice(this.segment.value)`. **This is the exact pattern our three LS totals blocks must follow.**
- `vendor/.../Magento_Theme/layout/default.xml` line 58: mini-cart drawer block is `cart-drawer`, template `Magento_Theme::html/cart/cart-drawer.phtml`, guarded by `ifconfig="checkout/sidebar/display"`.
- `vendor/.../Magento_Theme/templates/html/cart/cart-drawer.phtml`: Alpine `initCartDrawer`; item loop is `<template x-for="item in cartItems">`; price shown at line 323 as `<span class="price-box" x-html="item.product_price"></span>`. `item.lsPriceOriginal` and `item.lsDiscountAmount` are present in the section data but not yet rendered.
- `vendor/.../Magento_Checkout/templates/php-cart/coupon.phtml`: Hyvä coupon form uses Alpine `initCouponForm` and submits via `hyva.postCart($form)` to `checkout/cart/couponPost`. Our Hyvä coupon template models on this.

### Established Hyvä conventions in this module (precedent)
- `view/frontend/layout/hyva_catalog_product_view.xml`: `<referenceBlock ... remove="true"/>` for the Luma blocks, then re-add Hyvä blocks pointing at `Ls_Omni::product/hyva/...phtml`. **Use this remove-then-readd pattern for any block whose Luma template would otherwise apply.**
- `view/frontend/layout/hyva_default.xml`: already adds a footer block on the global handle — extend this file for the cart-drawer override.
- Alpine.js template style: `view/frontend/templates/product/hyva/view/availability.phtml` and `product/hyva/view/proactive.phtml` (`x-data`, `@click.prevent`, `x-show`, `x-cloak`, `x-html`, `document.addEventListener('alpine:init', ...)`, Tailwind utility classes).
- Tailwind styling pipeline: `view/frontend/tailwind/module.css` is compiled into the theme `styles.css` via `Observer/RegisterModuleForHyvaConfig.php` (bound in `etc/frontend/events.xml`). New Tailwind classes used in the new templates are picked up automatically by the `@source` scan; no manual config. See `README-HYVA.md`.

---

## DI changes

**None.** Confirmed:
- No new classes, interfaces, plugins, observers, or preferences.
- All blocks (`Ls\Omni\Block\Cart\*`) and ViewModels (`Ls\Omni\ViewModel\*`) already exist and are referenced from layout via `class=`/`<argument xsi:type="object">`, which Magento auto-wires through DI.
- The Hyvä coupon AJAX controller `HyvaProactiveDiscountsAndCoupons` already exists and is routed.
- No `di.xml`, `sales.xml`, `events.xml`, or `webapi` edits.

---

## Files to create / modify

### Layout (new files)

#### 1. `view/frontend/layout/hyva_checkout_cart_index.xml` (NEW)
Hyvä-only cart-page handle. Mirrors the role of the Luma `checkout_cart_index.xml` but targets Hyvä block names. Contains:

a. **Coupon override** — re-template the existing coupon block with the LS Hyvä coupon template and inject the ViewModel:
```
<referenceBlock name="checkout.cart.coupon" template="Ls_Omni::cart/hyva/coupons.phtml">
    <arguments>
        <argument name="view_model" xsi:type="object">Ls\Omni\ViewModel\CouponsViewModel</argument>
    </arguments>
</referenceBlock>
```
(The block class stays `Magento\Checkout\Block\Cart\Coupon`, which is what the Hyvä template currently uses; the new template falls back to the stock Hyvä coupon markup when LS coupons are disabled — see template note 4.)

b. **Loyalty points + gift card blocks** added to `cart.discount` (same container the Hyvä coupon lives in), guarded by the same `ifconfig` flags as Luma:
```
<referenceContainer name="cart.discount">
    <block class="Ls\Omni\Block\Cart\LoyaltyPoints" name="checkout.cart.loyaltypoints.hyva"
           template="Ls_Omni::cart/hyva/loyalty-points.phtml" ifconfig="ls_mag/ls_loyaltypoints/cart"/>
    <block class="Ls\Omni\Block\Cart\Giftcard" name="checkout.cart.giftcard.hyva"
           template="Ls_Omni::cart/hyva/gift-card.phtml" ifconfig="ls_mag/ls_giftcard/cart"/>
</referenceContainer>
```
Note distinct block names (`.hyva` suffix) so they never collide with the Luma block names if both handles were ever loaded.

c. **LS totals child blocks** added to `checkout.cart.totals`, plus their companion JS blocks in `checkout.cart.totals.scripts` (this is mandatory — the segment renderer needs its `Alpine.data` registered, exactly as `discount` + `discount.js` are paired in the Hyvä Tax layout):
```
<referenceBlock name="checkout.cart.totals">
    <block name="ls_discount" template="Ls_Omni::cart/hyva/totals/ls-discount.phtml"/>
    <block name="ls_points_discount" template="Ls_Omni::cart/hyva/totals/loyalty-discount.phtml"/>
    <block name="ls_gift_card_amount_used" template="Ls_Omni::cart/hyva/totals/giftcard-discount.phtml"/>
</referenceBlock>
<referenceContainer name="checkout.cart.totals.scripts">
    <block name="ls_discount.js" template="Ls_Omni::cart/hyva/totals/js/ls-discount-js.phtml"/>
    <block name="ls_points_discount.js" template="Ls_Omni::cart/hyva/totals/js/loyalty-discount-js.phtml"/>
    <block name="ls_gift_card_amount_used.js" template="Ls_Omni::cart/hyva/totals/js/giftcard-discount-js.phtml"/>
</referenceContainer>
```

d. **Cart item renderer override** — re-template the four renderer block names to the LS Hyvä item template with the CartViewModel injected (mirror of Luma `checkout_cart_item_renderers.xml`):
```
<referenceBlock name="checkout.cart.item.renderers.default"    template="Ls_Omni::cart/hyva/item/default.phtml">
    <arguments><argument name="view_model" xsi:type="object">Ls\Omni\ViewModel\CartViewModel</argument></arguments>
</referenceBlock>
... repeat for .simple, .configurable, .bundle ...
```

#### 2. `view/frontend/layout/hyva_default.xml` (MODIFY — append to existing file)
Add the mini-cart drawer override on the global Hyvä handle (the existing file already adds the footer block — append, do not replace):
```
<referenceBlock name="cart-drawer" template="Ls_Omni::cart/hyva/cart-drawer.phtml"/>
```
Keep the `ifconfig="checkout/sidebar/display"` behavior inherited from the vendor `default.xml` (re-templating a block does not drop its `ifconfig`).

### Templates (new files, under `view/frontend/templates/cart/hyva/`)

#### 3. `cart/hyva/coupons.phtml` (NEW)
Models on `vendor/.../Magento_Checkout/templates/php-cart/coupon.phtml` (Alpine `initCouponForm` + `hyva.postCart`) and the Luma `cart/coupons.phtml` logic.
- Receives `view_model` (`CouponsViewModel`) via `$block->getData('view_model')` and `$block` is the `Magento\Checkout\Block\Cart\Coupon`.
- If `$viewModel->isCouponsEnabled() === "1"`: render the Hyvä `<details>` coupon form (apply/cancel to `checkout/cart/couponPost` via `hyva.postCart`, same as the vendor template) **plus** a coupon-recommendations region that lazy-loads from the existing Hyvä AJAX controller.
  - Recommendations: render a container with `x-data` that, on `init`, `fetch`es `omni/ajax/HyvaProactiveDiscountsAndCoupons` (GET, `X-Requested-With: XMLHttpRequest`) and injects `data.output` via `x-html` — the controller returns the Alpine `lsDiscountDropdown` markup from `product/hyva/view/proactive.phtml`. This replaces the Luma `coupons-loader.phtml` + `Ls_Omni/js/view/checkout/cart/coupons` RequireJS path. Selecting a recommended code fills the coupon input (Alpine binding, replacing the jQuery `#my_radio_box` handler in the Luma template).
- Else if `!$viewModel->isModuleEnabled()`: fall back to the stock Hyvä coupon markup by `echo`ing `$block->getBlockHtml(...)` / re-rendering the vendor template, so non-LS stores keep the default Hyvä coupon form (parallels the Luma `elseif` branch that renders `Magento_Checkout::cart/coupon.phtml`).

#### 4. `cart/hyva/loyalty-points.phtml` (NEW)
Models on Luma `cart/loyalty-points.phtml` + Alpine pattern from `product/hyva/view/availability.phtml`.
- `$block` is `Ls\Omni\Block\Cart\LoyaltyPoints`. Guard: only render when `(int)$block->getMemberPoints() > 0 && $block->getPointsRate() > 0` (identical condition to Luma).
- Collapsible `<details>`/Alpine section "Apply Loyalty Points". The `<summary>` label uses `LucideIcons` (via `$viewModels->require(LucideIcons::class)`) with `$lucideIcons->sparklesHtml('text-primary', 20, 20, ['aria-hidden' => 'true'])` before the label text, and `$lucideIcons->chevronDownHtml('transform group-open:rotate-180', 20, 20, ['aria-hidden' => 'true'])` on the right — identical to the Hyvä coupon template summary pattern.
- Shows balance, exchange rate (`$block->formatValue($pointRate)`), and `$block->getFormattedPrice(1)`.
- Number input `loyalty_points` pre-filled with `$block->getLsPointsSpent()`; hidden `remove-points`; form `action="omni/cart/RedeemPoints"` `method="post"` submitted via `hyva.postCart($form)` (replacing the Luma `data-mage-init="loyaltyPoints"` widget). Apply/Update/Cancel buttons follow the Luma empty-vs-set logic. Include `<?= $block->getBlockHtml('formkey') ?>`.

#### 5. `cart/hyva/gift-card.phtml` (NEW)
Models on Luma `cart/gift-card.phtml`.
- `$block` is `Ls\Omni\Block\Cart\Giftcard`. Guard: `$block->getGiftCardActive()`.
- Collapsible Alpine "Apply Gift Card" section. The `<summary>` label uses `$lucideIcons->sparklesHtml('text-primary', 20, 20, ['aria-hidden' => 'true'])` before the label and `$lucideIcons->chevronDownHtml('transform group-open:rotate-180', 20, 20, ['aria-hidden' => 'true'])` on the right — same pattern as loyalty points and the coupon template.
- Fields: `giftcardno`, optional `giftcardpin` (when `$block->isPinCodeFieldEnable()`), `giftcardamount`; hidden `removegiftcard`. Form `action="omni/cart/GiftCardUsed"` posted via `hyva.postCart`. Apply/Update/Cancel per Luma logic, pre-filling from `getGiftCardNo()`, `getGiftCardPin()`, `getGiftCardAmountUsed()`. Include formkey.

#### 6. `cart/hyva/item/default.phtml` (NEW)
Models on Hyvä `vendor/.../php-cart/item/default.phtml` (structure) + Luma `cart/item/default.phtml` (LS price logic).
- Copy the Hyvä vendor item layout (image, name, qty input, actions via `$block->getActions($item)`).
- Replace the price area: receive `view_model` (`CartViewModel`), compute `$basketData = $viewModel->getOneListCalculateData($item)`, `$item->setDiscountAmount($viewModel->getItemRowDiscount($item))`, `$item->setRowTotalInclTax($viewModel->getItemRowTotal($item))`.
  - When `$basketData` is non-empty: show row total `$priceCurrency->format($viewModel->getItemRowTotal($item), true)`; if `$item->getDiscountAmount() > 0` show `<s>`/`line-through` original price (`$viewModel->getItemPriceIncludeCustomOptions($item)`) and a "Save X" badge (`'(' . __($basketData[1]) . ' ' . $priceCurrency->format($item->getDiscountAmount()) . ')'`), styled with Tailwind (e.g. `line-through text-fg-secondary`, badge `text-sm text-green-700`).
  - Else fall back to `$block->getUnitPriceHtml($item)` / `$block->getRowTotalHtml($item)` (stock Hyvä behavior for non-LS items).
- Also render the offers/promotions line `$basketData[0]` near the product name (matching Luma line 76-78).

#### 7. `cart/hyva/totals/ls-discount.phtml` (NEW)
Models on `vendor/.../Magento_Tax/templates/php-cart/totals/discount.phtml`.
```
<div x-data="initLsDiscountSegment">
  <template x-if="isLsDiscountSegment" data-total-code="ls_discount_amount">
    <div :class="wrapperClass">
      <div x-text="formatTotalLabel"></div>
      <div x-text="formatLsDiscountValue" class="justify-self-end"></div>
    </div>
  </template>
</div>
```

#### 8. `cart/hyva/totals/js/ls-discount-js.phtml` (NEW)
Models on `vendor/.../Magento_Tax/templates/php-cart/totals/js/discount-js.phtml`.
```
<script>
function initLsDiscountSegment() {
  return {
    isLsDiscountSegment() { return this.segment.code === 'ls_discount_amount'; },
    formatLsDiscountValue() { return hyva.formatPrice(this.segment.value); }
  };
}
window.addEventListener('alpine:init', () => Alpine.data('initLsDiscountSegment', initLsDiscountSegment), {once: true})
</script>
<?php $hyvaCsp->registerInlineScript() ?>
```
(Requires the `HyvaCsp` viewmodel header like the vendor file. Reuse the vendor file's exact `use`/`@var` preamble.)

#### 9. `cart/hyva/totals/loyalty-discount.phtml` (NEW) + 10. `cart/hyva/totals/js/loyalty-discount-js.phtml` (NEW)
Same pattern, segment code `ls_points_discount`, Alpine name `initLoyaltyDiscountSegment`.

#### 11. `cart/hyva/totals/giftcard-discount.phtml` (NEW) + 12. `cart/hyva/totals/js/giftcard-discount-js.phtml` (NEW)
Same pattern, segment code `ls_gift_card_amount_used`, Alpine name `initGiftcardDiscountSegment`.

> The segment `title` ("Discount" / "Loyalty Points Redeemed" / "Gift Card Redeemed") comes from the totals collector via `this.segment.title`, surfaced by `formatTotalLabel` (already defined in `initCartTotals`). No translations need to be re-declared in the JS blocks.

#### 13. `cart/hyva/cart-drawer.phtml` (NEW)
Copy of `vendor/.../Magento_Theme/templates/html/cart/cart-drawer.phtml` with one targeted change to the per-item price area (around vendor line 323). Replace:
```
<span class="price-box" x-html="item.product_price"></span>
```
with a block that keeps the discounted price and adds the strike-through original + "Save X" label, all from section data already present:
```
<span class="price-box flex flex-col items-end">
    <span x-html="item.product_price"></span>
    <template x-if="item.lsPriceOriginal">
        <s class="text-sm text-fg-secondary" x-html="item.lsPriceOriginal"></s>
    </template>
    <template x-if="item.lsDiscountAmount">
        <span class="text-xs text-green-700" x-html="item.lsDiscountAmount"></span>
    </template>
</span>
```
Everything else in the file is copied verbatim (it is a full template override, like the Luma module overrides core templates via layout). No JS changes needed — `lsPriceOriginal`/`lsDiscountAmount` are already on each `item` object from the CustomerData plugin.

### Styling
- If any of the new templates introduce Tailwind classes not already scanned, they are auto-compiled because the templates live under the module path that `RegisterModuleForHyvaConfig` registers as a Tailwind `@source`. Optionally add cart-specific overrides to the existing `view/frontend/tailwind/module.css` (MODIFY) if bespoke styling is required; prefer plain Tailwind utilities to avoid a CSS file change. Document the required `npm run build` step (already in `README-HYVA.md`).

---

## Implementation order

1. **Totals segments first** (files 7-12) + wire them in the totals section of `hyva_checkout_cart_index.xml` (3c). Self-contained, easiest to verify against the working vendor `discount` segment.
2. **Cart item renderer** (file 6) + layout 3d. Verifies LS price/discount rendering on the page.
3. **Loyalty points** (file 4) + layout 3b; then **gift card** (file 5) + layout 3b.
4. **Coupon + recommendations** (file 3) + layout 3a. Most complex (AJAX recommendations) — do after the simpler forms validate the `hyva.postCart` pattern.
5. **Mini-cart drawer** (file 13) + `hyva_default.xml` (file 2).
6. **Styling pass** (`module.css` if needed) + run the Hyvä Tailwind build.

---

## Test plan

### Existing integration tests — must still pass unchanged (regression guard)
- `Test/Integration/Plugin/Checkout/CustomerData/CartPluginTest.php` — asserts `lsPriceOriginal`, `lsDiscountAmount`, `product_price` keys exist (and are `""` when LSR disabled). The mini-cart template consumes exactly these keys; confirms the contract the drawer relies on.
- `Test/Integration/Plugin/Quote/CartTotalRepositoryTest.php::testAroundGet` — asserts `loyalty_points` totals extension attribute. Confirms the loyalty data path used by checkout/REST.
- These are PHP/data-layer tests; since this story changes **only** templates and layout, they should pass without modification. Run the full `Ls_Omni` integration suite to confirm no layout XML parse errors break block instantiation.

### New tests
- **No new PHP unit/integration tests are warranted**: there is no new PHP logic, and template/layout rendering is not unit-testable in a meaningful way here. Adding a layout-render integration test that instantiates `checkout.cart.totals` children under the `hyva_*` handle would require a Hyvä theme to be the active test theme, which the integration framework default theme is not — out of scope and low value.
- **Manual / QA acceptance checks** (map to acceptance criteria), to be run on a Hyvä storefront with LS Central connected:
  1. Add/remove/update cart item → prices recalc (basket sync via existing `CartObserver`).
  2. Cart page line item shows strike-through original + "Save X" when discounted (file 6).
  3. Coupon recommendations render and apply/cancel works (file 3 + `HyvaProactiveDiscountsAndCoupons`).
  4. Loyalty points section: balance/rate shown, apply/update/cancel works (file 4 → `omni/cart/RedeemPoints`).
  5. Gift card section: apply/update/cancel works (file 5 → `omni/cart/GiftCardUsed`).
  6. Totals show `ls_discount_amount`, `ls_points_discount`, `ls_gift_card_amount_used` segments (files 7-12).
  7. Mini-cart drawer shows discounted price + strike-through original + "Save X" (file 13).
  8. **Luma regression**: load a Luma store — cart page, mini-cart, coupon, loyalty, gift card, totals all behave exactly as before (no Luma file touched; new handles are `hyva_*`-prefixed so they never load on Luma).

### Config-flag verification
Each section must respect its existing guard (FR #7): loyalty `ls_mag/ls_loyaltypoints/cart`, gift card `ls_mag/ls_giftcard/cart` (both via layout `ifconfig`); coupon via `CouponsViewModel::isCouponsEnabled()` and `Block\Cart\Coupons::isCouponEnabled()`; totals self-suppress because the collectors only emit a segment when an amount exists. Verify each toggles correctly.

---

## Risk / impact assessment

**Changed surface**: layout XML on two new/extended Hyvä-only handles + new phtml templates. No shared PHP class, interface, plugin, observer, or DB schema is modified.

- **Luma**: zero risk — no Luma file changed; `hyva_*` handles do not load under Luma.
- **Hyvä core blocks re-templated** (`checkout.cart.coupon`, `cart-drawer`, `checkout.cart.item.renderers.*`): LOW — re-templating via `<referenceBlock template=...>` is the standard, upgrade-safe override; block classes unchanged so all block methods used by the new templates already exist.
- **Totals child blocks**: LOW — additive blocks following the vendor `discount` precedent exactly; if a segment is absent the `x-if` simply renders nothing.
- **Coupon AJAX**: LOW — reuses the already-shipped `HyvaProactiveDiscountsAndCoupons` controller; no new route.

**Risk level: LOW.**