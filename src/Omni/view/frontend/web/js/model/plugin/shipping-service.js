define([
    'underscore',
    'mage/utils/wrapper',
    'jquery'
],function (_, wrapper, $) {
    'use strict';
    return function (ShippingService) {
        var setShippingRates = wrapper.wrap(
            ShippingService.setShippingRates,
            function (originalSetShippingRates, ratesData) {
                var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
                if (stores.totalRecords === 0) {
                    ratesData = $.grep(ratesData, function (el) {
                        return el.carrier_code !== 'clickandcollect';

                    });
                }
                return originalSetShippingRates(ratesData);
            }
        );

        return _.extend(ShippingService, {
            setShippingRates: setShippingRates
        });
    };
});
