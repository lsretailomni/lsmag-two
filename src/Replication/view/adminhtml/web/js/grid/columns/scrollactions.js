require([
    'jquery',
    'domReady!'
], function ($) {
    $(document).ready(function () {
        var checkExist = setInterval(function () {
            if ($('.ls_scrollbutton_forward').length) {
                $(document).on('click', '.ls_scrollbutton_forward', function (event) {
                    event.preventDefault();
                    if($(".admin__data-grid-wrap").filter(':animated').length>0) {
                        return false;
                    }
                    $('.admin__data-grid-wrap').animate({
                        scrollLeft: "+=200px"
                    }, "slow");
                });
                clearInterval(checkExist);
            }
        }, 1000);

        var checkExistBack = setInterval(function () {
            if ($('.ls_scrollbutton_back').length) {
                $(document).on('click', '.ls_scrollbutton_back', function (event) {
                    event.preventDefault();
                    if($(".admin__data-grid-wrap").filter(':animated').length>0) {
                        return false;
                    }
                    $('.admin__data-grid-wrap').animate({
                        scrollLeft: "-=200px"
                    }, "slow");
                });
                clearInterval(checkExistBack);
            }
        }, 1000);
    });
});