<?php

namespace Drupal\solana_integration\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\solana_integration\Service\SolanaClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'solana_wallet_link' formatter.
 *
 * @FieldFormatter(
 *  id = "solana_wallet_link",
 *  label = @Translation("Solana Wallet Link"),
 *  field_types = {
 *      "solana_wallet"
 *  }
 * )
 */
class SolanaWalletFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The Solana client service.
   *
   * @var \Drupal\solana_integration\Service\SolanaClient
   */
  protected SolanaClient $solanaClient;

  /**
   * Constructs a SolanaWalletFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter-specific settings.
   * @param string $label
   *   The formatter label.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\solana_integration\Service\SolanaClient $solana_client
   *   The Solana client service.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, SolanaClient $solana_client) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->solanaClient = $solana_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'link_to_explorer' => TRUE,
      'trim_address' => TRUE,
      'trim_length' => 4,
      'explorer' => 'solscan',
      'show_balance' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['link_to_explorer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to a blockchain explorer'),
      '#default_value' => $this->getSetting('link_to_explorer'),
    ];

    $elements['explorer'] = [
      '#type' => 'select',
      '#title' => $this->t('Explorer'),
      '#options' => [
        'solscan' => 'Solscan',
        'solanafm' => 'Solana.fm',
        'official' => 'explorer.solana.com',
      ],
      '#default_value' => $this->getSetting('explorer'),
      '#states' => [
        'visible' => [':input[name$="[link_to_explorer]"]' => ['checked' => TRUE]],
      ],
    ];

    $elements['trim_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Abbreviate the address (e.g., 43rW...y5kC)'),
      '#default_value' => $this->getSetting('trim_address'),
    ];

    $elements['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters to show at start/end'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 2,
      '#max' => 10,
      '#states' => [
        'visible' => [':input[name$="[trim_address]"]' => ['checked' => TRUE]],
      ],
    ];

    $elements['show_balance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show SOL balance'),
      '#default_value' => $this->getSetting('show_balance'),
      '#description' => $this->t('Display the account balance next to the address. This may impact page load time as it requires an RPC call for each address.'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    if ($this->getSetting('link_to_explorer')) {
      $summary[] = $this->t('Links to @explorer', ['@explorer' => $this->getSetting('explorer')]);
    }
    else {
      $summary[] = $this->t('Does not link to explorer');
    }
    if ($this->getSetting('trim_address')) {
      $len = $this->getSetting('trim_length');
      $summary[] = $this->t('Abbreviated to @len...@len characters', ['@len' => $len]);
    }
    if ($this->getSetting('show_balance')) {
      $summary[] = $this->t('Shows SOL balance');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $link_to_explorer = $this->getSetting('link_to_explorer');
    $trim = $this->getSetting('trim_address');
    $trim_length = (int) $this->getSetting('trim_length');
    $explorer = $this->getSetting('explorer');
    $show_balance = $this->getSetting('show_balance');

    foreach ($items as $delta => $item) {
      $address = $item->address;
      if (!$address) {
        continue;
      }

      $display_address = $address;
      if ($trim && strlen($address) > ($trim_length * 2)) {
        $display_address = substr($address, 0, $trim_length) . '...' . substr($address, -$trim_length);
      }

      $balance_text = '';
      if ($show_balance) {
        try {
          $balance_array = $this->solanaClient->getBalance($address);
          $lamports = $balance_array['value'] ?? NULL;
          if (is_int($lamports)) {
            $sol = $lamports / 1_000_000_000;
            // Format SOL to a reasonable number of decimal places.
            $formatted_sol = number_format($sol, $sol > 0.001 ? 4 : 9);
            $balance_text = " ({$formatted_sol} SOL)";
          }
        }
        catch (\Exception $e) {
          // Fail silently, just don't show the balance.
        }
      }

      if ($link_to_explorer) {
        $url = $this->buildExplorerUrl($address, $explorer);
        $elements[$delta] = [
          '#markup' => $this->t('<a href=":url" target="_blank" title="@title">:address</a>@balance', [
            ':url' => $url,
            '@title' => $this->t('View on explorer: @address', ['@address' => $address]),
            ':address' => $display_address,
            '@balance' => $balance_text,
          ]),
        ];
      }
      else {
        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => '{{ address }}{{ balance }}',
          '#context' => ['address' => $display_address, 'balance' => $balance_text],
        ];
      }
    }

    return $elements;
  }

  /**
   * Helper function to build the explorer URL.
   */
  protected function buildExplorerUrl(string $address, string $explorer): string {
    return match ($explorer) {
      'solanafm' => "https://solana.fm/address/{$address}",
      'official' => "https://explorer.solana.com/address/{$address}",
      default => "https://solscan.io/account/{$address}",
    };
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('solana_integration.client')
    );
  }

}