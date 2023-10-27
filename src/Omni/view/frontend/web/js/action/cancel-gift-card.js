define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/error-processor',
    'Ls_Omni/js/model/payment/gift-card-messages',
    'mage/storage',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'mage/translate',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, quote, errorProcessor, messageContainer, storage, getPaymentInformationAction, totals, $t,
             fullScreenLoader
) {
    'use strict';

    return function (isApplied) {
        var quoteId = quote.getQuoteId(),
            url = 'omni/ajax/updateGiftCard',
            message = $t('Gift card successfully removed.');

        messageContainer.clear();
        fullScreenLoader.startLoader();

        return storage.post(
            url,
            JSON.stringify({'gift_card_no': null, 'gift_card_amount': 0, 'gift_card_pin': null}),
            true,
            'application/json'
        ).done(function () {
            var deferred = $.Deferred();

            totals.isLoading(true);
            getPaymentInformationAction(deferred);
            $.when(deferred).done(function () {
                isApplied(false);
                totals.isLoading(false);
                fullScreenLoader.stopLoader();
            });
            messageContainer.addSuccessMessage({
                'message': message
            });
        }).fail(function (response) {
            totals.isLoading(false);
            fullScreenLoader.stopLoader();
            errorProcessor.process(response, messageContainer);
        });
    };
});
