define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/modal/alert'
], function ($, wrapper, quote, customer, urlBuilder, url, errorProcessor, alert) {
    'use strict';

    return function (placeOrderService) {
        return wrapper.wrap(placeOrderService, function (originalAction, serviceUrl, payload, messageContainer) {
            if (payload.paymentMethod.method === 'propultech_webpayplusmall') {
                var deferred = $.Deferred();

                // Call the original action to place the order
                originalAction(serviceUrl, payload, messageContainer).done(function (response) {
                    // Create Webpay Plus Mall transaction
                    $.ajax({
                        url: url.build('propultech_webpayplusmall/transaction/create'),
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'order_id': response
                        }
                    }).done(function (resp) {
                        if (resp && resp.url && resp.token_ws) {
                            // Create form and submit to redirect to Webpay
                            var form = $('<form>', {
                                'action': resp.url,
                                'method': 'post'
                            });
                            form.append($('<input>', {
                                'name': 'token_ws',
                                'value': resp.token_ws,
                                'type': 'hidden'
                            }));
                            form.appendTo('body').submit();
                            // Do not resolve deferred to prevent default success redirect
                        } else {
                            var msg = (resp && resp.error) ? resp.error : 'Error al procesar el pago con Webpay Plus Mall';
                            alert({
                                content: msg
                            });
                            // Explicitly reject to block success redirect
                            deferred.reject();
                        }
                    }).fail(function (xhr) {
                        // Show generic error via modal and reject
                        var msg = 'Error al crear la transacción con Webpay Plus Mall.';
                        try {
                            var json = xhr && xhr.responseJSON;
                            if (json && json.message) {
                                msg = json.message;
                            }
                        } catch (e) {}
                        alert({
                            content: msg
                        });
                        deferred.reject();
                    });
                }).fail(function (xhr) {
                    errorProcessor.process(xhr, messageContainer);
                    deferred.reject();
                });

                return deferred.promise();
            }

            return originalAction(serviceUrl, payload, messageContainer);
        });
    };
});
