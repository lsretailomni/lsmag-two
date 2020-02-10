define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Ls_Omni/js/action/set-gift-card',
    'Ls_Omni/js/action/cancel-gift-card',
    'mage/translate',
], function ($, ko, Component, quote, totals, setGiftCardAction, cancelGiftCardAction, $t) {
    'use strict';

    var giftCardAmount = ko.observable(null),
        isGiftCardApplied;

    var giftCardNo = ko.observable(null),
        isGiftCardApplied;


    if (totals) {
        var giftAmount = totals.getSegment('ls_gift_card_amount_used');
        if (giftAmount) {
            giftCardAmount(giftAmount.value);
        }
    }
    if (totals) {
        var giftNo = totals.getSegment('ls_gift_card_no');
        if (giftNo) {
            giftCardNo(giftNo.value);
        }
    }
    isGiftCardApplied = ko.observable(giftCardNo() != null && giftCardAmount() != null);

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/payment/giftcard'
        },

        giftCardNo: giftCardNo,
        giftCardAmount: giftCardAmount,

        /**
         * Applied flag
         */
        isGiftCardApplied: isGiftCardApplied,

        /**
         * Giftcard apply procedure
         */
        applyGiftCard: function () {
            if (this.validateGiftCard()) {
                setGiftCardAction(giftCardNo(), giftCardAmount(), isGiftCardApplied);
            }
        },

        /**
         * Cancel GiftCard
         */
        cancelGiftCard: function () {
            if (this.validateGiftCard()) {
                giftCardNo('');
                giftCardAmount('');
                cancelGiftCardAction(isGiftCardApplied);
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
        },

        /**
         * Enable or disable GiftCard
         */
        isDisplay: function () {
            if (window.checkoutConfig.gift_card_enable == "1") {
                return true;
            } else {
                return false;
            }

        }
    });
});