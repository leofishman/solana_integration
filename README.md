# Solana Integration (Drupal module)

Provides integration with the Solana blockchain: configurable JSON-RPC client, service container integration, and a settings UI. This is a starting point for building wallet and transaction features.

## Requirements
- Drupal 10 or 11
- PHP 8.3+
- **PHP Sodium extension**

## Installation
1. Place this module under `web/modules/custom/solana_integration` (or appropriate module path).
2. Enable the module: `drush en solana_integration` or via the Drupal UI.
3. Configure at `Administration » Configuration » Web services » Solana Integration`.

## Configuration
- RPC endpoint URL
- Request timeout

## Development notes
- The service `solana_integration.client` wraps basic JSON-RPC calls.
- Extend `src/Service/SolanaClient.php` for higher-level operations.

## License
GPL-2.0-or-later
