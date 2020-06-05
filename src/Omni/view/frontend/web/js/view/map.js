define([
    'jquery'
], function ($) {
    'use strict';
    return {
        initMap: function () {
            var self = this;
            var myLatLng = {
                lat: window.checkoutConfig.shipping.select_store.lat,
                lng: window.checkoutConfig.shipping.select_store.lng
            };
            var map = new google.maps.Map(document.getElementById('map-canvas'), {
                zoom: window.checkoutConfig.shipping.select_store.zoom,
                center: myLatLng
            });

            var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
            var infoWindow = new google.maps.InfoWindow();

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
                        var storeInfo = $(stores.storesInfo).find('#store-' + store.nav_id).html();
                        infoWindow.setContent('<div style="text-align: center;"><button style="font-size: 10px;width: 120px;padding: 2px 0px;margin-right: 5px;" data-id="'
                            + store.nav_id + '" class="check-store-availability">Check Availability</button><button style="font-size: 10px;width: 120px;padding: 2px 0px;margin-right: 5px;" disabled data-id="'
                            + store.nav_id + '" data-name="' + store.Name + '" class="apply-store">Pick Up Here!</button></div><div class="stock-remarks"><div class="custom-loader" style="text-align:center;"></div><ul style="padding:0;margin-bottom: 10px;"></ul></div></div><br/>'
                            + '<div class="infowindow">' + storeInfo + '</div>');
                        infoWindow.open(map, marker);
                    });
                })(marker, store);
            });
        }
    }
});
