/**
 * @file
 * Handles Solana Pay QR code generation and payment verification polling.
 */

(function ($, Drupal, drupalSettings, once) { // <-- `once` is now passed in
  'use strict';

  Drupal.behaviors.solanaPayQr = {
    attach: function (context, settings) {
      // Use the new `once()` function to find unprocessed placeholders.
      const placeholders = once('solana-pay-qr', '.solana-pay-qr-placeholder', context);

      placeholders.forEach(function (placeholder) {
        const uniqueId = placeholder.getAttribute('id');
        const solanaPaySettings = drupalSettings.solanaPay[uniqueId];

        if (!solanaPaySettings) {
          return;
        }

        const paymentRequestUrl = solanaPaySettings.paymentRequestUrl;
        const verificationUrl = solanaPaySettings.verificationUrl;
        const $placeholder = $(placeholder);

        // 1. Generate and display the QR Code.
        try {
          const qr = qrcode(0, 'M');
          qr.addData(paymentRequestUrl);
          qr.make();
          $placeholder.html(qr.createImgTag(5, 20));
          $placeholder.append('<div class="solana-pay-status">' + Drupal.t('Scan to pay with your Solana wallet.') + '</div>');
        } catch (e) {
          $placeholder.html(Drupal.t('Error generating QR code.'));
          return;
        }

        // 2. Start polling for payment confirmation.
        const interval = setInterval(function () {
          $.ajax({
            url: verificationUrl,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
              if (response.status === 'confirmed') {
                clearInterval(interval);
                $placeholder.html('<div class="solana-pay-success">' + Drupal.t('Payment confirmed! Thank you.') + '</div>');
              }
            }
          });
        }, 5000);
      });
    }
  };

}(jQuery, Drupal, drupalSettings, once)); // <-- Pass `once` in here