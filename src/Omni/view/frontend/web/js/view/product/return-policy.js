define([
    'jquery',
    'mage/validation',
    'mage/url',
    'ko',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, _, url, ko, modal, alert, $t) {
    'use strict';

    function initializer(config, node) {
        var dataForm = $(node);
        var ignore = null;
        $(document).on("click", "a.return-policy", function () {
            var validOrNotValid = dataForm.validation('isValid'); //validates form and returns boolean
            if (validOrNotValid) {
                var formData = dataForm.data();
                var itemId = formData.productSku;
                var variantId = $("input[name=selected_configurable_option]").val();
                var controllerUrl = getBaseUrl("rest/V1/get-return-policy" + "?itemId=" + itemId + "&variantId=" + variantId + "&storeId=");
                $.ajax({
                    url: controllerUrl,
                    type: 'POST',
                    dataType: "json",
                    beforeSend: function () {
                        $('body').loader('show');
                    },
                    complete: function () {
                        $('body').loader('hide');
                    },
                    success: function (data) {
                        if (data != null && data != '') {
                            $('#ls-return-policy').html(data);
                            getPopUp(data).openModal();
                        } else {
                            alert({
                                title: $t("Not Found"),
                                content: $t("Return Policy not found"),
                                actions: {
                                    always: function () {
                                    }
                                }
                            });
                        }
                    },
                    error: function (xhr) { // if error occured
                        console.log(xhr.statusText + xhr.responseText);
                    }
                });
            }
            return false;
        });
    }

    function getPopUp() {
        var self = this,
            buttons;

        if (!popUp) {
            var popUp = modal({
                'responsive': true,
                'innerScroll': true,
                'buttons': [],
                'type': 'popup',
                'modalClass': 'return-policy-content',
                closed: function () {
                    getPopUp();
                }
            }, $('#ls-return-policy'));
        }
        return popUp;
    }

    function getBaseUrl(param) {
        return url.build(param);
    }

    return function (config, node) {
        initializer(config, node);
    }
});
