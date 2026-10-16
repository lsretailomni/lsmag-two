define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/validation'
], function ($, urlBuilder, modal, alert) {
    'use strict';

    $.widget('lsomni.vendorSourcing', {
        options: {
            ajaxUrl: ''
        },

        _create: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            $(document).on('click', 'a.ls-vendor-sourcing-link', function () {
                if (!self.element.validation('isValid')) {
                    return false;
                }

                var formData = self.element.data(),
                    sku = formData.productSku,
                    selectedSimpleProductId = $('input[name=selected_configurable_option]').val(),
                    params = {sku: sku};

                if (selectedSimpleProductId) {
                    params.id = selectedSimpleProductId;
                }

                $.ajax({
                    url: self.options.ajaxUrl || urlBuilder.build('omni/ajax/vendorsourcingoptions'),
                    type: 'GET',
                    data: params,
                    dataType: 'json',
                    showLoader: true,
                    success: function (data) {
                        if (data.success && data.output) {
                            self.openPopup(data.output);
                        } else {
                            self.showMessage(data.message);
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.statusText, xhr.responseText);
                        self.showMessage('');
                    }
                });

                return false;
            });
        },

        openPopup: function (html) {
            var container = $('#ls-vendor-sourcing-popup');

            container.html(html);

            if (!this.popup) {
                this.popup = modal({
                    title: $.mage.__('Delivery & collection options'),
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'ls-vendor-sourcing-modal',
                    buttons: []
                }, container);
            }

            this.popup.openModal();
        },

        showMessage: function (message) {
            alert({
                title: $.mage.__('Notice'),
                content: message ||
                    $.mage.__('Delivery and collection options are currently not available. Please try again later.')
            });
        }
    });

    return $.lsomni.vendorSourcing;
});
