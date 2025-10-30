<?php

namespace Drupal\x402_solana_content\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'x402_solana_content' field type.
 *
 * @FieldType(
 *   id = "x402_solana_content",
 *   label = @Translation("x402 Solana Content"),
 *   description = @Translation("Provides a field type to handle x402 micropayments for content."),
 *   default_widget = "x402_solana_content_default",
 *   default_formatter = "x402_solana_content_default"
 * )
 */
class X402SolanaContentItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'price' => [
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
          'not null' => FALSE,
        ],
        'currency' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'address' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['price'] = DataDefinition::create('float')
      ->setLabel(t('Price'));
    $properties['currency'] = DataDefinition::create('string')
      ->setLabel(t('Currency'));
    $properties['address'] = DataDefinition::create('string')
      ->setLabel(t('Address'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $price = $this->get('price')->getValue();
    $currency = $this->get('currency')->getValue();
    $address = $this->get('address')->getValue();
    return empty($price) && empty($currency) && empty($address);
  }

}
