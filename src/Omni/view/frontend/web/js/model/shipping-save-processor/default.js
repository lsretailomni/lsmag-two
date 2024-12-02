;define([
    'mage/utils/wrapper',
    'Ls_Omni/js/model/shipping-save-processor/payload-extender'
], function (wrapper, customPayloadExtender) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalFunction, payload) {
            payload = originalFunction(payload);

            customPayloadExtender(payload);

            return payload;
        });
    };
});
