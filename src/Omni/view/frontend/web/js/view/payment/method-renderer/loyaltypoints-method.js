
define([
    'ko',
    'jquery',
    'mage/storage',
    'mage/translate',
    'Magento_Checkout/js/view/payment/default'
], function (ko,
             $,
             storage,
             $t,
             Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/payment/loyaltypoints'
        },
        getPointValue: function () {
            //return 'Dynamic Points';
            storage.get(
                'omni/ajax/points',
                false
            ).done(
                function (response) {
                    //TODO bad approach, fix this according to Knockout JS structure.
                    //alert(response);
                    $('#somethingas').html(response);

                   //

                }
            ).fail(
                function (response) {
                }
            );
            //console.log(resp);


            //return resp;

        },
        /*
        @We are not using this function, it was/is for testing only.
         */
        getMailingAddress: function () {
            return 'Something';
        }

    });
});