define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Ls_Omni/js/model/discount-validator'
    ],
    function (Component, additionalValidators, discountValidator) {
        'use strict';
        additionalValidators.registerValidator(discountValidator);
        return Component.extend({});
    }
);
