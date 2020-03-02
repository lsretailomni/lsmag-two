define([
    'jquery'
], function ($) {
    "use strict";

    return function () {
        $.validator.addMethod(
            'validate-sequence-number',
            function (value) {
                var regex;
                regex = /^[a-zA-Z0-9-_]+$/;
                if ($.mage.isEmpty(value) || !regex.test(value)) {
                    return false;
                }
                return true;
            },
            $.mage.__('Please use only letters (a-z or A-Z), numbers (0-9), dash or underscore ' +
                'in this field.')
        );
    }
});