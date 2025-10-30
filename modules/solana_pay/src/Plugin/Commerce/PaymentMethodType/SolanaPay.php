<?php

namespace Drupal\solana_pay\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Solana Pay payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "solana_pay",
 *   label = @Translation("Solana Pay"),
 *   create_label = @Translation("Solana Pay"),
 * )
 */
class SolanaPay extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Solana Pay');
  }

}
