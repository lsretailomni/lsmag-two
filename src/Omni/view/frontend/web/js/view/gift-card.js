define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Ls_Omni/js/action/set-gift-card',
    'Ls_Omni/js/action/cancel-gift-card'
], function ($, ko, Component, quote, totals, setGiftCardAction, cancelGiftCardAction) {
    'use strict';

    var giftCardAmount = ko.observable(null),
        giftCardNo = ko.observable(null),
        giftCardPin = ko.observable(null),
        isGiftCardApplied;

    var hasAnyEntries = false;

    if (totals) {
        var posDataSegment = totals.getSegment('ls_pos_data_entries'),
            voucherAmountSegment = totals.getSegment('ls_entry_amount');

        if (posDataSegment && posDataSegment.value) {
            try {
                var entries = JSON.parse(posDataSegment.value);
                if (Array.isArray(entries)) {
                    hasAnyEntries = entries.length > 0;
                }
            } catch (e) {}
        }

        if (!hasAnyEntries && voucherAmountSegment) {
            hasAnyEntries = true;
        }
    }

    // Single applied observable — true if ANY POS data entry (any entry_type) is applied
    isGiftCardApplied = ko.observable(hasAnyEntries);

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
         * Apply POS data entry
         */
        applyGiftCard: function () {
            if (this.validateGiftCard()) {
                setGiftCardAction(giftCardNo(), giftCardAmount(), giftCardPin(), isGiftCardApplied, null);
            }
        },

        /**
         * Cancel all POS data entries
         */
        cancelGiftCard: function () {
            if (this.validateGiftCard()) {
                cancelGiftCardAction(isGiftCardApplied, giftCardNo(), giftCardPin());
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
