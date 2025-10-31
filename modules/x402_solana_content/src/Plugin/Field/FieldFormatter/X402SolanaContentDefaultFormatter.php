<?php

namespace Drupal\x402_solana_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'x402_solana_content_default' formatter.
 *
 * @FieldFormatter(
 *   id = "x402_solana_content_default",
 *   label = @Translation("x402 Solana Content default"),
 *   field_types = {
 *     "x402_solana_content"
 *   }
 * )
 */
class X402SolanaContentDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $setting = $items->getFieldDefinition()->getSetting('configuration_mode');

    foreach ($items as $delta => $item) {
      if (!$item->enabled) {
        continue;
      }

      if ($setting === 'global') {
        $amount = $items->getFieldDefinition()->getSetting('global_amount');
        $currency = $items->getFieldDefinition()->getSetting('global_currency');
        $address = $items->getFieldDefinition()->getSetting('global_address');

        $elements[$delta] = [
          '#theme' => 'x402_solana_content_formatter',
          '#amount' => $amount,
          '#currency' => $currency,
          '#address' => $address,
        ];
      }
      else {
        $elements[$delta] = [
          '#theme' => 'x402_solana_content_formatter',
          '#amount' => $item->amount,
          '#currency' => $item->currency,
          '#address' => $item->address,
        ];
      }
    }

    return $elements;
  }

}
