define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Ls_Omni/js/model/points',
    'Magento_Checkout/js/model/totals',
    'Ls_Omni/js/action/set-loyalty-points',
    'Ls_Omni/js/action/cancel-points',
    'mage/translate',
], function ($, ko, Component, quote, points, totals, setLoyaltyPointsAction, cancelPointsAction, $t) {
    'use strict';

    var loyaltyPoints = ko.observable(null),
        isApplied;

    if (totals) {
        var pointSpent = totals.getSegment('ls_points_spent');
        if (pointSpent)
            loyaltyPoints(pointSpent.value);
    }
    isApplied = ko.observable(loyaltyPoints() != null);

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/payment/loyaltypoints'
        },


        balanceFormatted: ko.computed(function () {
            var label = $t('You have %1 loyalty points. Each of 10 Points gets %2 discount.');
            label = label.replace('%1', '<strong>' + points.format(points.balance) + '</strong>');
            return label.replace('%2', '<strong>' + points.rateLabel + '</strong>');
        }),

        loyaltyPoints: loyaltyPoints,

        /**
         * Applied flag
         */
        isApplied: isApplied,

        /**
         * loyalty Points  application procedure
         */
        applyPoints: function () {
            if (this.validatePoints()) {
                setLoyaltyPointsAction(loyaltyPoints(), isApplied);
            }
        },

        /**
         * Cancel using loyalty
         */
        cancelPoints: function () {
            if (this.validatePoints()) {
                loyaltyPoints('');
                cancelPointsAction(isApplied);
            }
        },

        /**
         * loyalty form validation
         *
         * @returns {Boolean}
         */
        validatePoints: function () {
            var form = '#loyalty-form';

            return $(form).validation() && $(form).validation('isValid');
        },

        isDisplayed: function () {
            return points.balance > 0;
        }
    });
});