<?php

namespace Drupal\x402_solana_content\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\Entity\Node;

/**
 * Subscriber to handle 402 responses for Solana-protected content.
 */
class NodeAccessSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(
    AccountProxyInterface $current_user, 
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkNodeAccess', 30];
    return $events;
  }

  /**
   * Check if the current request is for a Solana-protected node.
   */
public function checkNodeAccess(RequestEvent $event) {
  $request = $event->getRequest();
  
  // Skip if this is already the payment required route to avoid infinite redirect
  if ($request->attributes->get('_route') === 'x402_solana_content.payment_required') {
    return;
  }
  
  // Only process if this is a node page
  if (!$request->attributes->has('node')) {
    return;
  }

  $node = $request->attributes->get('node');
  
  // Ensure we have a node entity
  if (!$node instanceof Node) {
    return;
  }

  // Skip if user has bypass permission
  if ($this->currentUser->hasPermission('bypass x402 solana payment')) {
    return;
  }

  // Get all Solana fields and check if any are enabled
  $solanaFields = $this->getSolanaFields($node);
  
  if (!empty($solanaFields) && !$this->hasPaidForContent($node, $solanaFields)) {
    $response = $this->buildPaymentRequiredResponse($node, $solanaFields);
    $event->setResponse($response);
  }
}

  /**
   * Check if node has Solana payment protection enabled.
   */
  protected function isSolanaProtected(Node $node) {
    $solanaFields = $this->getSolanaFields($node);
    return !empty($solanaFields);
  }

  /**
   * Check if current user has paid for this content.
   */
  protected function hasPaidForContent(Node $node, array $solanaFields = []) {
    // If no fields provided, check if any exist
    if (empty($solanaFields)) {
      $solanaFields = $this->getSolanaFields($node);
      if (empty($solanaFields)) {
        return TRUE; // No protection, allow access
      }
    }

    // Check session for temporary access grants
    $session = \Drupal::request()->getSession();
    $accessKey = 'x402_solana_access_' . $node->id();
    if ($session->has($accessKey)) {
      $accessData = $session->get($accessKey);
      if ($accessData['expires'] > time()) {
        return TRUE;
      }
      // Remove expired session data
      $session->remove($accessKey);
    }

    // Check database for permanent access grants
    // TODO: Implement database check for verified payments
    // This would query your payment tracking system

    return FALSE;
  }

  /**
   * Build a 402 Payment Required response.
   */
//   protected function buildPaymentRequiredResponse(Node $node, array $solanaFields) {
//     $config = $this->configFactory->get('x402_solana_content.settings');
    
//     $build = [
//       '#theme' => 'x402_solana_payment_required',
//       '#title' => $node->getTitle(),
//       '#solana_fields' => $solanaFields,
//       '#node_id' => $node->id(),
//       '#attached' => [
//         'library' => [
//           'x402_solana_content/wallet_integration',
//         ],
//         'drupalSettings' => [
//           'x402Solana' => [
//             'nodeId' => $node->id(),
//             'rpcEndpoint' => $config->get('rpc_endpoint') ?: 'https://api.mainnet-beta.solana.com', //TODO check from default network configurarion
//             'network' => $config->get('network') ?: 'mainnet-beta', //TODO check from default network configurarion
//             'fields' => $solanaFields,
//           ],
//         ],
//       ],
//     ];

//     // Add cacheability metadata
//     $cacheableMetadata = new CacheableMetadata();
//     $cacheableMetadata->addCacheableDependency($node);
//     $cacheableMetadata->addCacheContexts(['user.permissions', 'session']);
//     $cacheableMetadata->addCacheTags(['x402_solana_content']);

//     $content = \Drupal::service('renderer')->renderRoot($build);
    
//     // Use CacheableResponse for proper caching
//     $response = new CacheableResponse($content, 402);
//     $response->headers->set('Content-Type', 'text/html; charset=utf-8');
//     $response->addCacheableDependency($cacheableMetadata);
    
//     return $response;
//   }
    protected function buildPaymentRequiredResponse(Node $node, array $solanaFields) {
    // Generate URL to the payment required page
    $url = \Drupal\Core\Url::fromRoute('x402_solana_content.payment_required', ['node' => $node->id()]);
    
    // Create a redirect response that maintains the 402 concept
    // We'll use a special header to indicate this is a payment redirect
    $response = new \Symfony\Component\HttpFoundation\RedirectResponse($url->toString());
    
    // Add custom header to indicate payment requirement (optional)
    $response->headers->set('X-Payment-Required', 'true');
    
    return $response;
    }

  /**
   * Get all Solana fields from the node.
   */
  public function getSolanaFields(Node $node) {
    $solana_fields = [];
    
    foreach ($node->getFieldDefinitions() as $field_definition) {
      if ($field_definition->getType() === 'x402_solana_content') {
        $field_name = $field_definition->getName();
        
        if ($node->hasField($field_name)) {
          $field = $node->get($field_name);
          
          if (!$field->isEmpty()) {
            $field_values = $field->getValue();
            
            foreach ($field_values as $delta => $values) {
              // Check if this field instance is enabled
              if (!empty($values['enabled'])) {
                $config_mode = $field->getFieldDefinition()->getSetting('configuration_mode') ?? 'individual';
                
                if ($config_mode === 'global') {
                  $solana_fields[] = [
                    'field_name' => $field_name,
                    'delta' => $delta,
                    'amount' => $field->getFieldDefinition()->getSetting('amount'),
                    'currency' => $field->getFieldDefinition()->getSetting('currency'),
                    'address' => $field->getFieldDefinition()->getSetting('address'),
                    'configuration_mode' => 'global',
                    'label' => $field->getFieldDefinition()->getLabel(),
                  ];
                }
                else { // individual
                  $solana_fields[] = [
                    'field_name' => $field_name,
                    'delta' => $delta,
                    'amount' => $values['amount'] ?? 0,
                    'currency' => $values['currency'] ?? 'SOL',
                    'address' => $values['address'] ?? '',
                    'configuration_mode' => 'individual',
                    'label' => $field->getFieldDefinition()->getLabel(),
                  ];
                }
              }
            }
          }
        }
      }
    }

    return $solana_fields;
  }

  /**
   * Grant temporary access to content after payment verification.
   */
  public static function grantTemporaryAccess($nodeId, $duration = 3600) {
    $session = \Drupal::request()->getSession();
    $accessKey = 'x402_solana_access_' . $nodeId;
    $session->set($accessKey, [
      'granted' => time(),
      'expires' => time() + $duration,
    ]);
  }

}