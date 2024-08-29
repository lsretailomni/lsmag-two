/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'uiComponent',
        'mage/translate',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/get-totals',
        'jquery'
    ],
    function (Component, $t, customerData, getTotalsAction, $) {
        "use strict";
        var quoteItemData = window.checkoutConfig.quoteItemData;
        return Component.extend({
            defaults: {
                template: 'Ls_Omni/checkout/summary/item/details'
            },
            initialize: function () {
                this._super();
                var self = this,
                    cart = customerData.get('cart');

                cart.subscribe(function (updatedCart) {
                    self.quoteItemData = updatedCart.items;
                    var deferred = $.Deferred();
                    getTotalsAction([], deferred);
                }, this);

            },
            quoteItemData: quoteItemData,
            getValue: function (quoteItem) {
                return quoteItem.name;
            },
            getOriginalPrice: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                if (item.lsPriceOriginal !== '') {
                    return item.lsPriceOriginal;
                }
            },
            getDiscountPrice: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                return item.product_price;
            },
            getDiscountAmount: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                if (item.lsDiscountAmount !== '') {
                    return item.lsDiscountAmount;
                }
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
