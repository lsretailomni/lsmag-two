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
        var currentProduct = config.currentProduct;
        $(document).ready(function () {
            setTimeout(function () {
                $.ajax({
                    context: '#ls-discounts',
                    url: ajaxUrl,
                    type: "POST",
                    data: {currentProduct: currentProduct}
                }).done(function (data) {
                    $('#ls-discounts').html(data.output);
                    $('#ls-discounts').find('.proactive-discounts-container').trigger('contentUpdated')
                    return true;
                });
            }, 2000);
        });
    };
});
