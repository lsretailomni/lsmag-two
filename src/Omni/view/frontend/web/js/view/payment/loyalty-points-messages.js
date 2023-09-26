define([
    'Magento_Ui/js/view/messages',
    '../../model/payment/loyalty-points-messages'
], function (Component, messageContainer) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/messages',
            selector: '[data-role=loyalty-points-messages]'
        },

        /** @inheritdoc */
        initialize: function (config) {
            return this._super(config, messageContainer);
        }
    });
});
