define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
], function ($, Component, ko, quote, $t) {
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
                        $("[name='shippingOptionSelect.pickup-timeslot']").show();
                        $("[name='shippingOptionSelect.pickup-date'] .label span").text($t('Pickup Date'));
                        return true;
                    }
                    if (window.checkoutConfig.shipping.pickup_date_timeslots.store_type === 0
                        && method['carrier_code'] !== 'clickandcollect'
                    ) {
                        return true;
                    }

                    if (method['carrier_code'] !== 'clickandcollect' &&
                        window.checkoutConfig.shipping.pickup_date_timeslots.store_type === 1
                    ) {
                        $("[name='shippingOptionSelect.pickup-timeslot']").hide();
                        $("[name='shippingOptionSelect.pickup-date'] .label span").text($t('Requested Delivery Date'));
                        return true;
                    }
                }
                return false;
            }, this);
            return this;
        },
        renderedHandler: function () {
            var method = quote.shippingMethod();
            if (method && method['carrier_code'] !== undefined) {
                if (method['carrier_code'] !== 'clickandcollect') {
                    $("[name='shippingOptionSelect.pickup-timeslot']").hide();
                    $("[name='shippingOptionSelect.pickup-date'] .label span").text($t('Requested Delivery Date'));
                }
            }
        },
        isDisplay: function () {
            return window.checkoutConfig.shipping.pickup_date_timeslots.enabled === "1";
        }
    });
});
