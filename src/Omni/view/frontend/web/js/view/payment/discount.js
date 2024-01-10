define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Ls_Omni/js/model/coupons',
    'Magento_Customer/js/model/customer',
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon'
], function ($, ko, Component, quote, coupons, customer, setCouponCodeAction, cancelCouponAction) {
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
            $(event.currentTarget).find('input').attr('checked', 'checked');
            var ele = $("input[name='group1']:checked"),
                selected_value = ele.val();
            ele.parent().siblings().removeClass('active');
            ele.parent().addClass('active');
            $("#discount-code").val(selected_value).change();
            return true;
        },
        checkCustomerLoggedIn: function () {
            return customer.isLoggedIn;
        },

        isDisplay: function () {
            return window.checkoutConfig.coupons_display === true && (this.getCoupons())().length > 0 && !(this.isApplied()) && this.checkCustomerLoggedIn();
        }
    });
});
