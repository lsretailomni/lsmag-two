define([
    'Magento_Checkout/js/model/quote',
    'jquery',
    'mage/translate',
], function (quote, $, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                if (quote.shippingMethod().carrier_code == 'clickandcollect') {
                    var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
                    // if ($('#pickup-date').val() == '' || (stores.totalRecords > 1 && $('#pickup-store').val() == '')) {
                    if (stores.totalRecords > 1 && $('#pickup-store').val() == '') {
                        this.errorValidationMessage($t('Please provide where (if suitable) you prefer to pick your order.'));
                        $('.message.notice').css("clear", "both");
                        return false;
                    }
                }
                return this._super();
            }
        });
    }
});