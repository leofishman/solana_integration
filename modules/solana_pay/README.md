# Solana Pay for Drupal Commerce

Payment gateway integration for accepting Solana (SOL) payments via Solana Pay protocol.

## Features

- QR code generation for mobile wallet scanning
- Deep link support for desktop wallets
- Real-time payment verification via Solana RPC
- Automatic order completion on payment confirmation
- Support for Devnet, Testnet, and Mainnet

## Requirements

- Drupal Commerce 3.x
- Solana Integration module (parent module)
- PHP Sodium extension
- Valid Solana wallet address for receiving payments

## Installation

1. Enable the module:
   ```bash
   ddev drush en solana_pay -y
   ddev drush cr
   ```

2. Configure merchant wallet address:
   - Go to `/admin/config/services/solana-integration`
   - Set your Solana wallet address
   - Select the network endpoint (devnet for testing, mainnet for production)

3. Add payment gateway to your store:
   - Go to `/admin/commerce/config/payment-gateways`
   - Click "Add payment gateway"
   - Select "Solana Pay"
   - Configure and save

## How It Works

### Checkout Flow

1. **Customer initiates checkout**: Selects Solana Pay as payment method
2. **Payment URL generation**: System generates unique Solana Pay URL with reference key
3. **QR code display**: Customer sees QR code and "Open in Wallet" button
4. **Payment submission**: Customer scans QR or clicks button to open wallet and approve transaction
5. **Status polling**: Browser polls every 3 seconds to check payment status on blockchain
6. **Verification**: Backend verifies transaction using reference key and expected amount
7. **Completion**: Once confirmed, payment is marked complete and order proceeds

### Technical Details

- **Reference Key**: Unique Base58-encoded public key generated per payment
- **Verification**: Checks Solana blockchain for transaction with matching reference and amount
- **Polling**: JavaScript polls status endpoint up to 40 times (2 minutes)
- **Manual Check**: "I've paid" button allows immediate re-check

## Configuration

### Merchant Wallet

Set your Solana wallet address in the main Solana Integration settings:
```bash
ddev drush config:set solana_integration.settings merchant_wallet_address YOUR_WALLET_ADDRESS
```

### Network Selection

Choose the appropriate network:
- **Devnet**: For testing (use devnet SOL from faucet)
- **Testnet**: For integration testing
- **Mainnet**: For production (real SOL)

## Testing

### On Devnet

1. Set endpoint to devnet in Solana Integration settings
2. Use a devnet-funded wallet
3. Get devnet SOL from: https://faucet.solana.com/
4. Place a test order and complete payment

### Payment Verification

Check payment status manually:
```bash
ddev drush solana:verify-payment REFERENCE_KEY AMOUNT
```

## Troubleshooting

### QR Code Not Appearing

- Check browser console for JavaScript errors
- Verify QRCode library loaded from CDN
- Clear cache: `ddev drush cr`

### Payment Not Confirming

- Ensure transaction was submitted and confirmed on blockchain
- Check network selection matches wallet network
- Verify merchant wallet address is correct
- Check Drupal logs: `ddev drush watchdog:tail`

### "Payment reference not found"

- Payment entity may not have saved properly
- Check database for commerce_payment records
- Verify Payment entity has remote_id field populated

## Files Structure

```
solana_pay/
├── src/
│   ├── Controller/
│   │   └── SolanaPayStatusController.php    # Status polling endpoint
│   └── Plugin/
│       └── Commerce/
│           ├── PaymentGateway/
│           │   └── SolanaPay.php             # Main gateway plugin
│           └── PaymentMethodType/
│               └── SolanaPay.php             # Payment method type
├── js/
│   └── checkout.js                           # QR rendering and polling
├── css/
│   └── checkout.css                          # Checkout page styles
├── solana_pay.info.yml                       # Module definition
├── solana_pay.libraries.yml                  # Asset libraries
├── solana_pay.routing.yml                    # Routes definition
└── README.md                                 # This file
```

## API Endpoints

- `GET /solana-pay/status/{commerce_payment}` - Check payment status
- Returns JSON: `{"status": "pending|confirmed|error", "message": "..."}`

## Security Considerations

- Reference keys are one-time use per payment
- Payment amount verification prevents partial payments
- Blockchain finality ensures non-reversible transactions
- Access control on status endpoint checks order ownership

## Support

For issues related to:
- Payment gateway: Check this module's code
- Solana RPC: Check parent solana_integration module
- Blockchain issues: Verify on Solana explorer

## License

GPL-2.0-or-later
