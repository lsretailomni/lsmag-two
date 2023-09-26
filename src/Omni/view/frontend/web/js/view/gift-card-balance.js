define([
    'jquery',
    'ko',
    'uiComponent',
    'Ls_Omni/js/action/get-gift-card-balance',
    'mage/translate',
], function ($, ko, Component, getGiftCardBalanceAction, $t) {
    'use strict';
    var giftCardBalance = ko.observable(null);
    var giftCardExpiryDate = ko.observable(null);
    var errorMessages = ko.observable(null);
    return Component.extend({
        defaults: {
            template: 'Ls_Omni/gift-card-balance'
        },

        checkGiftCardBalance: function (form) {
            var giftCardData = {},
                formDataArray = $(form).serializeArray();
            formDataArray.forEach(function (entry) {
                giftCardData[entry.name] = entry.value;
            });
            if ($(form).validation()
                && $(form).validation('isValid')
            ) {
                getGiftCardBalanceAction(giftCardData, giftCardBalance, giftCardExpiryDate, errorMessages).always(function () {
                });
            }
        },
        getGiftCardBalance: function () {
            return giftCardBalance;
        },
        getGiftCardExpiryDate: function () {
            return giftCardExpiryDate;
        },
        getErrorMessages: function () {
            return errorMessages;
        },
        cancelGiftCardBalance: function () {
            $('#gift_card_code').val('');
            giftCardBalance(null);
            giftCardExpiryDate(null);
            errorMessages(null);
        }
    });
});