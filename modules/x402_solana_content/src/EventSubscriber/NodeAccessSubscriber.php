<?php

namespace Drupal\x402_solana_content\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
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
   * Constructor.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
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

    // Check if this node has Solana payment protection enabled
    if ($this->isSolanaProtected($node) && !$this->hasPaidForContent($node)) {
      $response = $this->buildPaymentRequiredResponse($node);
      $event->setResponse($response);
    }
  }

  /**
   * Check if node has Solana payment protection enabled.
   */
  protected function isSolanaProtected(Node $node) {
    // Check if the node has the Solana field and it's enabled
    // TODO: replace field name to field type and handle multple fields of type
    if ($node->hasField('field_x402')) {
      $field = $node->get('field_x402');
      if (!$field->isEmpty()) {
        $values = $field->getValue();
        return empty($values[0]['enable']);
      }
    }
    return FALSE;
  }

  /**
   * Check if current user has paid for this content.
   */
  protected function hasPaidForContent(Node $node) {
    // Implement your payment verification logic here
    // This could check:
    // - Session storage for temporary access
    // - Database records of payments
    // - Solana blockchain verification via RPC
    // - User roles/permissions
    
    // For now, return false to demonstrate 402 behavior
    // You'll replace this with actual payment verification
    return FALSE;
  }

  /**
   * Build a 402 Payment Required response.
   */
  protected function buildPaymentRequiredResponse(Node $node) {
    $solanaField = $node->get('field_x402')->getValue();
    $config = $solanaField[0] ?? [];
    
    $amount = $config['amount'] ?? 0;
    $currency = $config['currency'] ?? 'SOL';
    $wallet = $config['wallet'] ?? '';
    
    $build = [
      '#theme' => 'x402_solana_payment_required',
      '#title' => $node->getTitle(),
      '#amount' => $amount,
      '#currency' => $currency,
      '#wallet' => $wallet,
      '#node_id' => $node->id(),
    ];

    $content = \Drupal::service('renderer')->renderRoot($build);
    
    $response = new Response($content, 402);
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    
    return $response;
  }

  protected function getSolanaFields($node) {
    $solana_fields = [];
    foreach ($node->getFieldDefinitions() as $field_definition) {
        if ($field_definition->getType() === 'x402_solana_content') {
          $field_name = $field_definition->getName();
          if ($node->hasField($field_name)) {
            $field = $node->get($field_name);
            if (!$field->isEmpty() && $field->enabled) {
              $config_mode = $field->getFieldDefinition()->getSetting('configuration_mode');
              if ($config_mode === 'global') {
                $solana_fields[] = [
                  'amount' => $field->getFieldDefinition()->getSetting('amount'),
                  'currency' => $field->getFieldDefinition()->getSetting('currency'),
                  'address' => $field->getFieldDefinition()->getSetting('address'),
                ];
              }
              else { // individual
                $solana_fields[] = [
                  'amount' => $field->amount,
                  'currency' => $field->currency,
                  'address' => $field->address,
                ];
              }
            }
          }
        }
    }

    return $solana_fields;
  }

}