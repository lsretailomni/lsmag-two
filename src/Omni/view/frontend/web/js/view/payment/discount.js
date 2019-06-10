/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Ls_Omni/js/model/coupons',
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon'
], function ($, ko, Component, quote, coupons, setCouponCodeAction, cancelCouponAction) {
    'use strict';

    var totals = quote.getTotals(),
        couponCode = ko.observable(null),
        isApplied;

    if (totals()) {
        couponCode(totals()['coupon_code']);
    }
    isApplied = ko.observable(couponCode() != null);

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/payment/discount'
        },
        couponCode: couponCode,

        /**
         * Applied flag
         */
        isApplied: isApplied,

        /**
         * Coupon code application procedure
         */
        apply: function () {
            if (this.validate()) {
                setCouponCodeAction(couponCode(), isApplied);
            }
        },

        /**
         * Cancel using coupon
         */
        cancel: function () {
            if (this.validate()) {
                couponCode('');
                cancelCouponAction(isApplied);
            }
        },

        /**
         * Coupon form validation
         *
         * @returns {Boolean}
         */
        validate: function () {
            var form = '#discount-form';

            return $(form).validation() && $(form).validation('isValid');
        },

        getCoupons: function () {
            return coupons.getCoupons();
        },

        selection: function (data, event) {
            var selected_value = $("input[name='group1']:checked").val();
            var ele = $("input[name='group1']:checked");
            ele.parent().siblings().removeClass('active');
            ele.parent().addClass('active');
            $("#discount-code").val(selected_value).change();
            return true;
        }
    });
});
