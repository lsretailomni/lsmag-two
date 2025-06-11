define([
    'jquery',
    'mage/validation',
    'mage/url',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, validation, urlBuilder, modal, alert, $t) {
    'use strict';

    $.widget('lsomni.returnPolicy', {
        options: {},

        _create: function () {
            var self = this;

            this.element.validation();

            $(document).on('click', 'a.return-policy', function (e) {
                e.preventDefault();

                if (!self.element.validation('isValid')) {
                    return false;
                }

                var formData = self.element.data(),
                    itemId = formData.productSku,
                    variantId = $("input[name=selected_configurable_option]").val(),
                    controllerUrl = self.buildUrl(
                        "rest/V1/get-return-policy" +
                        "?itemId=" + encodeURIComponent(itemId) +
                        "&variantId=" + encodeURIComponent(variantId) +
                        "&storeId="
                    );

                $.ajax({
                    url: controllerUrl,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function () {
                        $('body').loader('show');
                    },
                    complete: function () {
                        $('body').loader('hide');
                    },
                    success: function (data) {
                        if (data) {
                            $('#ls-return-policy').html(data);
                            self.getPopup().openModal();
                        } else {
                            alert({
                                title: $t("Not Found"),
                                content: $t("Return Policy not found")
                            });
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.statusText, xhr.responseText);
                    }
                });

                return false;
            });
        },

        getPopup: function () {
            if (!this.popup) {
                this.popup = modal({
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'return-policy-content',
                    buttons: [],
                    type: 'popup'
                }, $('#ls-return-policy'));
            }
            return this.popup;
        },

        buildUrl: function (path) {
            return urlBuilder.build(path);
        }
    });

    return $.lsomni.returnPolicy;
});
