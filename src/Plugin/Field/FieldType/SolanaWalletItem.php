<?php

namespace Drupal\solana_integration\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the 'solana_wallet' field type.
 *
 * @FieldType(
 * id = "solana_wallet",
 * label = @Translation("Solana Wallet Address"),
 * description = @Translation("Stores a Solana blockchain wallet address."),
 * category = "Web3",
 * default_widget = "solana_wallet_default",
 * default_formatter = "solana_wallet_link"
 * )
 */
class SolanaWalletItem extends FieldItemBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'address' => [
          'type' => 'varchar',
          'length' => 44, // Max length of a base58 encoded Solana public key.
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['address'] = DataDefinition::create('string')
      ->setLabel($field_definition->getLabel())
      ->setRequired(TRUE)
      ->addConstraint('Regex', [
        // Basic validation: checks for 32-44 base58 characters.
        // This regex ensures the string contains only valid base58 characters.
        'pattern' => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',
        'message' => t('This does not appear to be a valid Solana wallet address.'),
      ]);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('address')->getValue();
    return $value === NULL || $value === '';
  }

}