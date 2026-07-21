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

    return function (gift_card_no, gift_card_amount, gift_card_pin, isGiftCardApplied, isVoucherApplied) {
        var quoteId = quote.getQuoteId(),
            url = 'omni/ajax/updateGiftCard';

        fullScreenLoader.startLoader();

        return storage.post(
            url,
            JSON.stringify({
                'gift_card_no': gift_card_no,
                'gift_card_amount': gift_card_amount,
                'gift_card_pin': gift_card_pin
            }),
            true,
            'application/json'
        ).done(function (response) {
            var deferred,
                message = $t('POS data entry successfully applied.');
            if (response.success) {
                deferred = $.Deferred();
                // Mark as applied — use whichever observable is provided
                if (typeof isGiftCardApplied === 'function') {
                    isGiftCardApplied(true);
                }
                if (typeof isVoucherApplied === 'function') {
                    isVoucherApplied(true);
                }
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
