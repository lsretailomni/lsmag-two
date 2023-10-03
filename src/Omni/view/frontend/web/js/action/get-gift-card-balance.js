define([
    'ko',
    'jquery',
    'mage/storage',
    'mage/translate'
], function (ko, $, storage, $t) {
    'use strict';

    return function (giftCardData, giftCardBalance, giftCardExpiryDate, errorMessages) {
        var url = 'omni/ajax/CheckGiftCardBalance';
        giftCardBalance();
        giftCardExpiryDate();
        errorMessages();
        var body = $('body').loader();
        body.loader('show');
        return storage.post(
            url,
            JSON.stringify(giftCardData),
            false,
            'application/json'
        ).done(function (response) {
            if (response.success) {
                var data = JSON.parse(response.data);
                giftCardBalance(data.giftcardbalance);
                giftCardExpiryDate(data.expirydate);
                errorMessages(null);
                body.loader('hide');
            } else {
                giftCardBalance(null);
                giftCardExpiryDate(null);
                errorMessages(response.message);
                body.loader('hide');
            }
        }).fail(function (response) {
            body.loader('hide');
        });
    };
});