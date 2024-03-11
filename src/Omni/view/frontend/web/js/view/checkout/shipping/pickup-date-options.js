define([
    'jquery',
    'ko',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
], function ($, ko, uiRegistry, select, quote, $t) {
    'use strict';
    var self;
    return select.extend({
        initialize: function () {
            self = this;
            this._super();
            this.selectedShippingMethod = quote.shippingMethod();
            quote.shippingMethod.subscribe(function () {
                let method = quote.shippingMethod();
                if (method && method['carrier_code'] !== undefined) {
                    if (!self.selectedShippingMethod || (self.selectedShippingMethod && self.selectedShippingMethod['carrier_code'] !== method['carrier_code'])) {
                        self.selectedShippingMethod = method;
                        self.updateDropdownValues([{'value': '', 'label': $t('Please select date')}]);
                        if (method && method['carrier_code'] !== undefined) {
                            if (method['carrier_code'] !== 'clickandcollect') {
                                self.storeId = window.checkoutConfig.shipping.pickup_date_timeslots.current_web_store;
                                self.updateDropdownValues(self.getDateValues());
                            }
                        }
                    }
                }
            }, null, 'change');

            $('body').on('click', '.apply-store', function () {
                self.storeId = $(this).data('id');
                self.updateDropdownValues(self.getDateValues(true));
            });
        },
        updateDropdownValues: function (values) {
            this.setOptions(values);
        },
        getDateValues: function (isTakeAway = false) {
            var optionsArray = [], values = [];

            values = window.checkoutConfig.shipping.pickup_date_timeslots.delivery_hours;

            if (isTakeAway) {
                values = window.checkoutConfig.shipping.pickup_date_timeslots.options;
            }

            $.each(values, function (key, value) {
                if (key == self.storeId) {
                    $.each(value, function (index, v) {
                        optionsArray.push(
                            {
                                'value': index,
                                'label': index
                            });
                    });
                }
            });
            return optionsArray;
        },
        onUpdate: function (value) {
            var pickupTimSlot = $("[name='pickup-timeslot']");
            var values = window.checkoutConfig.shipping.pickup_date_timeslots.options;
            $.each(values, function (index, val) {
                pickupTimSlot.empty();
                var flag = false;
                $.each(val, function (i, v) {
                    if (i == value) {
                        $.each(v, function (index, value) {
                            pickupTimSlot.append(new Option(value, value));
                        });
                        flag = true;
                    }
                });

                if (flag) {
                    return false;
                }
            });
        },
    });
});
