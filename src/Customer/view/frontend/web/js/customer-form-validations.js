define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    $.widget('mage.customerFormValidations', {
        options: {
            formSelector: 'form',
            emailSelector: 'input[type="email"]'
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            var email = $(this.options.formSelector).find(this.options.emailSelector),
                validation = eval('(' + email.attr('data-validate') + ')');
            validation['validate-length'] = true;
            validation = JSON.stringify(validation);
            email.attr('data-validate', validation);
            email.addClass('maximum-length-80');
        },
    });

    return $.mage.customerFormValidations;
});
