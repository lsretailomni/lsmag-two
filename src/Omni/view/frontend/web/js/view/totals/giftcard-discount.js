define([
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/totals',
        'Ls_Omni/js/action/cancel-gift-card',
        'Magento_Checkout/js/model/full-screen-loader'
    ], function (Component, totals, cancelGiftCardAction, fullScreenLoader) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Ls_Omni/totals/giftcard-discount'
            },

            /**
             * Get Gift Card discount total
             * @returns {*}
             */
            getTotal: function () {
                return totals.getSegment('ls_gift_card_amount_used');
            },

            /**
             * Get loyalty points discount formatted
             * @returns {*|String}
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
             * Get list of individual applied gift cards from ls_gift_card_no segment (JSON array)
             */
            getGiftCardList: function () {
                var segment = totals.getSegment('ls_gift_card_no');
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

            getGiftCardAmount: function (card) {
                return this.getFormattedPrice(-card.amount);
            },

            removeGiftCard: function (card) {
                cancelGiftCardAction(null, null, null, null, card.entry_no || card.no);
            },

            /**
             * Cancel all applied gift cards (keep vouchers)
             */
            cancelAllGiftCards: function () {
                cancelGiftCardAction(null, null, null, null, null, 'gift_cards');
            }
        });
    }
);
