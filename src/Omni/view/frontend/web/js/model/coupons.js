define(['jquery', 'ko'], function ($, ko) {
    "use strict";

    var couponsData = window.checkoutConfig.coupons,
        coupons = ko.observable(couponsData);

    return {
        coupons: coupons,
        /**
         *
         * @return {*}
         */
        getCoupons: function () {
            return coupons;
        },
    }
});

