define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/model/messageList'
], function ($, wrapper, quote, customer, urlBuilder, url, errorProcessor, messageList) {
    'use strict';

    return function (placeOrderService) {
        return wrapper.wrap(placeOrderService, function (originalAction, serviceUrl, payload, messageContainer) {
            if (payload.paymentMethod.method === 'propultech_webpayplusmall') {
                var deferred = $.Deferred();

                // Call the original action to place the order
                originalAction(serviceUrl, payload, messageContainer).done(function (response) {
                    // Redirect to Webpay Plus Mall
                    $.ajax({
                        url: url.build('propultech_webpayplusmall/transaction/create'),
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'order_id': response
                        }
                    }).done(function (response) {
                        if (response.url && response.token_ws) {
                            // Create form to redirect to Webpay
                            var form = $('<form>', {
                                'action': response.url,
                                'method': 'post'
                            });
                            form.append($('<input>', {
                                'name': 'token_ws',
                                'value': response.token_ws,
                                'type': 'hidden'
                            }));
                            form.appendTo('body').submit();
                        } else if (response.error) {
                            messageList.addErrorMessage({
                                message: response.error
                            });
                            deferred.reject();
                        } else {
                            messageList.addErrorMessage({
                                message: 'Error al procesar el pago con Webpay Plus Mall'
                            });
                            deferred.reject();
                        }
                    }).fail(function (response) {
                        errorProcessor.process(response, messageContainer);
                        deferred.reject();
                    });
                }).fail(function (response) {
                    errorProcessor.process(response, messageContainer);
                    deferred.reject();
                });

                return deferred.promise();
            }

            return originalAction(serviceUrl, payload, messageContainer);
        });
    };
});
