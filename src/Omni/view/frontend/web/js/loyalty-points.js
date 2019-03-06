define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('lsomni.loyaltyPoints', {
        options: {
        },
        
        _create: function () {
            this.loyaltyPoints = $(this.options.loyaltyPointsSelector);
            this.removePoints = $(this.options.removePointsSelector);

            $(this.options.applyButton).on('click', $.proxy(function () {
                this.loyaltyPoints.attr('data-validate', '{required:true}');
                this.removePoints.attr('value', '0');
                $(this.element).validation().submit();
            }, this));

            $(this.options.cancelButton).on('click', $.proxy(function () {
                this.loyaltyPoints.removeAttr('data-validate');
                this.removePoints.attr('value', '1');
                this.element.submit();
            }, this));
        }
    });

    return $.lsomni.loyaltyPoints;
});
