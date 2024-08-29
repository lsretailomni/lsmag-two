define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function ($, Component, ko, quote, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/checkout/shipping/pickup-date-time-block'
        },
        initObservable: function () {
            let self = this;
            this._super();
            this.showAdditionalOption = ko.computed(function () {
                let method = quote.shippingMethod();

                if (method && method['carrier_code'] !== undefined) {
                    if (window.checkoutConfig.shipping.pickup_date_timeslots.store_type === 1) {
                        if (method['carrier_code'] === 'clickandcollect') {
                            if (window.checkoutConfig.shipping.pickup_date_timeslots.enabled === "1") {
                                self.updateLabels();
                                return true;
                            }
                        } else {
                            if (window.checkoutConfig.shipping.pickup_date_timeslots.delivery_hours_enabled === "1") {
                                self.updateLabels();
                                return true;
                            }
                        }
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
            return window.checkoutConfig.shipping.pickup_date_timeslots.delivery_hours_enabled === "1";
        },
        updateLabels: function () {
            let interval= setInterval(function () {
                let dateSelector = $('.ls-pickup-additional-options-wrapper div[name="shippingOptionSelect.pickup-date"] .label span'),
                    timeSelector = $('.ls-pickup-additional-options-wrapper div[name="shippingOptionSelect.pickup-timeslot"] .label span');

                if (dateSelector.length && timeSelector.length) {
                    let method = quote.shippingMethod();

                    if (method && method['carrier_code'] !== undefined) {
                        if (window.checkoutConfig.shipping.pickup_date_timeslots.store_type === 1) {
                            if (method['carrier_code'] !== 'clickandcollect') {
                                dateSelector.text(
                                    $t('Requested Delivery Date')
                                );
                                timeSelector.text(
                                    $t('Requested Delivery Time')
                                );
                            } else {
                                dateSelector.text(
                                    $t('Pick up Date')
                                );
                                timeSelector.text(
                                    $t('Pick up Time')
                                );
                            }
                        }
                    }

                    clearInterval(interval);
                }
            }, 100);
        }
    });
});
