/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/view/messages',
    '../../model/payment/gift-card-messages'
], function (Component, messageContainer) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/giftcard-messages',
            selector: '[data-role=gift-card-messages]'
        },

        /** @inheritdoc */
        initialize: function (config) {
            return this._super(config, messageContainer);
        }
    });
});
