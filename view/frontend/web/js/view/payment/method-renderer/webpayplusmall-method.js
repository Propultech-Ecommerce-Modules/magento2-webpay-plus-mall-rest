define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Ui/js/modal/alert'
    ],
    function (
        $,
        Component,
        placeOrderAction,
        additionalValidators,
        url,
        alert
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Propultech_WebpayPlusMallRest/payment/webpayplusmall'
            },

            /**
             * Get payment method code
             * @returns {String}
             */
            getCode: function () {
                return 'propultech_webpayplusmall';
            },

            /**
             * Place order handler (Fintoc-style): places Magento order, then creates Webpay Mall transaction and redirects
             */
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                var self = this;

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .done(function (response) {
                            // Create Webpay Plus Mall transaction and redirect
                            $.ajax({
                                url: url.build('propultech_webpayplusmall/transaction/create'),
                                type: 'POST',
                                dataType: 'json',
                                // The controller uses last real order from session; sending order id is optional
                                data: {order_id: response}
                            }).done(function (resp) {
                                if (resp && resp.url && resp.token_ws) {
                                    // Create form and submit to Transbank
                                    var form = $('<form>', {
                                        action: resp.url,
                                        method: 'post'
                                    });
                                    form.append($('<input>', {
                                        name: 'token_ws',
                                        value: resp.token_ws,
                                        type: 'hidden'
                                    }));
                                    form.appendTo('body').submit();
                                } else {
                                    var msg = (resp && (resp.error || resp.message)) ? (resp.error || resp.message) : 'Error al procesar el pago con Webpay Plus Mall';
                                    alert({content: msg});
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            }).fail(function (xhr) {
                                var msg = 'Error al crear la transacción con Webpay Plus Mall.';
                                try {
                                    var json = xhr && xhr.responseJSON;
                                    if (json && (json.message || json.error)) {
                                        msg = json.message || json.error;
                                    }
                                } catch (e) {
                                }
                                alert({content: msg});
                                self.isPlaceOrderActionAllowed(true);
                            });
                        })
                        .fail(function (xhr) {
                            // Magento place order failed
                            try {
                                var json = xhr && xhr.responseJSON;
                                if (json && json.message) {
                                    alert({content: json.message});
                                }
                            } catch (e) {
                            }
                            self.isPlaceOrderActionAllowed(true);
                        })
                        .always(function () {
                            // Keep disabled until we either redirect or error out; errors re-enable above
                        });

                    return true;
                }

                return false;
            },

            /**
             * Get place order deferred object
             * @returns {*}
             */
            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            /**
             * Get payment method data
             * @returns {Object}
             */
            getData: function () {
                return {
                    method: this.getCode(),
                    additional_data: {}
                };
            }
        });
    }
);
