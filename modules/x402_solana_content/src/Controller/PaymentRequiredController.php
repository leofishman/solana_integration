<?php

namespace Drupal\x402_solana_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Cache\CacheableResponse;

class PaymentRequiredController extends ControllerBase {

  public function paymentRequired(Node $node) {
    // Get the Solana fields from the node using the subscriber service
    $subscriber = \Drupal::service('x402_solana_content.node_access_subscriber');
    $solanaFields = $subscriber->getSolanaFields($node);
    
    $config = $this->config('x402_solana_content.settings');
    
    $build = [
      '#theme' => 'x402_solana_payment_required',
      '#title' => $node->getTitle(),
      '#solana_fields' => $solanaFields,
      '#node_id' => $node->id(),
      '#attached' => [
        'library' => [
          'x402_solana_content/wallet_integration',
        ],
        'drupalSettings' => [
          'x402Solana' => [
            'nodeId' => $node->id(),
            'rpcEndpoint' => $config->get('rpc_endpoint') ?: 'https://api.mainnet-beta.solana.com',
            'network' => $config->get('network') ?: 'mainnet-beta',
            'fields' => $solanaFields,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user.permissions', 'session'],
        'tags' => ['node:' . $node->id(), 'x402_solana_content'],
        'max-age' => 0, // No caching for payment pages
      ],
    ];

    // Return the render array - Drupal will handle the 200 response
    // The 402 status is handled by the redirect from the event subscriber
    return $build;
  }
}