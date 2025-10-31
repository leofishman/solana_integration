<?php

namespace Drupal\x402_solana_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'x402_solana_content_default' widget.
 *
 * @FieldWidget(
 *   id = "x402_solana_content_default",
 *   label = @Translation("x402 Solana Content default"),
 *   field_types = {
 *     "x402_solana_content"
 *   }
 * )
 */
class X402SolanaContentDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_definition = $items->getFieldDefinition();
    $setting = $field_definition->getSetting('configuration_mode');

    $element['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable x402 micropayment'),
      '#default_value' => isset($items[$delta]->enabled) ? $items[$delta]->enabled : $field_definition->getSetting('enabled'),
    ];

    if ($setting === 'individual') {
      $element['amount'] = [
        '#type' => 'number',
        '#title' => $this->t('amount'),
        '#default_value' => isset($items[$delta]->amount) ? $items[$delta]->amount : $field_definition->getSetting('amount'),
        '#step' => '0.01',
        '#min' => 0,
        '#states' => [
          'visible' => [
            ':input[name*="[enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $element['currency'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Currency'),
        '#default_value' => isset($items[$delta]->currency) ? $items[$delta]->currency : $field_definition->getSetting('currency'),
        '#size' => 60,
        '#maxlength' => 255,
        '#states' => [
          'visible' => [
            ':input[name*="[enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $element['address'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Address'),
        '#default_value' => isset($items[$delta]->address) ? $items[$delta]->address : $field_definition->getSetting('address'),
        '#size' => 60,
        '#maxlength' => 255,
        '#states' => [
          'visible' => [
            ':input[name*="[enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $element;
  }

}
