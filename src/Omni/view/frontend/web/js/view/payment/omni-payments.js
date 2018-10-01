
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'loyaltypoints',
            component: 'Ls_Omni/js/view/payment/method-renderer/loyaltypoints-method'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
