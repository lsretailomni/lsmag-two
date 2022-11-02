define([
    'jquery'
], function ($) {
    'use strict';

    return function (optionConfig) {
        $('#used_for_sort_by').attr("id", "used_for_sort_by_modify");
        return optionConfig;
    }
});
