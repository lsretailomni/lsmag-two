define([
    'jquery',
    'mage/validation',
    'lsomni/map-loader',
    'mage/url',
    'ko',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert'
], function ($, _, MapLoader, url, ko, modal, alert) {
    'use strict';

    function initializer(config, node) {
        var dataForm = $(node);
        var ignore = null;
        $(document).on("click", "a.checkavailability", function () {
            var validOrNotValid = dataForm.validation('isValid'); //validates form and returns boolean
            if (validOrNotValid) {
                var formData = dataForm.data();
                var sku = formData.productSku;
                var selectedSimpleProductId = $("input[name=selected_configurable_option]").val();
                var controllerUrl = getBaseUrl("omni/stock/product" + "?sku=" + sku + "&id=" + selectedSimpleProductId);
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
                        var stores = $.parseJSON(data.stocks);
                        if (stores.totalRecords > 0) {
                            getPopUp(stores).openModal();
                        } else {
                            alert({
                                title: data.title,
                                content: data.content,
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
    };

    function getPopUp(stores) {
        var self = this,
            buttons;

        if (!popUp) {
            MapLoader.done($.proxy(initMap(stores), this)).fail(function () {
                console.error("ERROR: Google maps library failed to load");
            });
            var popUp = modal({
                'responsive': true,
                'innerScroll': true,
                'buttons': [],
                'type': 'slide',
                'modalClass': 'mc_cac_map',
                closed: function () {
                    getPopUp(stores);
                }
            }, $('#map-canvas'));
        }
        return popUp;
    }

    function getBaseUrl(param) {
        return url.build(param);
    }

    function initMap(stores) {
        var self = this;
        var myLatLng = {
            lat: parseFloat(defaultLat),
            lng: parseFloat(defaultLong)
        };
        var map = new google.maps.Map(document.getElementById('map-canvas'), {
            zoom: parseInt(defaultZoom),
            center: myLatLng
        });
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
                    infoWindow.setContent('<div class="infowindow"><h3>' + store.Name + '</h3><br /><br /><strong>Address: </strong>' + store.Street +', '+ store.City+' '+ store.State +' '+ store.ZipCode+' '+ store.Country+' </div>');
                    infoWindow.open(map, marker);
                });
            })(marker, store);
        });
    }

    return function (config, node) {
        initializer(config, node);
    }
});
