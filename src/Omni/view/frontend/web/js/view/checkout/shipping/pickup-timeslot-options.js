define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
], function ($, ko, select, quote, $t) {
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
                    if (!self.selectedShippingMethod || (self.selectedShippingMethod && self.selectedShippingMethod['carrier_code'] != method['carrier_code'])) {
                        self.selectedShippingMethod = method;
                        self.updateDropdownValues([{'value': '', 'label': $t('Please select time')}]);
                    }
                }
            }, null, 'change');
        },
        updateDropdownValues: function (values) {
            this.setOptions(values);
        }
    });
});
