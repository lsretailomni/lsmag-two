define([
    'jquery',
    'mage/url',
    'lsomni/map-loader',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/validation'
], function ($, urlBuilder, MapLoader, modal, alert) {
    'use strict';

    $.widget('lsomni.stock', {
        options: {
            googleMapApiKey: '',
            defaultLat: '',
            defaultLong: '',
            defaultZoom: ''
        },

        _create: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            $(document).on('click', 'a.checkavailability', function () {
                if (!self.element.validation('isValid')) {
                    return false;
                }

                var formData = self.element.data(),
                    sku = formData.productSku,
                    selectedSimpleProductId = $("input[name=selected_configurable_option]").val(),
                    requestUrl = urlBuilder.build("omni/stock/product?sku=" + sku + "&id=" + selectedSimpleProductId);

                $.ajax({
                    url: requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    showLoader: true,
                    success: function (data) {
                        var stores = $.parseJSON(data.stocks);
                        if (stores.totalRecords > 0) {
                            self.getPopup(stores).openModal();
                        } else {
                            alert({
                                title: data.title,
                                content: data.content
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

        getPopup: function (stores) {
            var self = this;

            if (!this.popup) {
                MapLoader.done(function () {
                    self.initMap(stores);
                }).fail(function () {
                    console.error("Google Maps failed to load.");
                });

                this.popup = modal({
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'mc_cac_map',
                    buttons: [],
                    type: 'slide'
                }, $('#map-canvas'));
            }

            return this.popup;
        },

        initMap: function (stores) {
            var self = this,
                center = {
                    lat: parseFloat(self.options.defaultLat),
                    lng: parseFloat(self.options.defaultLong)
                },
                map = new google.maps.Map(document.getElementById('map-canvas'), {
                    zoom: parseInt(self.options.defaultZoom),
                    center: center
                }),
                infoWindow = new google.maps.InfoWindow();

            $.each(stores.items, function (index, store) {
                var latLng = {
                    lat: parseFloat(store.latitude),
                    lng: parseFloat(store.longitude)
                };

                var marker = new google.maps.Marker({
                    position: latLng,
                    map: map,
                    title: store.name
                });

                google.maps.event.addListener(marker, 'click', function () {
                    var storeInfo = $(stores.storesInfo).find('#store-' + store.no).html();
                    infoWindow.setContent(
                        '<div class="omni-stores-index">' +
                        '<div class="stores-maps-container">' +
                        '<div class="store-map-plus-info-container info-window">' +
                        storeInfo + '</div></div></div>'
                    );
                    infoWindow.open(map, marker);
                });
            });
        }
    });

    return $.lsomni.stock;
});
