define([
        'jquery',
        'Magento_Checkout/js/view/payment/default'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ls_Omni/payment/paystore'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'ls_payment_method_pay_at_store';
            },

            isActive: function() {
                return true;
            }
        });
    }
);