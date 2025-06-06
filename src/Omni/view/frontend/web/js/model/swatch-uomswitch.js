define([
    'jquery',
    'mage/utils/wrapper',
    'mage/translate',
], function ($, wrapper, $t) {
    'use strict';

    return function (targetModule) {
        var updatePrice = targetModule.prototype._UpdatePrice;
        var _UpdateOnClick = targetModule.prototype._OnClick;
        targetModule.prototype.configurableUom = $('div.lsr_uom .swatch-attribute-selected-option').html();
        var updatePriceWrapper = wrapper.wrap(updatePrice, function (original) {
            var allSelected = true;
            for (var i = 0; i < this.options.jsonConfig.attributes.length; i++) {
                if (!$('div.product-info-main .product-options-wrapper .swatch-attribute.' + this.options.jsonConfig.attributes[i].code).attr('option-selected')) {
                    allSelected = false;
                }
            }
            var uomQty = this.configurableUom;
            var text = '';
            if (allSelected) {
                var products = this._CalcProducts();
                uomQty = this.options.jsonConfig.uomQty[products.slice().shift()];
                    text = $t('contains %1 quantity');
                    text = text.replace('%1', uomQty);
            }
            $('div.lsr_uom .swatch-attribute-selected-option').html(text);
            return original();
        });

        var _UpdateOnClickWrapper = wrapper.wrap(_UpdateOnClick, function (original, $this, widget) {
            original($this, widget);
            $('input[name="selected_configurable_option"]').val(widget.getProductId());
        });

        targetModule.prototype._UpdatePrice = updatePriceWrapper;
        targetModule.prototype._OnClick = _UpdateOnClickWrapper;
        return targetModule;
    };
});
