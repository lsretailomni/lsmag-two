define([
    'ko',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/error-processor',
    'Ls_Omni/js/model/payment/gift-card-messages',
    'mage/storage',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/full-screen-loader'
], function (ko, $, quote, errorProcessor, messageContainer, storage, $t, getPaymentInformationAction,
             totals, fullScreenLoader
) {
    'use strict';

    return function (gift_card_no, gift_card_amount, isGiftCardApplied) {
        var quoteId = quote.getQuoteId(),
            url = 'omni/ajax/updateGiftCard',
            message = $t('Gift card successfully applied.');

        fullScreenLoader.startLoader();

        return storage.post(
            url,
            JSON.stringify({'gift_card_no': gift_card_no, 'gift_card_amount': gift_card_amount}),
            true,
            'application/json'
        ).done(function (response) {
            var deferred;
            if (response.success) {
                deferred = $.Deferred();
                isGiftCardApplied(true);
                totals.isLoading(true);
                getPaymentInformationAction(deferred);
                $.when(deferred).done(function () {
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                });
                messageContainer.addSuccessMessage({
                    'message': message
                });
            } else {
                fullScreenLoader.stopLoader();
                totals.isLoading(false);
                messageContainer.addErrorMessage({
                    'message': response.message
                });
            }
        }).fail(function (response) {
            fullScreenLoader.stopLoader();
            totals.isLoading(false);
            errorProcessor.process(response, messageContainer);
        });
    };
});
