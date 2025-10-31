<?php

namespace Drupal\solana_integration\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;

class X402Subscriber implements EventSubscriberInterface {

  protected $config;

  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 28];
    return $events;
  }

  public function onKernelRequest(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Skip admin, assets, etc.
    if (strpos($path, '/admin') === 0 || strpos($path, '/x402/verify') === 0) {
      return;
    }

    $protected = $this->isProtected($request);
    if ($protected && !$this->hasValidPayment($request)) {
      $response = $this->return402($request, $protected);
      $event->setResponse($response);
    }
  }

  protected function isProtected(Request $request) {
    $route_match = $request->attributes->get('_route_match');
    if (!$route_match) {
      return FALSE;
    }
    $node = $route_match->getParameter('node');

    if ($node instanceof NodeInterface) {
      foreach ($node->getFieldDefinitions() as $field_definition) {
        if ($field_definition->getType() === 'x402_solana_content') {
          $field_name = $field_definition->getName();
          if ($node->hasField($field_name)) {
            $field = $node->get($field_name);
            if (!$field->isEmpty() && $field->enabled) {
              $config_mode = $field->getFieldDefinition()->getSetting('configuration_mode');
              if ($config_mode === 'global') {
                return [
                  'price' => $field->getFieldDefinition()->getSetting('price'),
                  'currency' => $field->getFieldDefinition()->getSetting('currency'),
                  'address' => $field->getFieldDefinition()->getSetting('address'),
                ];
              }
              else { // individual
                return [
                  'price' => $field->price,
                  'currency' => $field->currency,
                  'address' => $field->address,
                ];
              }
            }
          }
        }
      }
    }

    return FALSE;
  }

  protected function hasValidPayment(Request $request) {
    if (!$request->headers->has('X402-Payment')) {
      return FALSE;
    }

    $payload = json_decode($request->headers->get('X402-Payment'), TRUE);
    if (!$payload) return FALSE;

    $client = \Drupal::httpClient();
    try {
      $response = $client->post($this->getFacilitatorUrl() . '/verify', [
        'json' => ['payload' => $payload],
      ]);
      return $response->getStatusCode() === 200;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  protected function return402(Request $request, array $payment_details) {
    $intent = [
      'amount' => $payment_details['price'],
      'currency' => $payment_details['currency'],
      'network' => 'solana',
      'recipient' => $payment_details['address'],
      'facilitator' => $this->getFacilitatorUrl(),
      'description' => 'Premium content access',
    ];

    return new Response(
      json_encode($intent),
      Response::HTTP_PAYMENT_REQUIRED,
      ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store']
    );
  }

  protected function getFacilitatorUrl() {
    $config = $this->config->get('solana_integration.settings');
    return rtrim($config->get('facilitator_url') ?: \Drupal::request()->getSchemeAndHttpHost(), '/') . '/x402';
  }
}
