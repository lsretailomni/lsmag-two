define([
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Customer/js/customer-data',
        'Magento_Ui/js/modal/alert',
        'mage/translate'
    ],
    function (ko, $, quote, urlBuilder, urlFormatter, totalsDefault, customerData, alert, $t) {
        'use strict';
        return {
            /**
             * Validate something
             *
             * @returns {boolean}
             */
            validate: function () {
                var quoteId = quote.getQuoteId(),
                    url = urlBuilder.createUrl('/check-discount-validity', {}),
                    payload = {'cart_id': quoteId},
                    validation = $.ajax({
                        url: urlFormatter.build(url),
                        type: 'post',
                        data: JSON.stringify(payload),
                        async: false,
                        contentType: 'application/json',
                        global: false,

                        /** @inheritdoc */
                        success: function (response) {
                            if (response === false) {
                                totalsDefault.estimateTotals(quote.shippingAddress);
                                customerData.invalidate(['cart']);
                                customerData.reload(['cart'], true);
                                alert({
                                    title: $t('Notice'),
                                    content: $t('Unfortunately since your discount is no longer valid your order summary has been updated.'),
                                    actions: {
                                        always: function () {}
                                    }
                                });
                            }
                            return response;
                        },

                        /** @inheritdoc */
                        error: function (xhr) {
                            console.log(xhr.statusText + xhr.responseText);
                        }
                    }).responseText;
                return validation === 'true';
            }
        }
    }
);
