;define([
    'mage/utils/wrapper',
    'Ls_Omni/js/model/shipping-save-processor/payload-extender'
], function (wrapper, customPayloadExtender) {
    'use strict';

    return function (shippingSaveProcessor) {
        shippingSaveProcessor.payloadExtender = wrapper.wrap(
            shippingSaveProcessor.payloadExtender,
            function (originalFunction, payload) {
                originalFunction(payload);

                customPayloadExtender(payload);
            }
        );

        return shippingSaveProcessor;
    };
});
