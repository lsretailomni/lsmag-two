define([
    'jquery'
], function ($) {
    'use strict';
    return {
        initMap: function () {
            var self = this,
                myLatLng = {
                    lat: window.checkoutConfig.shipping.select_store.lat,
                    lng: window.checkoutConfig.shipping.select_store.lng
            },
                map = new google.maps.Map(document.getElementById('map-canvas'), {
                    zoom: window.checkoutConfig.shipping.select_store.zoom,
                    center: myLatLng
                }),
                stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores),
                infoWindow = new google.maps.InfoWindow(),
                availableStoresOnly = $.parseJSON(window.checkoutConfig.shipping.select_store.available_store_only)

            $.each(stores.items, function (index, store) {

                var latitude = parseFloat(store.Latitute),
                    longitude = parseFloat(store.Longitude),
                    latLng = new google.maps.LatLng(latitude, longitude),
                    marker = new google.maps.Marker({
                        position: latLng,
                        map: map,
                        title: store.Name
                    });

                (function (marker, store) {
                    google.maps.event.addListener(marker, 'click', function (e) {
                        if (store.State == null) {
                            store.State = "";
                        }
                        var storeInfo = $(stores.storesInfo).find('#store-' + store.nav_id).html(),
                        infoWindowContent = '<div style="text-align: center;">';
                        if (!availableStoresOnly) {
                            infoWindowContent += '<button style="font-size: 10px;width: 120px;padding: 2px 0px;margin-right: 5px;" data-id="'
                            + store.nav_id + '" class="check-store-availability">Check Availability</button><button style="font-size: 10px;width: 120px;padding: 2px 0px;margin-right: 5px;" disabled data-id="'
                            + store.nav_id + '" data-name="' + store.Name + '" class="apply-store">Pick Up Here!</button>';
                        } else {
                            infoWindowContent += '<button style="font-size: 10px;width: 120px;padding: 2px 0px;margin-right: 5px;" data-id="'
                            + store.nav_id + '" data-name="' + store.Name + '" class="apply-store">Pick Up Here!</button>';
                        }

                        infoWindowContent += '</div><div class="stock-remarks"><div class="custom-loader" style="text-align:center;"></div><ul style="padding:0;margin-bottom: 10px;"></ul></div></div><br/>'
                        + '<div class="omni-stores-index "><div class="stores-maps-container"><div class="store-map-plus-info-container info-window">' + storeInfo + '</div></div></div>';
                        infoWindow.setContent(infoWindowContent);
                        infoWindow.open(map, marker);
                    });
                })(marker, store);
            });
        }
    }
});
