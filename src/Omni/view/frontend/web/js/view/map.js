
define([
    'jquery'
], function ($) {
    'use strict';
    return {
        initMap: function() {
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
            console.log(stores);
            var infoWindow = new google.maps.InfoWindow();

            $.each(stores.items, function(index, store) {

                    var latitude = parseFloat(store.Latitute),
                        longitude = parseFloat(store.Longitude),
                        latLng = new google.maps.LatLng(latitude, longitude),
                        marker = new google.maps.Marker({
                            position: latLng,
                            map: map,
                            title: store.Name
                        });

                    (function(marker, store) {
                        google.maps.event.addListener(marker, 'click', function(e) {
                            infoWindow.setContent('<h3>'+ store.Name + '</h3><br /><br /><strong>Address: </strong>' + store.Street +', '+ store.City+'<br /><br /><button data-id="'
                                + store.nav_id + '" data-name="'+ store.Name +'" class="apply-store">Pick Up Here!</button></div>');
                            infoWindow.open(map, marker);
                        });
                    })(marker, store);
            });
        }
    }
});