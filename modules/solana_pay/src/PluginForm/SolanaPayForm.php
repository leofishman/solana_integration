<?php

namespace Drupal\solana_pay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the offsite payment form for Solana Pay.
 */
class SolanaPayForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    
    /** @var \Drupal\solana_pay\Plugin\Commerce\PaymentGateway\SolanaPay $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;

    // Build the Solana Pay checkout page directly (no external redirect needed)
    $redirect_form = $payment_gateway_plugin->buildPaymentPage(
      $payment,
      $form_state
    );

    // Return the custom form instead of using buildRedirectForm
    // because we're displaying QR code on the same site
    return array_merge($form, $redirect_form);
  }

}
