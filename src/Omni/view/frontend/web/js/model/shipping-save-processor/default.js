;define([
    'jquery',
    'mage/utils/wrapper',
    'underscore'
], function ($, wrapper, _) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalFunction, payload) {

            payload = originalFunction(payload);

            _.extend(payload.addressInformation, {
                extension_attributes: {
                    'pickup_store': $('#pickup-store').val()
                }
            });

            return payload;
        });
    };
});
