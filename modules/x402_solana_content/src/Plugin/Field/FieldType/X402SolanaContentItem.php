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
      'amount' => '',
      'currency' => '',
      'address' => '',
      'enabled' => 1,
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
        'global' => $this->t('Global - Use the same wallet, amount and currency for all content using this field.'),
        'individual' => $this->t('Individual - Configure wallet, amount and currency for each content item.'),
      ],
      '#default_value' => $this->getSetting('configuration_mode'),
    ];

    $element['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled by default'),
      '#default_value' => $this->getSetting('enabled'),
    ];

    $element['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#default_value' => $this->getSetting('amount'),
      '#step' => '0.01',
      '#min' => 0,
    ];

    $element['currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency'),
      '#default_value' => $this->getSetting('currency'),
    ];

    $element['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#default_value' => $this->getSetting('address'),
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
        'amount' => [
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
    $properties['amount'] = DataDefinition::create('float')
      ->setLabel(t('Amount'));
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
    $amount = $this->get('amount')->getValue();
    $currency = $this->get('currency')->getValue();
    $address = $this->get('address')->getValue();
    return empty($amount) && empty($currency) && empty($address);
  }

}
