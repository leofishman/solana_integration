/**
 * @file
 * Solana Pay checkout behaviors.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.solanaPayCheckout = {
    attach: function (context, settings) {
      const config = settings.solanaPay;
      if (!config) {
        return;
      }

      const qrContainer = document.getElementById('solana-pay-qr');
      const statusMessage = document.getElementById('solana-pay-status');
      const walletLink = document.getElementById('solana-pay-open');
      const copyButton = document.getElementById('copy-address-btn');
      const addressElement = document.getElementById('merchant-address');
      const copyFeedback = document.getElementById('copy-feedback');

      if (!qrContainer || !statusMessage) {
        return;
      }

      // Handle copy address button
      if (copyButton && addressElement) {
        copyButton.addEventListener('click', function() {
          const address = addressElement.textContent;
          
          navigator.clipboard.writeText(address).then(function() {
            copyFeedback.textContent = Drupal.t('Address copied!');
            copyFeedback.className = 'solana-pay-address__feedback solana-pay-address__feedback--success';
            
            setTimeout(function() {
              copyFeedback.textContent = '';
              copyFeedback.className = 'solana-pay-address__feedback';
            }, 2000);
          }).catch(function(err) {
            copyFeedback.textContent = Drupal.t('Failed to copy address');
            copyFeedback.className = 'solana-pay-address__feedback solana-pay-address__feedback--error';
            console.error('Failed to copy: ', err);
          });
        });
      }

      // Handle manual transaction signature verification
      const verifyButton = document.getElementById('verify-signature-btn');
      const signatureInput = document.getElementById('transaction-signature');
      const verifyFeedback = document.getElementById('verify-feedback');

      if (verifyButton && signatureInput && verifyFeedback) {
        verifyButton.addEventListener('click', function() {
          const signature = signatureInput.value.trim();
          
          if (!signature) {
            verifyFeedback.textContent = Drupal.t('Please enter a transaction signature');
            verifyFeedback.className = 'verify-feedback verify-feedback--error';
            return;
          }

          verifyButton.disabled = true;
          verifyButton.textContent = Drupal.t('Verifying...');
          verifyFeedback.textContent = Drupal.t('Checking transaction on blockchain...');
          verifyFeedback.className = 'verify-feedback verify-feedback--pending';

          fetch(config.statusUrl + '?signature=' + encodeURIComponent(signature), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'confirmed') {
              verifyFeedback.textContent = Drupal.t('Payment verified! Redirecting...');
              verifyFeedback.className = 'verify-feedback verify-feedback--success';
              
              statusMessage.innerHTML = '<div class="solana-pay-status__icon">✅</div>' +
                '<div class="solana-pay-status__message">' + Drupal.t('Payment Confirmed!') + '</div>';
              statusMessage.className = 'solana-pay-status solana-pay-status--success';
              
              setTimeout(function() {
                window.location.href = config.completionUrl;
              }, 2000);
            } else {
              verifyFeedback.textContent = data.message || Drupal.t('Transaction not found or invalid. Please check the signature and network.');
              verifyFeedback.className = 'verify-feedback verify-feedback--error';
              verifyButton.disabled = false;
              verifyButton.textContent = Drupal.t('Verify Payment');
            }
          })
          .catch(error => {
            console.error('Verification error:', error);
            verifyFeedback.textContent = Drupal.t('Verification failed. Please try again.');
            verifyFeedback.className = 'verify-feedback verify-feedback--error';
            verifyButton.disabled = false;
            verifyButton.textContent = Drupal.t('Verify Payment');
          });
        });
      }

      if (typeof QRCode === 'undefined') {
        console.error('QRCode library not loaded');
        statusMessage.textContent = 'Error: QR code library not available.';
        return;
      }

      qrContainer.innerHTML = '';
      new QRCode(qrContainer, {
        text: config.solanaUrl,
        width: 256,
        height: 256,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });

      if (walletLink) {
        walletLink.href = config.solanaUrl;
      }

      let pollCount = 0;
      const maxPolls = 40;
      const pollInterval = 3000;

      const pollStatus = function() {
        if (pollCount >= maxPolls) {
          statusMessage.textContent = Drupal.t('Payment verification timeout. Please refresh the page or contact support if you completed the payment.');
          statusMessage.className = 'solana-pay-status error';
          return;
        }

        pollCount++;

        fetch(config.statusUrl, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'confirmed') {
            statusMessage.innerHTML = '<div class="solana-pay-status__icon">✅</div>' +
              '<div class="solana-pay-status__message">' + Drupal.t('Payment Confirmed!') + '</div>' +
              '<div class="solana-pay-status__help">' + Drupal.t('Your payment has been received. The page will reload in a moment.') + '</div>';
            statusMessage.className = 'solana-pay-status solana-pay-status--success';
            
            // Hide the QR and wallet button
            if (qrContainer) qrContainer.style.display = 'none';
            if (walletLink) walletLink.parentElement.style.display = 'none';
            
            // Redirect to checkout completion page
            setTimeout(function() {
              if (config.completionUrl) {
                window.location.href = config.completionUrl;
              } else {
                window.location.reload();
              }
            }, 2000);
          }
          else if (data.status === 'pending') {
            statusMessage.textContent = Drupal.t('Waiting for payment confirmation... (@count/@max)', {
              '@count': pollCount,
              '@max': maxPolls
            });
            statusMessage.className = 'solana-pay-status pending';
            setTimeout(pollStatus, pollInterval);
          }
          else {
            statusMessage.textContent = Drupal.t('Payment status check failed. Please try again.');
            statusMessage.className = 'solana-pay-status error';
          }
        })
        .catch(error => {
          console.error('Payment status check error:', error);
          if (pollCount < maxPolls) {
            setTimeout(pollStatus, pollInterval);
          }
        });
      };

      setTimeout(pollStatus, pollInterval);

      // Only create manual check button if it doesn't already exist
      if (!document.querySelector('.solana-pay-manual-check')) {
        const manualCheckButton = document.createElement('button');
        manualCheckButton.textContent = Drupal.t("I've paid - Check now");
        manualCheckButton.className = 'button solana-pay-manual-check';
        manualCheckButton.onclick = function(e) {
          e.preventDefault();
          pollCount = 0;
          pollStatus();
        };
        statusMessage.parentNode.insertBefore(manualCheckButton, statusMessage.nextSibling);
      }
    }
  };

})(Drupal, drupalSettings);
