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

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'x402_solana_content_formatter',
        '#price' => $item->price,
        '#currency' => $item->currency,
        '#address' => $item->address,
      ];
    }

    return $elements;
  }

}
