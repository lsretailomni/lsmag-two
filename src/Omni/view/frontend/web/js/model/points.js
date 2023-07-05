define(['jquery', 'ko', 'Magento_Checkout/js/model/quote'], function ($, ko, quote) {
    "use strict";

    var pattern,
        balance = 0,
        rateLabel;

    var extensionAttributes = quote.getTotals()().extension_attributes;
    pattern = {single: "{point} loyalty point", plural: "{point} loyalty points"};
    if (extensionAttributes && extensionAttributes.loyalty_points) {
        balance = extensionAttributes.loyalty_points.balance;
        rateLabel = extensionAttributes.loyalty_points.rateLabel;
    }

    return {
        pattern: pattern,
        balance: balance,
        rateLabel: rateLabel,
        isCheckoutCart: $('body').hasClass('checkout-cart-index'),

        format: function (point) {
            if (parseInt(point) > 1) {
                return pattern.plural.replace('{point}', point);
            }

            return pattern.single.replace('{point}', point);
        }
    }
});

