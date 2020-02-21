var config = {
    map: {
        '*': {
            'lsomni/map-loader': 'Ls_Omni/js/map-loader',
            'lsomni/stores-provider': 'Ls_Omni/js/model/stores-provider',
            'lsomni/map': 'Ls_Omni/js/view/map',
            'lsomni/stock': 'Ls_Omni/js/view/product',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Ls_Omni/js/model/shipping-save-processor/default',
            'OwlCarousel': 'Ls_Omni/js/owl-carousel',
            'loyaltyPoints': 'Ls_Omni/js/loyalty-points',
            'giftCard': 'Ls_Omni/js/gift-card',
            'Magento_Checkout/template/minicart/item/default.html': 'Ls_Omni/template/minicart/item/default.html',
            'Magento_SalesRule/template/summary/discount.html': 'Ls_Omni/template/summary/discount.html',
            'Magento_OfflinePayments/js/view/payment/method-renderer/checkmo-method': 'Ls_Omni/js/view/payment/method-renderer/checkmo-method'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Ls_Omni/js/view/plugin/shipping': true
            }
        }
    },
    shim: {
        'Ls_Omni/js/owl.carousel.min': ['jquery', 'jquery/ui']
    }
};
