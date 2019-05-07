define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/totals'
    ], function (Component, totals) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'Ls_Omni/totals/giftcard-label'
            },

            /**
             * Get GiftCard Segment
             * @returns {*}
             */
            getGiftCardAmountUsed: function () {
                return totals.getSegment('ls_giftcard_amount_used') || 0;
            },

            /**
             * Get Gift Card Amount applied
             * @returns {*}
             */
            getGiftCardAmountUsedValue: function () {
                var giftCardAmount = this.getGiftCardAmountUsed().value;

                return giftCardAmount;
            }
        });
    }
);
