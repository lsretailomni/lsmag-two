define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals',
    'Ls_Omni/js/action/cancel-gift-card',
    'Magento_Checkout/js/model/full-screen-loader'
], function (ko, Component, totals, cancelGiftCardAction, fullScreenLoader) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/totals/voucher-discount'
        },

        /**
         * Get the ls_entry_amount totals segment
         */
        getTotal: function () {
            return totals.getSegment('ls_entry_amount');
        },

        /**
         * Get formatted total voucher discount value (negative)
         */
        getValue: function () {
            var total = this.getTotal();
            if (!total) {
                return this.getFormattedPrice(0);
            }
            var value = total.value < 0 ? total.value : -total.value;
            return this.getFormattedPrice(value);
        },

        /**
         * Get list of individual applied vouchers from ls_pos_data_entries segment (JSON array)
         */
        getVoucherList: function () {
            var segment = totals.getSegment('ls_pos_data_entries');
            if (!segment || !segment.value) {
                return [];
            }
            try {
                var parsed = JSON.parse(segment.value);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (e) {}
            return [];
        },

        /**
         * Format individual voucher amount
         */
        getVoucherAmount: function (voucher) {
            return this.getFormattedPrice(-voucher.amount);
        },

        /**
         * Remove a specific voucher by its code
         */
        removeVoucher: function (voucher) {
            cancelGiftCardAction(null, null, null, voucher.entry_no, null);
        },

        /**
         * Cancel all applied vouchers (keep gift cards)
         */
        cancelAllVouchers: function () {
            cancelGiftCardAction(null, null, null, null, null, 'vouchers');
        }
    });
});
