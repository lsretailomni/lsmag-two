/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'uiComponent',
        'mage/translate'
    ],
    function (Component,$t) {
        "use strict";
        var quoteItemData = window.checkoutConfig.quoteItemData;
        return Component.extend({
            defaults: {
                template: 'Ls_Omni/checkout/summary/item/details'
            },
            quoteItemData: quoteItemData,
            getValue: function (quoteItem) {
                return quoteItem.name;
            },
            getOriginalPrice: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                return item.originalprice;
            },
            getDiscountPrice: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                return item.discountprice;
            },
            getDiscountAmount: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                return '( '+ $t(item.discountamounttext)+' '+item.discountamount+' )';
            },
            getItem: function (item_id) {
                var itemElement = null;
                _.each(this.quoteItemData, function (element, index) {
                    if (element.item_id == item_id) {
                        itemElement = element;
                    }
                });
                return itemElement;
            }
        });
    }
);