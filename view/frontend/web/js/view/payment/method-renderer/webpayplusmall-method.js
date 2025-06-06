define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery',
        'ko',
        'mage/translate'
    ],
    function (Component, quote, priceUtils, fullScreenLoader, $, ko, $t) {
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
             * Get payment method title
             * @returns {String}
             */
            getTitle: function () {
                return window.checkoutConfig.payment.propultech_webpayplusmall.title;
            },

            /**
             * Get payment method logo URL
             * @returns {String}
             */
            getLogoUrl: function () {
                return require.toUrl('Propultech_WebpayPlusMallRest/images/webpay-logo.png');
            },

            /**
             * Get formatted price
             * @param {Number} price
             * @returns {String}
             */
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },

            /**
             * Get total amount
             * @returns {Number}
             */
            getTotalAmount: function () {
                return quote.totals().grand_total;
            },

            /**
             * Get formatted total amount
             * @returns {String}
             */
            getFormattedTotalAmount: function () {
                return this.getFormattedPrice(this.getTotalAmount());
            },

            /**
             * Get payment method data
             * @returns {Object}
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {}
                };
            },

            /**
             * After place order callback
             * @returns {void}
             */
            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
            }
        });
    }
);
