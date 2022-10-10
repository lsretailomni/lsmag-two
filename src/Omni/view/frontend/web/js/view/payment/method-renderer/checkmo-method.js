define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_OfflinePayments/payment/checkmo'
        },

        /**
         * Returns send check to info.
         *
         * @return {*}
         */
        getMailingAddress: function () {
            if (typeof window.checkoutConfig.payment.checkmo === 'undefined' && window.checkoutConfig.ls_enabled) {
                return "";
            } else {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            }
        },

        /**
         * Returns payable to info.
         *
         * @return {*}
         */
        getPayableTo: function () {
            if (typeof window.checkoutConfig.payment.checkmo === 'undefined' && window.checkoutConfig.ls_enabled) {
                return null;
            } else {
                return window.checkoutConfig.payment.checkmo.payableTo;
            }
        }
    });
});
