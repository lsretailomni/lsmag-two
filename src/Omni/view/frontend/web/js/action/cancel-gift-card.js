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

    /**
     * Cancel a gift card or voucher, or remove a specific one from the list.
     *
     * @param {ko.observable} isApplied
     * @param {string|null}   giftCardNo          - gift card number (for full cancel)
     * @param {string|null}   giftCardPin
     * @param {string|null}   cancelVoucherNo     - specific voucher entry_no to remove
     * @param {string|null}   cancelGiftCardNo    - specific gift card entry_no to remove
     * @param {string|null}   cancelAllType       - 'vouchers' to clear all vouchers, 'gift_cards' to clear all gift cards
     */
    return function (isApplied, giftCardNo, giftCardPin, cancelVoucherNo, cancelGiftCardNo, cancelAllType) {
        var url = 'omni/ajax/updateGiftCard';

        messageContainer.clear();
        fullScreenLoader.startLoader();

        var payload = {
            'gift_card_no': giftCardNo || null,
            'gift_card_amount': 0,
            'gift_card_pin': giftCardPin || null
        };

        if (cancelVoucherNo) {
            payload['cancel_voucher_no'] = cancelVoucherNo;
        }
        if (cancelGiftCardNo) {
            payload['cancel_gift_card_no'] = cancelGiftCardNo;
        }
        if (cancelAllType === 'vouchers') {
            payload['cancel_all_vouchers'] = true;
        } else if (cancelAllType === 'gift_cards') {
            payload['cancel_all_gift_cards'] = true;
        }

        return storage.post(
            url,
            JSON.stringify(payload),
            true,
            'application/json'
        ).done(function (response) {
            var deferred,
                message = $t('POS data entry successfully removed.');

            totals.isLoading(true);
            getPaymentInformationAction(deferred = $.Deferred());
            $.when(deferred).done(function () {
                if (!cancelVoucherNo && !cancelGiftCardNo && !payload['cancel_all_vouchers'] && !payload['cancel_all_gift_cards'] && isApplied) {
                    isApplied(false);
                }
                totals.isLoading(false);
                fullScreenLoader.stopLoader();
            });
            messageContainer.addSuccessMessage({'message': message});
        }).fail(function (response) {
            totals.isLoading(false);
            fullScreenLoader.stopLoader();
            errorProcessor.process(response, messageContainer);
        });
    };
});
