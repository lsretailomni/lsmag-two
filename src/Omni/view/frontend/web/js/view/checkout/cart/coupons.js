define([
    "jquery",
    "jquery/ui",
    "OwlCarousel"
], function ($) {
    "use strict";
    return function main(config, element)
    {
        var $element = $(element);
        var ajaxUrl = config.ajaxUrl;
        $(document).ready(function () {
            setTimeout(function () {
                $.ajax({
                    context: '#ls-coupons',
                    url: ajaxUrl,
                    type: "POST"
                }).done(function (data) {
                    $('#ls-coupons').html(data.output);
                    return true;
                });
            }, 2000);
        });
    };
});
