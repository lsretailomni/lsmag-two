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
                        return true;
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
