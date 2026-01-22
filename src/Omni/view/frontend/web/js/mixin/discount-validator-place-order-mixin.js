define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (
    $,
    quote,
    urlBuilder,
    urlFormatter,
    storage,
    errorProcessor,
    totalsDefault,
    customerData,
    alert,
    $t
) {
    'use strict';

    return function (placeOrderAction) {
        return function (paymentData, messageContainer) {
        
                return validateDiscountBeforePlaceOrder().then(
                    function () {
                        return placeOrderAction(paymentData, messageContainer);
                    },
                    function () {
                        return $.Deferred().reject().promise();
                    }
                );
            };

        function validateDiscountBeforePlaceOrder()
        {
            var deferred = $.Deferred();
            var url = urlBuilder.createUrl('/check-discount-validity', {});
            var payload = { cart_id: quote.getQuoteId() };

            $.ajax({
                url: urlFormatter.build(url),
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',

                success: function (response) {

                    for (var i = 0; i < response.length; i++) {
                        var validity = response[i];
                        if (!validity.valid) {

                            // Refresh UI
                            totalsDefault.estimateTotals(quote.shippingAddress);
                            customerData.invalidate(['cart']);
                            customerData.reload(['cart'], true);

                            // Remove coupon/gift-card
                            if (validity.type === 'giftcard') {
                                $('#gift-card .action-cancel').trigger('click');
                            } else if (validity.remarks === 'coupon') {
                                $('#discount-form .action-cancel').trigger('click');
                            }

                            alert({
                                title: $t('Notice'),
                                content: validity.msg
                            });

                            deferred.reject();
                            return;
                        }
                    }

                    deferred.resolve();
                },

                error: function (response) {
                    errorProcessor.process(response);
                    deferred.reject();
                }
            });

            return deferred.promise();
        }
    };
});
