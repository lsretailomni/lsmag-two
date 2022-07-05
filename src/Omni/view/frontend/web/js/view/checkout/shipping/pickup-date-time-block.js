define([
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote'

], function (Component, ko, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/checkout/shipping/pickup-date-time-block'
        },
        initObservable: function () {
            var self = this._super();
            this.showAdditionalOption = ko.computed(function () {
                var method = quote.shippingMethod();
                if (method && method['carrier_code'] !== undefined) {
                    if (method['carrier_code'] === 'clickandcollect' &&
                        window.checkoutConfig.shipping.pickup_date_timeslots.store_type === 1) {
                        return true;
                    }
                    if (window.checkoutConfig.shipping.pickup_date_timeslots.store_type === 0
                        && method['carrier_code'] !== 'clickandcollect'
                    ) {
                        return true;
                    }
                }
                return false;
            }, this);
            return this;
        },
        isDisplay: function () {
            return window.checkoutConfig.shipping.pickup_date_timeslots.enabled === "1";
        }
    });
});
