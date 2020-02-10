var markers = [];
var maps = [];

define([
    'Ls_Omni/js/view/stores/async!https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&callback=initMap',
    'uiComponent',
    'jquery',
    'ko'
], function ($) {
    return {
        allStoresMap: function (locations) {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 5,
                center: {lat: parseFloat(locations[0][1]), lng: parseFloat(locations[0][2])},
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            window.map = new google.maps.Map(document.getElementById('map'), {
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            var infowindow = new google.maps.InfoWindow();

            var bounds = new google.maps.LatLngBounds();

            for (i = 0; i < locations.length; i++) {
                marker = new google.maps.Marker({
                    position: {lat: parseFloat(locations[i][1]), lng: parseFloat(locations[i][2])},
                    map: map
                });

                bounds.extend(marker.position);

                google.maps.event.addListener(marker, 'click', (function (marker, i) {
                    return function () {
                        infowindow.setContent(locations[i][0]);
                        infowindow.open(map, marker);
                    }
                })(marker, i));
            }

            map.fitBounds(bounds);
        },
        singleStoreMap: function (locations) {
            for (var i = 0; i < locations.length; i++) {
                var latlng = new google.maps.LatLng((locations[i][1], locations[i][2]));
                maps[i] = new google.maps.Map(document.getElementById('map' + i), {
                    zoom: locations[i][3],
                    center: {lat: parseFloat(locations[i][1]), lng: parseFloat(locations[i][2])},
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                marker = new google.maps.Marker({
                    position: {lat: parseFloat(locations[i][1]), lng: parseFloat(locations[i][2])},
                    map: maps[i]
                });

                google.maps.event.addListener(marker, 'click', (function (marker, i) {
                    return function () {
                        var url = "https://www.google.com/maps/dir/?api=1&destination=" + locations[i][1] + "," + locations[i][2];
                        window.open(url, "_blank");
                    }
                })(marker, i));

            }

        }

    }
});