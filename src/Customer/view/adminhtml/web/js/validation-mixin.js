define(['jquery'], function ($) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-username',
            function (value) {
                return value.match(/^[a-zA-Z0-9-_@.]+$/);
            },
            $.mage.__('Enter a valid username prefix. Valid characters are A-Z a-z 0-9 . _ - @.')
        );
    }
});
