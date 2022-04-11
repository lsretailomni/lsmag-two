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
                let isEnabledTimeSlots = window.checkoutConfig.shipping.pickup_date_timeslots.enabled;

                if (quote.shippingMethod().carrier_code == 'clickandcollect' && $('#pickup-store').val() == '') {
                    if (stores.totalRecords === 0) {
                        this.errorValidationMessage($t('We are afraid click and collect is not available for this order.'));
                    } else {
                        this.errorValidationMessage($t('Please provide where (if suitable) you prefer to pick your order.'));
                    }

                    return false;
                }
                if (isEnabledTimeSlots && ($("[name='pickup-date']").val() === '')) {
                    this.errorValidationMessage($t('Please select delivery date for your order.'));
                    return false;
                }
                return this._super();
            }
        });
    }
});
