define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/error-processor',
    'Ls_Omni/js/model/payment/loyalty-points-messages',
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
            url = 'omni/ajax/updatePoints',
            message = $t('Your points are successfully removed.');

        messageContainer.clear();
        fullScreenLoader.startLoader();

        return storage.post(
            url,
            JSON.stringify({'loyaltyPoints': 0}),
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
