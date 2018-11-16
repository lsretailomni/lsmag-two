define(['jquery'], function ($) {
    var google_maps_loaded_def = null;
    if (!google_maps_loaded_def) {

        google_maps_loaded_def = $.Deferred();

        window.google_maps_loaded = function () {
            google_maps_loaded_def.resolve(google.maps);
        }
        var key = googleMapApiKey;
        require(['https://maps.googleapis.com/maps/api/js?key=' + key + '&callback=google_maps_loaded'], function () {
        }, function (err) {
            google_maps_loaded_def.reject();
        });
    }
    return google_maps_loaded_def.promise();
});