define(['jquery', 'ko', 'Magento_Checkout/js/model/quote', 'mage/translate'], function ($, ko, quote, $t) {
    "use strict";

    var pattern,
        balance = 0,
        rateLabel;

    var extensionAttributes = quote.getTotals()().extension_attributes;
    pattern = {single: "{point} " + $t("point"), plural: "{point} " + $t("points")};
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

