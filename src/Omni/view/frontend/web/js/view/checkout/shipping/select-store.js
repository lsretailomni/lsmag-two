define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote',
    'lsomni/map-loader',
    'lsomni/map',
    'mage/url'
], function (Component, ko, $, $t, modal, quote, MapLoader, map, url) {
    'use strict';

    var popUp1 = null;
    var popUp2 = null;
    return Component.extend({
        defaults: {
            template: 'Ls_Omni/checkout/shipping/select-store',
            body: $('body')
        },
        isClickAndCollect: ko.observable(false),
        isSelectStoreVisible: ko.observable(false),
        isMapVisible: ko.observable(false),
        pickStoreId: ko.observable(null),
        pickStoreName: ko.observable(null),

        initialize: function () {
            this._super();
            var self = this;
            quote.shippingMethod.subscribe(function () {
                var storeId, storeName;
                if (quote.shippingMethod() &&
                    quote.shippingMethod().carrier_code !== undefined &&
                    quote.shippingMethod().carrier_code === 'clickandcollect') {
                    self.isClickAndCollect(true);
                    var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
                    if (stores.totalRecords > 0) {
                        self.isSelectStoreVisible(true);
                        if (stores.totalRecords === 1) {
                            storeId = stores.items[0].nav_id;
                            storeName = stores.items[0].Name;
                            self.pickStoreId(storeId);
                            self.pickStoreName(storeName);
                        }
                    }
                } else {
                    self.isClickAndCollect(false);
                }
            });

            this.isMapVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                } else {
                    self.getPopUp().closeModal();
                }
            });

            ko.bindingHandlers.datetimepicker = {
                init: function (element, valueAccessor, allBindingsAccessor) {
                    var $el = $(element);
                    $el.datetimepicker({
                        'showTimepicker': false,
                        'format': 'yyyy-MM-dd'
                    });
                    var writable = valueAccessor();
                    if (!ko.isObservable(writable)) {
                        var propWriters = allBindingsAccessor()._ko_property_writers;
                        if (propWriters && propWriters.datetimepicker) {
                            writable = propWriters.datetimepicker;
                        } else {
                            return;
                        }
                    }
                    writable($(element).datetimepicker("getDate"));
                },
                update: function (element, valueAccessor) {
                    var widget = $(element).data("DateTimePicker");
                    if (widget) {
                        var date = ko.utils.unwrapObservable(valueAccessor());
                        widget.date(date);
                    }
                }
            };

            self.body.on('click', '.apply-store', function () {
                self.pickStoreId($(this).data('id'));
                self.pickStoreName($(this).data('name'));
                self.isMapVisible(false);
                if (popUp2) {
                    popUp2.closeModal();
                }
            });

            self.body.on('click', '.check-store-availability', function () {
                var selectedStore = $(this).data('id'),
                    controllerUrl = self.getBaseUrl("omni/stock/store" + "?storeid=" + selectedStore),
                    backUrl = self.getBaseUrl("checkout/cart"),
                    translatedText = $t('cart'),
                    flag = "1",
                    stockRemarks = $('.stock-remarks'),
                    stockRemarksList = stockRemarks.find('ul'),
                    customLoader = $('.custom-loader'),
                    storeMapPlusInfoContainer = $('.store-map-plus-info-container'),
                    storeOpeningHours = storeMapPlusInfoContainer.find('.store-opening-hours'),
                    applyStoreBtn = $('.apply-store');
                $.ajax({
                    url: controllerUrl,
                    type: 'POST',
                    dataType: "json",
                    beforeSend: function () {
                        customLoader.append('<p>loading...</p>');
                    },
                    complete: function () {
                        customLoader.html("");
                    },
                    success: function (data) {
                        stockRemarksList.html('');
                        if (data.storeHoursHtml) {
                            storeOpeningHours.remove();
                            storeMapPlusInfoContainer.append(data.storeHoursHtml);
                        }
                        if (data.stocks) {
                            for (var i in data.stocks) {
                                var o = data.stocks[i];
                                if (o.status === "0" && flag === "1") {
                                    flag = "0";
                                }
                                if (o.status === "0") {
                                    stockRemarksList.append("<br/><li><strong>" + o.name + ":</strong> <span style='color:red'>" + o.display + "</span></li>")
                                } else {
                                    stockRemarksList.append("<br/><li><strong>" + o.name + ":</strong> <span style='color:green'>" + o.display + "</span></li>")
                                }
                            }
                            if (flag === "1") {
                                applyStoreBtn.removeAttr('disabled');
                            } else {
                                stockRemarks.find('> strong').remove();
                                stockRemarks.append("<strong>" + data.remarks + " <a href='" + backUrl + "'>" + translatedText + "</a></strong>");
                            }
                        } else {
                            stockRemarks.find('> strong').remove();
                            stockRemarks.append("<br/><strong>" + data.remarks + "</strong><br/>");
                        }
                    },
                    error: function (xhr) {
                        // if any error occurred
                        console.log(xhr.statusText + xhr.responseText);
                    }
                });

            });
        },

        showMap: function () {
            this.isMapVisible(true);
        },
        getBaseUrl: function (param) {
            return url.build(param);
        },
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp1) {
                MapLoader.done($.proxy(map.initMap, this)).fail(function () {
                    console.error("ERROR: Google maps library failed to load");
                });
                popUp1 = modal({
                    'responsive': true,
                    'innerScroll': true,
                    'buttons': [],
                    'type': 'slide',
                    'modalClass': 'mc_cac_map',
                    closed: function () {
                        self.isMapVisible(false)
                    }
                }, $('#map-canvas'));
            }
            return popUp1;
        },
        getStores: function () {
            var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
            return stores.items;
        },
        check: function (data, event) {
            console.log(event);
            var a = "", query = "", txtValue = "";
            query = $(event.currentTarget).val().toUpperCase();
            a = $(".cnc-stores-dropdown .block-dropdown a");
            for (var i = 0; i < a.length; i++) {
                txtValue = a[i].textContent || a[i].innerText;
                if (txtValue.toUpperCase().startsWith(query)) {
                    a[i].style.display = "";
                } else {
                    a[i].style.display = "none";
                }
            }
        },
        clicked: function (store) {
            if (!popUp2) {
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: 'Click & Collect Store',
                    buttons: [],
                }
                popUp2 = modal(options, $('#popup-modal'));
            }
            var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores),
                availableStoresOnly = $.parseJSON(window.checkoutConfig.shipping.select_store.available_store_only),
                storeInfo = $(stores.storesInfo).find('#store-' + store.nav_id).html(),
                popupModal = $("#popup-modal"),
                popUpHtml = '<div class="double-btn-container">';
            if (!availableStoresOnly) {
                popUpHtml += '<button data-id="'
                + store.nav_id + '" class="check-store-availability">Check Availability</button><button disabled data-id="'
                + store.nav_id + '" data-name="' + store.Name + '" class="apply-store">Pick Up Here!</button>';
            } else {
                popUpHtml += '<button data-id="'
                + store.nav_id + '" data-name="' + store.Name + '" class="apply-store">Pick Up Here!</button>';
            }

            popUpHtml += '</div><div class="stock-remarks"><div class="custom-loader"></div><ul></ul></div></div><br/>'
            + '<div class="omni-stores-index "><div class="stores-maps-container"><div class="store-map-plus-info-container info-window">' + storeInfo + '</div></div></div>';

            popupModal.html('').append(popUpHtml);
            popupModal.modal('openModal');
        }
    });
});
