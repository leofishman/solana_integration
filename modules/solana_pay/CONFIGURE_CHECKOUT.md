# Configure Checkout Flow for Solana Pay

After installing the module, you need to configure your checkout flow to use the Solana Pay checkout pane.

## Option 1: Manual Configuration via UI

1. Go to `/admin/commerce/config/checkout-flows`
2. Edit your checkout flow (usually "Default")
3. Find the **"payment"** step
4. **Disable** the "Payment process" pane (drag it to "Disabled" section)
5. **Enable** the "Solana Pay Checkout" pane (drag it to "payment" step)
6. Adjust weight so "Solana Pay Checkout" appears where you want it
7. Save

## Option 2: Configuration via YAML

Create or edit your checkout flow config at:
`config/sync/commerce_checkout.commerce_checkout_flow.default.yml`

Update the `panes` section:

```yaml
configuration:
  panes:
    # ... other panes ...
    
    # DISABLE the standard payment_process pane
    payment_process:
      display_label: null
      step: _disabled
      weight: 4
      wrapper_element: container
      capture: false
    
    # ENABLE the Solana Pay checkout pane
    solana_pay_checkout:
      display_label: null
      step: payment
      weight: 4
      wrapper_element: container
```

Then import the config:
```bash
drush cim -y
drush cr
```

## Option 3: Conditional Display (Keep Both)

If you want to support multiple payment gateways, **keep both panes**:

- `payment_process` - Will handle non-Solana gateways
- `solana_pay_checkout` - Will automatically show only when Solana Pay is selected

The `solana_pay_checkout` pane has an `isVisible()` method that only shows it when the Solana Pay gateway is selected.

## Testing

1. Clear cache
2. Go to checkout
3. Select "Pay with Solana" 
4. Continue to the payment step
5. You should see the QR code directly in the checkout flow

## Troubleshooting

### QR Code Not Showing

Check that:
- Module is enabled: `drush pm:list | grep solana`
- Cache is cleared: `drush cr`
- Pane is in the correct step
- Payment gateway is set on the order

### Still Using PaymentProcess

If the system is still trying to use the offsite redirect:
- Make sure `solana_pay_checkout` pane is enabled
- Set `payment_process` step to `_disabled`
- Clear cache again
