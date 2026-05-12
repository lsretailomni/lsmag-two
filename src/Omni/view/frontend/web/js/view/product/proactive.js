define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('lsomni.proactiveDiscounts', {
        options: {
            ajaxUrl: '',
            currentProduct: ''
        },

        _create: function () {
            var self = this;

            // Delay to ensure other components render first
            setTimeout(function () {
                $.ajax({
                    url: self.options.ajaxUrl,
                    type: 'GET',
                    data: {
                        currentProduct: self.options.currentProduct
                    },
                    success: function (response) {
                        $('#ls-discounts').html(response.output);
                        $('#ls-discounts .proactive-discounts-container').trigger('contentUpdated');
                    },
                    error: function (xhr) {
                        console.error('Discount load error:', xhr.statusText, xhr.responseText);
                    }
                });
            }, 2000);
        }
    });

    return $.lsomni.proactiveDiscounts;
});
