var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/place-order': 'Propultech_WebpayPlusMallRest/js/model/place-order-mixin'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-payment-information': {
                'Propultech_WebpayPlusMallRest/js/action/set-payment-information-mixin': true
            }
        }
    }
};
