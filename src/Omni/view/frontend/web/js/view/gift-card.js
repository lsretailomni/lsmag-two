define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Ls_Omni/js/action/set-gift-card',
    'Ls_Omni/js/action/cancel-gift-card',
    'mage/translate',
    'mage/storage'
], function ($, ko, Component, quote, totals, setGiftCardAction, cancelGiftCardAction, $t, storage) {
    'use strict';

    var giftCardAmount = ko.observable(null),
        isGiftCardApplied;

    var giftCardNo = ko.observable(null),
        isGiftCardApplied;

    var giftCardPin = ko.observable(null),
        isGiftCardApplied;

    if (totals) {
        var giftAmount = totals.getSegment('ls_gift_card_amount_used');
        if (giftAmount) {
            giftCardAmount(giftAmount.value);
        }
        var giftNo = totals.getSegment('ls_gift_card_no');
        if (giftNo) {
            giftCardNo(giftNo.value);
        }
        var giftPin = totals.getSegment('ls_gift_card_pin');
        if (giftPin) {
            giftCardPin(giftPin.value);
        }
    }

    isGiftCardApplied = ko.observable(giftCardNo() != null && giftCardAmount() != null);

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/payment/giftcard'
        },

        giftCardNo: giftCardNo,
        giftCardAmount: giftCardAmount,
        giftCardPin: giftCardPin,

        /**
         * Applied flag
         */
        isGiftCardApplied: isGiftCardApplied,

        isPinCodeFieldEnable: function () {
            storage.get('omni/ajax/CheckPinCodeEnable').done(
                function (response) {
                    if (response.value) {
                        $('#pincode').show();
                    }
                }
            ).fail(
                function (response) {
                    $('#pincode').hide();
                }
            );
        },

        /**
         * Giftcard apply procedure
         */
        applyGiftCard: function () {
            if (this.validateGiftCard()) {
                setGiftCardAction(giftCardNo(), giftCardAmount(), giftCardPin(), isGiftCardApplied);
            }
        },

        /**
         * Cancel GiftCard
         */
        cancelGiftCard: function () {
            if (this.validateGiftCard()) {
                giftCardNo('');
                giftCardAmount('');
                giftCardPin('');
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
        }
    });
});
