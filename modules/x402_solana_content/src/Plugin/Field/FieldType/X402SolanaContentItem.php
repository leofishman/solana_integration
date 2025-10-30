<?php

namespace Drupal\x402_solana_content\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

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
  public static function defaultFieldSettings() {
    return [
      'configuration_mode' => 'individual',
      'global_price' => '',
      'global_currency' => '',
      'global_address' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['configuration_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Configuration Mode'),
      '#options' => [
        'global' => $this->t('Global - Use the same wallet, price and currency for all content using this field.'),
        'individual' => $this->t('Individual - Configure wallet, price and currency for each content item.'),
      ],
      '#default_value' => $this->getSetting('configuration_mode'),
    ];

    $element['global_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Global Price'),
      '#default_value' => $this->getSetting('global_price'),
      '#step' => '0.01',
      '#min' => 0,
      '#states' => [
        'visible' => [
          'input[name*="[settings][configuration_mode]"][value="global"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['global_currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Global Currency'),
      '#default_value' => $this->getSetting('global_currency'),
      '#states' => [
        'visible' => [
          'input[name*="[settings][configuration_mode]"][value="global"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['global_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Global Address'),
      '#default_value' => $this->getSetting('global_address'),
      '#states' => [
        'visible' => [
          'input[name*="[settings][configuration_mode]"][value="global"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'enabled' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ],
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
    $properties['enabled'] = DataDefinition::create('boolean')
      ->setLabel(t('Enabled'));
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
    if ($this->get('enabled')->getValue()) {
      return FALSE;
    }
    $price = $this->get('price')->getValue();
    $currency = $this->get('currency')->getValue();
    $address = $this->get('address')->getValue();
    return empty($price) && empty($currency) && empty($address);
  }

}
