define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'ls_payment_method_pay_at_store',
                component: 'Ls_Omni/js/view/payment/method-renderer/pay-at-store'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });