<?php

namespace Drupal\solana_integration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'solana_wallet_default' field widget.
 *
 * @FieldWidget(
 *  id = "solana_wallet_default",
 *  label = @Translation("Solana Wallet Address"),
 *  field_types = {
 *      "solana_wallet"
 *  }
 * )
 */
class SolanaWalletWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['address'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->address ?? NULL,
      '#placeholder' => $this->t('Enter Solana wallet address'),
      '#size' => 45,
      '#maxlength' => 44,
      '#element_validate' => [
        [static::class, 'validate'],
      ],
    ];
    return $element;
  }

  /**
   * Custom validation to trim whitespace.
   */
  public static function validate(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);
  }

}