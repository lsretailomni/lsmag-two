define([
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Customer/js/customer-data',
        'Magento_Ui/js/modal/alert',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko, $, quote, urlBuilder, urlFormatter, totalsDefault, customerData, alert, $t, fullScreenLoader) {
        'use strict';
        return {
            /**
             * Validate something
             *
             * @returns {boolean}
             */
            validate: function () {
                var quoteId = quote.getQuoteId(),
                    validation = 'true',
                    url = urlBuilder.createUrl('/check-discount-validity', {}),
                    payload = {'cart_id': quoteId};
                    $.ajax({
                        url: urlFormatter.build(url),
                        type: 'post',
                        data: JSON.stringify(payload),
                        async: false,
                        contentType: 'application/json',
                        global: false,
                        beforeSend: function () {
                            fullScreenLoader.startLoader();
                        },
                        complete: function () {
                            fullScreenLoader.stopLoader();
                        },
                        /** @inheritdoc */
                        success: function (response) {
                            response.forEach((validity) => {
                                if (validity['valid'] === false) {
                                    validation = 'false';
                                    totalsDefault.estimateTotals(quote.shippingAddress);
                                    customerData.invalidate(['cart']);
                                    customerData.reload(['cart'], true);

                                    if (validity['type'] === 'giftcard') {
                                        if ($('#gift-card .field .input-text').val()) {
                                            $('#gift-card .action-cancel').trigger('click');
                                        }
                                    } else {
                                        if (validity['remarks'] == 'coupon' &&
                                            $('#discount-form .field .input-text').val()
                                        ) {
                                            $('#discount-form .action-cancel').trigger('click');
                                        }
                                    }
                                    alert({
                                        title: $t('Notice'),
                                        content: validity['msg'],
                                        actions: {
                                            always: function () {}
                                        }
                                    });
                                }
                            });
                        },

                        /** @inheritdoc */
                        error: function (xhr) {
                            console.log(xhr.statusText + xhr.responseText);
                            fullScreenLoader.stopLoader();
                        }
                    });
                return validation === 'true';
            }
        }
    }
);
