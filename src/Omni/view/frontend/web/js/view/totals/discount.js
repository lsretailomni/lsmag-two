define([
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/totals'
    ], function (Component, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Ls_Omni/totals/discount'
            },

            /**
             * Get loyalty points discount total
             * @returns {*}
             */
            getTotal: function () {
                return totals.getSegment('ls_points_discount');
            },

            /**
             * Get loyalty points discount formatted
             * @returns {*|String}
             */
            getValue: function () {
                return '-' + this.getFormattedPrice(this.getTotal().value);
            }
        });
    }
);
