<?php

namespace Drupal\solana_pay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\solana_integration\Service\SolanaClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for checking Solana Pay payment status.
 */
class SolanaPayStatusController extends ControllerBase {

  /**
   * The Solana client.
   *
   * @var \Drupal\solana_integration\Service\SolanaClient
   */
  protected $solanaClient;

  /**
   * Constructs a new SolanaPayStatusController object.
   *
   * @param \Drupal\solana_integration\Service\SolanaClient $solana_client
   *   The Solana client.
   */
  public function __construct(SolanaClient $solana_client) {
    $this->solanaClient = $solana_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('solana_integration.client')
    );
  }

  /**
   * Checks the status of a Solana Pay payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $commerce_payment
   *   The payment entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with the payment status.
   */
  public function check(PaymentInterface $commerce_payment) {
    $current_user = $this->currentUser();
    $order = $commerce_payment->getOrder();

    if (!$order->access('view', $current_user)) {
      throw new AccessDeniedHttpException();
    }

    $reference = $commerce_payment->getRemoteId();
    
    if (empty($reference)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Payment reference not found.'),
      ], 400);
    }

    $current_state = $commerce_payment->getState()->value;
    
    if ($current_state === 'completed') {
      return new JsonResponse([
        'status' => 'confirmed',
        'message' => $this->t('Payment already confirmed.'),
      ]);
    }

    $expected_amount = (float) $commerce_payment->getAmount()->getNumber();
    $is_verified = $this->solanaClient->verifyPayment($reference, $expected_amount);

    if ($is_verified) {
      $commerce_payment->setState('completed');
      $commerce_payment->save();
      
      // Trigger order paid event to update order state
      $order = $commerce_payment->getOrder();
      $order->save();
      
      \Drupal::logger('solana_pay')->notice('Payment @id confirmed for order @order', [
        '@id' => $commerce_payment->id(),
        '@order' => $order->id(),
      ]);
      
      return new JsonResponse([
        'status' => 'confirmed',
        'message' => $this->t('Payment confirmed on blockchain.'),
      ]);
    }

    return new JsonResponse([
      'status' => 'pending',
      'message' => $this->t('Payment not yet confirmed.'),
    ]);
  }

}
