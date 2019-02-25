define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/totals',
        'Ls_Omni/js/model/points'
    ], function (Component, totals, points) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'Ls_Omni/totals/label'
            },

            /**
             * Get earning segment
             * @returns {*}
             */
            getEarning: function () {
                return totals.getSegment('ls_points_earn');
            },

            /**
             * Get earning point formatted
             * @returns {*}
             */
            getEarningValue: function () {
                var point = this.getEarning().value;

                return points.format(point);
            },

            /**
             * Get spending segment
             * @returns {*}
             */
            getSpending: function () {
                return totals.getSegment('ls_points_spent');
            },

            /**
             * Get spending point formatted
             * @returns {*}
             */
            getSpendingValue: function () {
                var point = this.getSpending().value;

                return points.format(point);
            }
        });
    }
);
