define([
    'Magento_Checkout/js/model/quote',
    'jquery',
    'mage/translate'
], function (quote, $, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                if (quote.shippingMethod().carrier_code == 'clickandcollect' && $('#pickup-store').val() == '') {
                    this.errorValidationMessage($t('Please provide where (if suitable) you prefer to pick your order.'));
                    return false;
                }
                return this._super();
            }
        });
    }
});
