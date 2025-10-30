<?php

namespace Drupal\solana_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\solana_integration\Service\SolanaClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for handling Solana Pay requests.
 */
class SolanaPayController extends ControllerBase {

  /**
   * The Solana client service.
   *
   * @var \Drupal\solana_integration\Service\SolanaClient
   */
  protected $solanaClient;

  /**
   * Constructs a new SolanaPayController object.
   *
   * @param \Drupal\solana_integration\Service\SolanaClient $solana_client
   * The Solana client service.
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
   * Verifies a payment transaction.
   *
   * @param string $reference
   * The transaction reference public key.
   * @param float $amount
   * The expected amount in SOL.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * A JSON response with the payment status.
   */
  public function verify(string $reference, float $amount) {
    $is_confirmed = $this->solanaClient->verifyPayment($reference, $amount);

    return new JsonResponse([
      'status' => $is_confirmed ? 'confirmed' : 'pending',
    ]);
  }

}