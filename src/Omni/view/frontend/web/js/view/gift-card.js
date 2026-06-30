define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Ls_Omni/js/action/set-gift-card'
], function ($, ko, Component, quote, totals, setGiftCardAction) {
    'use strict';

    var giftCardAmount = ko.observable(null),
        giftCardNo = ko.observable(null),
        giftCardPin = ko.observable(null),
        isGiftCardApplied = ko.observable(false);

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/payment/giftcard'
        },

        giftCardNo: giftCardNo,
        giftCardAmount: giftCardAmount,
        giftCardPin: giftCardPin,

        /**
         * Applied flag — true when any POS data entry is applied
         */
        isGiftCardApplied: isGiftCardApplied,

        isPinCodeFieldEnable: function () {
            if (window.checkoutConfig.gift_card_pin_enable) {
                return true;
            }
        },

        /**
         * Apply POS data entry and clear fields so another can be added immediately.
         */
        applyGiftCard: function () {
            if (this.validateGiftCard()) {
                setGiftCardAction(giftCardNo(), giftCardAmount(), giftCardPin(), isGiftCardApplied, null);
                giftCardNo('');
                giftCardAmount('');
                giftCardPin('');
            }
        },

        /**
         * GiftCard form validation
         *
         * @returns {Boolean}
         */
        validateGiftCard: function () {
            var form = '#gift-card';

            return $(form).validation() && $(form).validation('isValid');
        }
    });
});
