define([
    'Ls_Omni/js/mixin/discount-validator-place-order-mixin'
], function (discountValidatorMixin) {
    'use strict';

    return function (placeOrderAction) {
        // Check if LS is enabled and discount validation is enabled
        if (window.checkoutConfig &&
            window.checkoutConfig.ls_enabled &&
            window.checkoutConfig.ls_discount_validator) {
            // Apply the discount validator mixin
            return discountValidatorMixin(placeOrderAction);
        }

        // Return original place order action without modification
        return placeOrderAction;
    };
});

