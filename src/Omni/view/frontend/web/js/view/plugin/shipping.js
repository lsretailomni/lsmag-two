define([
    'Magento_Checkout/js/model/quote',
    'jquery',
    'mage/translate'
], function (quote, $, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                if (!window.checkoutConfig.ls_enabled) {
                    return this._super();
                }

                let isEnabledTimeSlots = window.checkoutConfig.shipping.pickup_date_timeslots.delivery_hours_enabled,
                    storeType = window.checkoutConfig.shipping.pickup_date_timeslots.store_type;

                if (quote.shippingMethod().carrier_code === 'clickandcollect' && $('#pickup-store').val() === '') {
                    let stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);

                    if (stores.totalRecords === 0) {
                        this.errorValidationMessage($t('We are afraid click and collect is not available for this order.'));
                    } else {
                        this.errorValidationMessage($t('Please provide where (if suitable) you prefer to pick your order.'));
                    }

                    return false;
                }

                if (isEnabledTimeSlots === "1" &&
                    storeType === 0 &&
                    quote.shippingMethod().carrier_code !== 'clickandcollect' &&
                    ($("[name='pickup-date']").val() === '')
                ) {
                    this.errorValidationMessage($t('Please select delivery date for your order.'));
                    return false;
                }

                return this._super();
            }
        });
    }
});
