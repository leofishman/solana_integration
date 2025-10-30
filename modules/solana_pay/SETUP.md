# Solana Pay Setup Guide

## Quick Start

### 1. Enable the Module

```bash
ddev drush en solana_pay -y
ddev drush cr
```

### 2. Configure Merchant Wallet

Go to `/admin/config/services/solana-integration` and set:
- **Merchant Wallet Address**: Your Solana wallet address (e.g., `7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU`)
- **Default Endpoint**: Select `devnet` for testing, `mainnet` for production

Or via Drush:
```bash
ddev drush config:set solana_integration.settings merchant_wallet_address YOUR_WALLET_ADDRESS
ddev drush config:set solana_integration.settings default_endpoint devnet
ddev drush cr
```

### 3. Add Payment Gateway

1. Go to `/admin/commerce/config/payment-gateways`
2. Click **Add payment gateway**
3. Fill in:
   - Label: `Solana Pay`
   - Plugin: Select `Solana Pay`
4. Save

### 4. Test with Devnet

1. Make sure your endpoint is set to `devnet`
2. Get devnet SOL from faucet: https://faucet.solana.com/
3. Create a test order in your store
4. Select Solana Pay at checkout
5. Scan QR or click "Open in Wallet"
6. Approve transaction in your wallet
7. Wait for confirmation (page will auto-redirect)

## How the Flow Works

```
Customer → Checkout → Solana Pay Selected
            ↓
        QR Code Displayed + "Open Wallet" Button
            ↓
        Customer Scans/Clicks → Opens Wallet App
            ↓
        Customer Approves Transaction
            ↓
        Transaction Submitted to Blockchain
            ↓
        JavaScript Polls Every 3s (max 2 min)
            ↓
        Backend Verifies on Blockchain
            ↓
        Payment Confirmed → Order Complete
```

## Troubleshooting

### Module Won't Enable

```bash
# Check dependencies
ddev composer require drupal/commerce
ddev drush en commerce_payment -y

# Enable parent module first
ddev drush en solana_integration -y

# Then enable Solana Pay
ddev drush en solana_pay -y
```

### QR Code Not Showing

1. Check browser console for errors
2. Verify library loaded: Look for `qrcode.min.js` in Network tab
3. Clear cache: `ddev drush cr`
4. Check permissions on js/css directories

### Payment Not Verifying

1. Ensure transaction was actually submitted and confirmed
2. Check the blockchain explorer:
   - Devnet: https://explorer.solana.com/?cluster=devnet
   - Mainnet: https://explorer.solana.com/
3. Verify merchant wallet address matches
4. Check network selection (devnet vs mainnet)
5. View logs: `ddev drush watchdog:tail`

### "Payment reference not found"

The payment entity didn't save the reference. Check:
```bash
# View recent payments
ddev drush sql:query "SELECT payment_id, remote_id, state FROM commerce_payment ORDER BY payment_id DESC LIMIT 5;"

# Should see remote_id populated with Base58 string
```

## Testing Checklist

- [ ] Module enables without errors
- [ ] Payment gateway appears in Commerce settings
- [ ] Checkout shows Solana Pay option
- [ ] QR code renders on payment page
- [ ] "Open in Wallet" button works
- [ ] Status polling starts automatically
- [ ] Payment confirms after wallet approval
- [ ] Order completes successfully
- [ ] Cancel link works and returns to cart

## Production Deployment

Before going live:

1. **Switch to Mainnet**:
   ```bash
   ddev drush config:set solana_integration.settings default_endpoint mainnet
   ```

2. **Use Production Wallet**: Update merchant_wallet_address to your production wallet

3. **Test with Small Amount**: Create test order with minimal SOL amount

4. **Monitor Logs**:
   ```bash
   ddev drush watchdog:show --type=solana_integration
   ```

5. **Consider Custom RPC**: For better reliability, use a paid RPC provider like Helius, QuickNode, or Alchemy

## Advanced Configuration

### Custom RPC Endpoint

Add custom endpoint in Solana Integration settings:
```yaml
endpoints:
  custom:
    enabled: true
    url: 'https://your-custom-rpc.com'
    label: 'Custom RPC'
```

### SPL Token Payments

To accept USDC or other SPL tokens, modify `SolanaClient::generatePaymentRequest()`:
```php
// For USDC on mainnet
$spl_token = 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v';
return $this->buildPaymentRequestUrl($recipient, $amount, $spl_token, $reference_key, $label, $message);
```

### Webhook Support

For async notifications without polling, implement the Solana Pay `link` parameter and transaction request flow.

## Support Resources

- Solana Pay Spec: https://docs.solanapay.com/spec
- Drupal Commerce Docs: https://docs.drupalcommerce.org/
- Solana RPC API: https://docs.solana.com/api/http

## Common Issues

| Issue | Solution |
|-------|----------|
| "Sodium extension not available" | Install PHP sodium: `apt-get install php-sodium` |
| Polling times out | Increase $maxPolls in checkout.js or check RPC |
| Wrong amount transferred | Verify SOL to lamports conversion (1 SOL = 1e9 lamports) |
| QR too small | Edit CSS: `.solana-pay-qr` width/height |
