<?php

namespace Drupal\solana_pay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\solana_integration\Service\SolanaClient;

/**
 * Controller for Solana Pay payment page.
 */
class SolanaPayController extends ControllerBase {

  /**
   * The Solana client.
   *
   * @var \Drupal\solana_integration\Service\SolanaClient
   */
  protected $solanaClient;

  /**
   * Constructs a SolanaPayController object.
   *
   * @param \Drupal\solana_integration\Service\SolanaClient $solana_client
   *   The Solana client service.
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
   * Displays the payment page with QR code.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $commerce_payment
   *   The payment entity.
   *
   * @return array
   *   Render array for the payment page.
   */
  public function payment(PaymentInterface $commerce_payment) {
    $order = $commerce_payment->getOrder();
    $order_id = $order->id();
    $amount = (float) $commerce_payment->getAmount()->getNumber();
    $currency_code = $commerce_payment->getAmount()->getCurrencyCode();
    $label = 'Payment for order #' . $order_id;
    $message = 'Order #' . $order_id;

    $reference = $commerce_payment->getRemoteId();
    if (empty($reference)) {
      $payment_request_url = $this->solanaClient->generatePaymentRequest($amount, $currency_code, $label, $message, $reference);
      
      if ($payment_request_url && !empty($reference)) {
        $commerce_payment->setRemoteId($reference);
        $commerce_payment->save();
      }
    }
    else {
      $payment_request_url = $this->solanaClient->generatePaymentRequest($amount, $currency_code, $label, $message, $reference);
    }

    if (!$payment_request_url) {
      return [
        '#markup' => $this->t('Solana Pay is not configured. Please contact support.'),
      ];
    }

    $sol_amount = $this->solanaClient->getLastConvertedAmount();
    $config = $this->config('solana_integration.settings');
    $merchant_address = $config->get('merchant_wallet_address');

    return [
      '#theme' => 'solana_pay_instructions',
      '#payment_url' => $payment_request_url,
      '#amount' => $sol_amount,
      '#currency' => $currency_code,
      '#original_amount' => $amount,
      '#merchant_address' => $merchant_address,
      '#payment_id' => $commerce_payment->id(),
      '#order_id' => $order_id,
      '#attached' => [
        'library' => ['solana_pay/checkout'],
        'drupalSettings' => [
          'solanaPay' => [
            'solanaUrl' => $payment_request_url,
            'statusUrl' => Url::fromRoute('solana_pay.status', ['commerce_payment' => $commerce_payment->id()], ['absolute' => TRUE])->toString(),
            'completionUrl' => Url::fromRoute('commerce_checkout.form', ['commerce_order' => $order_id, 'step' => 'complete'], ['absolute' => TRUE])->toString(),
            'paymentId' => $commerce_payment->id(),
          ],
        ],
      ],
    ];
  }

}
