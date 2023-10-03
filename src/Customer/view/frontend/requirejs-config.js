var config = {
    map: {
        '*': {
            'Magento_Checkout/template/authentication.html': 'Ls_Customer/template/authentication.html',
            'customerFormValidations' : 'Ls_Customer/js/customer-form-validations',
        }
    },
    shim: {
        'Ls_Core/js/datatables.min': ['jquery', 'jquery/ui']
    }
};
