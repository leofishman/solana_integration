// js/wallet_integration.js
(function () {
  'use strict';

  function initializeWalletIntegration() {
    const settings = window.drupalSettings?.x402Solana;
    
    if (!settings) {
      console.error('x402Solana settings not found');
      return;
    }

    let provider = null;
    let publicKey = null;
    let connected = false;

    // Check for wallet providers on page load
    detectWallets();

    // Wallet connection handlers
    document.querySelectorAll('.wallet-button').forEach(button => {
      button.addEventListener('click', connectWallet);
    });

    // Copy address handlers
    document.querySelectorAll('.copy-address').forEach(button => {
      button.addEventListener('click', copyAddress);
    });

    // Payment handler
    const paymentButton = document.getElementById('make-payment');
    if (paymentButton) {
      paymentButton.addEventListener('click', makePayment);
    }

    function detectWallets() {
      const phantomButton = document.getElementById('connect-phantom');
      const solflareButton = document.getElementById('connect-solflare');
      const backpackButton = document.getElementById('connect-backpack');

      // Check for Phantom
      if (window?.phantom?.solana?.isPhantom) {
        provider = window.phantom.solana;
        phantomButton.style.display = 'block';
        phantomButton.disabled = false;
      } else {
        phantomButton.style.display = 'none';
      }

      // Check for Solflare
      if (window?.solflare?.isSolflare) {
        provider = window.solflare;
        solflareButton.style.display = 'block';
        solflareButton.disabled = false;
      } else {
        solflareButton.style.display = 'none';
      }

      // Check for Backpack
      if (window?.backpack) {
        provider = window.backpack;
        backpackButton.style.display = 'block';
        backpackButton.disabled = false;
      } else {
        backpackButton.style.display = 'none';
      }

      // If no wallets detected, show message
      if (!provider) {
        updateWalletStatus('No Solana wallet detected. Please install Phantom, Solflare, or Backpack.', 'error');
      }
    }

    async function connectWallet(event) {
      const walletType = event.target.id.replace('connect-', '');
      
      try {
        // Re-detect provider in case it was installed after page load
        detectWallets();
        
        if (!provider) {
          updateWalletStatus('No wallet provider available. Please install a Solana wallet.', 'error');
          return;
        }

        updateWalletStatus('Connecting to wallet...');

        // Different wallets have different connection methods
        if (provider.isPhantom) {
          // Phantom wallet
          try {
            const response = await provider.connect();
            publicKey = response.publicKey.toString();
            connected = true;
          } catch (error) {
            // User might have rejected the connection
            if (error.code === 4001) {
              updateWalletStatus('Connection rejected by user', 'error');
            } else {
              throw error;
            }
            return;
          }
        } else if (provider.isSolflare) {
          // Solflare wallet
          try {
            await provider.connect();
            publicKey = provider.publicKey.toString();
            connected = true;
          } catch (error) {
            updateWalletStatus('Solflare connection failed: ' + error.message, 'error');
            return;
          }
        } else if (window.backpack) {
          // Backpack wallet
          try {
            const response = await window.backpack.connect();
            publicKey = response.publicKey.toString();
            connected = true;
          } catch (error) {
            updateWalletStatus('Backpack connection failed: ' + error.message, 'error');
            return;
          }
        }

        if (connected && publicKey) {
          updateWalletStatus('Connected: ' + publicKey.substring(0, 8) + '...', 'success');
          if (paymentButton) {
            paymentButton.disabled = false;
          }
          
          // Listen for account changes
          if (provider.on) {
            provider.on('accountChanged', (newPublicKey) => {
              if (newPublicKey) {
                publicKey = newPublicKey.toString();
                updateWalletStatus('Account changed: ' + publicKey.substring(0, 8) + '...', 'info');
              } else {
                // User disconnected
                connected = false;
                publicKey = null;
                updateWalletStatus('Wallet disconnected', 'error');
                if (paymentButton) {
                  paymentButton.disabled = true;
                }
              }
            });
          }
        }

      } catch (error) {
        console.error('Wallet connection failed:', error);
        updateWalletStatus('Connection failed: ' + error.message, 'error');
      }
    }

    function copyAddress(event) {
      const address = event.target.dataset.address;
      navigator.clipboard.writeText(address).then(() => {
        const originalText = event.target.textContent;
        event.target.textContent = 'Copied!';
        setTimeout(() => {
          event.target.textContent = originalText;
        }, 2000);
      }).catch(err => {
        console.error('Failed to copy address:', err);
      });
    }

    async function makePayment() {
      if (!connected || !publicKey || !provider) {
        updateWalletStatus('Please connect your wallet first', 'error');
        return;
      }

      const verificationEl = document.getElementById('payment-verification');
      const paymentButton = document.getElementById('make-payment');
      
      if (verificationEl) verificationEl.style.display = 'block';
      if (paymentButton) paymentButton.disabled = true;
      updateWalletStatus('Preparing transaction...');

      try {
        // Get the first payment option (you can enhance this to let user choose)
        const paymentOption = settings.fields[0];
        if (!paymentOption) {
          throw new Error('No payment options available');
        }

        // Create and send transaction
        const transaction = await createPaymentTransaction(paymentOption, publicKey);
        const signature = await sendTransaction(transaction);
        
        updateWalletStatus('Transaction sent. Verifying...', 'info');
        
        // Verify the transaction
        await verifyTransaction(signature);
        
        // Grant access
        await grantAccess(settings.nodeId, signature);
        
      } catch (error) {
        console.error('Payment failed:', error);
        updateWalletStatus('Payment failed: ' + error.message, 'error');
        if (verificationEl) verificationEl.style.display = 'none';
        if (paymentButton) paymentButton.disabled = false;
      }
    }

    async function createPaymentTransaction(paymentOption, fromPublicKey) {
      // This is a simplified transaction creation
      // In reality, you'd use @solana/web3.js to create proper transactions
      
      const connection = new window.solanaWeb3.Connection(
        settings.rpcEndpoint, 
        'confirmed'
      );

      const transaction = new window.solanaWeb3.Transaction().add(
        window.solanaWeb3.SystemProgram.transfer({
          fromPubkey: new window.solanaWeb3.PublicKey(fromPublicKey),
          toPubkey: new window.solanaWeb3.PublicKey(paymentOption.address),
          lamports: window.solanaWeb3.LAMPORTS_PER_SOL * paymentOption.amount, // Convert SOL to lamports
        })
      );

      // Get recent blockhash
      const { blockhash } = await connection.getRecentBlockhash();
      transaction.recentBlockhash = blockhash;
      transaction.feePayer = new window.solanaWeb3.PublicKey(fromPublicKey);

      return transaction;
    }

    async function sendTransaction(transaction) {
      if (!provider) {
        throw new Error('Wallet not connected');
      }

      // Sign and send transaction
      const { signature } = await provider.signAndSendTransaction(transaction);
      return signature;
    }

    async function verifyTransaction(signature) {
      const connection = new window.solanaWeb3.Connection(settings.rpcEndpoint);
      
      // Wait for confirmation
      const confirmation = await connection.confirmTransaction(signature, 'confirmed');
      
      if (confirmation.value.err) {
        throw new Error('Transaction failed: ' + confirmation.value.err);
      }
      
      return true;
    }

    async function grantAccess(nodeId, signature) {
      try {
        const response = await fetch('/x402-solana/grant-access', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': await getCsrfToken()
          },
          body: JSON.stringify({
            nodeId: nodeId,
            signature: signature,
          }),
        });

        if (response.ok) {
          updateWalletStatus('Payment verified! Redirecting...', 'success');
          setTimeout(() => {
            // Redirect back to the original node page
            window.location.href = '/node/' + nodeId;
          }, 2000);
        } else {
          throw new Error('Access grant failed: ' + response.statusText);
        }
      } catch (error) {
        throw new Error('Network error: ' + error.message);
      }
    }

    async function getCsrfToken() {
      // Simple CSRF token fetch - you might need to adjust this for Drupal
      try {
        const response = await fetch('/session/token');
        if (response.ok) {
          return await response.text();
        }
      } catch (error) {
        console.warn('Could not fetch CSRF token:', error);
      }
      return '';
    }

    function updateWalletStatus(message, type = 'info') {
      const statusEl = document.getElementById('wallet-status');
      if (statusEl) {
        statusEl.textContent = message;
        statusEl.className = 'wallet-status ' + type;
      }
    }
  }

  // Load solana-web3.js if not already loaded
  function loadSolanaWeb3() {
    return new Promise((resolve, reject) => {
      if (window.solanaWeb3) {
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://unpkg.com/@solana/web3.js@latest/lib/index.iife.js';
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  // Initialize when everything is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
      try {
        await loadSolanaWeb3();
        initializeWalletIntegration();
      } catch (error) {
        console.error('Failed to load Solana Web3:', error);
      }
    });
  } else {
    loadSolanaWeb3().then(initializeWalletIntegration).catch(console.error);
  }

})();