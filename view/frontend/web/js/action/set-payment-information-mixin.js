define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/modal/alert'
], function ($, wrapper, quote, alert) {
    'use strict';

    return function (setPaymentInformationAction) {
        return wrapper.wrap(setPaymentInformationAction, function (originalAction, messageContainer, paymentData) {
            var result = originalAction(messageContainer, paymentData);

            if (paymentData && paymentData.method === 'propultech_webpayplusmall') {
                // Intercept failures to show modal alert instead of inline messages
                if (result && typeof result.fail === 'function') {
                    result.fail(function (xhr) {
                        var msg = 'Error al guardar la información de pago.';
                        try {
                            var json = xhr && xhr.responseJSON;
                            if (json && json.message) {
                                msg = json.message;
                            }
                        } catch (e) {}
                        alert({
                            content: msg
                        });
                    });
                }
            }

            return result;
        });
    };
});
