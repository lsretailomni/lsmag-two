define([
    'Magento_Checkout/js/model/quote',
    'jquery',
    'mage/translate'
], function (quote, $, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);

                if (quote.shippingMethod().carrier_code == 'clickandcollect' && $('#pickup-store').val() == '') {
                    if (stores.totalRecords === 0) {
                        this.errorValidationMessage($t('We are afraid click and collect is not available for this order.'));
                    } else {
                        this.errorValidationMessage($t('Please provide where (if suitable) you prefer to pick your order.'));
                    }

                    return false;
                }
                return this._super();
            }
        });
    }
});
