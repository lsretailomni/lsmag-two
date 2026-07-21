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
         * Get list of individual applied vouchers (non-GIFTCARDNO entries).
         * Reads from TotalsInterface extension_attributes — segment.value is float-typed
         * in Magento's API and would cast JSON to 0.
         */
        getVoucherList: function () {
            var totalsData = totals.totals();
            var extAttrs   = totalsData && totalsData.extension_attributes;
            if (!extAttrs || !extAttrs.ls_pos_data_entries) {
                return [];
            }
            try {
                var parsed = JSON.parse(extAttrs.ls_pos_data_entries);
                if (Array.isArray(parsed)) {
                    return parsed.filter(function (e) {
                        return (e.entry_type || '').toUpperCase() !== 'GIFTCARDNO';
                    });
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
