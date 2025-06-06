define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setPaymentInformationAction) {
        return wrapper.wrap(setPaymentInformationAction, function (originalAction, messageContainer, paymentData) {
            if (paymentData.method === 'propultech_webpayplusmall') {
                // For Webpay Plus Mall, we don't need to modify the behavior
                // This mixin is here in case we need to add custom logic in the future
            }
            return originalAction(messageContainer, paymentData);
        });
    };
});
