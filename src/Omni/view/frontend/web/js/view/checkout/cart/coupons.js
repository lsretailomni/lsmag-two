define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('lsomni.coupons', {
        options: {
            ajaxUrl: '',
        },

        _create: function () {
            var self = this;

            // Delay to ensure other components render first
            setTimeout(function () {
                $.ajax({
                    url: self.options.ajaxUrl,
                    type: 'POST',
                    success: function (data) {
                        $('#ls-coupons').html(data.output);
                    },
                    error: function (xhr) {
                        console.error('Coupon load error:', xhr.statusText, xhr.responseText);
                    }
                });
            }, 2000);
        }
    });

    return $.lsomni.coupons;
});

