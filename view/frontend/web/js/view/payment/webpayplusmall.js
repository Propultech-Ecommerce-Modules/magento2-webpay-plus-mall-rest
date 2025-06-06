define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'propultech_webpayplusmall',
                component: 'Propultech_WebpayPlusMallRest/js/view/payment/method-renderer/webpayplusmall-method'
            }
        );
        return Component.extend({});
    }
);
