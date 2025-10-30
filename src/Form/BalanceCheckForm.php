<?php

namespace Drupal\solana_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\solana_integration\Service\SolanaClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to check the balance of a Solana account.
 */
class BalanceCheckForm extends FormBase {

  /**
   * The Solana client service.
   *
   * @var \Drupal\solana_integration\Service\SolanaClient
   */
  protected SolanaClient $solanaClient;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(SolanaClient $solana_client, ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->solanaClient = $solana_client;
    $this->configFactory = $config_factory;
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('solana_integration.client'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'solana_integration_balance_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $account_address = $form_state->getValue('account_address') ?? '';

    $form['account_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solana Account Address'),
      '#description' => $this->t('Enter the public key of the Solana account to check.'),
      '#required' => TRUE,
      '#maxlength' => 44,
      '#size' => 45,
      '#default_value' => $account_address,
    ];

    // Get configuration.
    $config = $this->configFactory->get('solana_integration.settings');
    $endpoints = $config->get('endpoints') ?? [];
    $default_endpoint_key = $config->get('default_endpoint') ?? 'mainnet';

    // Build endpoint options (only show enabled endpoints).
    $endpoint_options = [];
    foreach ($endpoints as $key => $endpoint) {
      if (!empty($endpoint['enabled'])) {
        $endpoint_options[$key] = $endpoint['name'] . ' (' . $endpoint['url'] . ')';
      }
    }

    // Add endpoint selector fieldset.
    if (!empty($endpoint_options)) {
      $form['endpoint_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('RPC Endpoint Settings'),
        '#open' => FALSE,
        '#description' => $this->t('Select which RPC endpoint to use for this balance check.'),
      ];

      $form['endpoint_settings']['endpoint'] = [
        '#type' => 'select',
        '#title' => $this->t('RPC Endpoint'),
        '#options' => $endpoint_options,
        '#default_value' => $default_endpoint_key,
        '#description' => $this->t('The RPC endpoint to query. Defaults to the configured default endpoint.'),
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check Balance'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $address = trim($form_state->getValue('account_address'));
    // Basic validation for Base58 characters and length.
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address)) {
      // $form_state->setErrorByName('account_address', $this->t('This does not appear to be a valid Solana wallet address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $address = $form_state->getValue('account_address');
    $selected_endpoint_key = $form_state->getValue('endpoint');

    // Get the selected endpoint details.
    $config = $this->configFactory->get('solana_integration.settings');
    $endpoints = $config->get('endpoints') ?? [];
    $selected_endpoint = $endpoints[$selected_endpoint_key] ?? null;

    if (!$selected_endpoint) {
      $this->messenger()->addError($this->t('Selected endpoint is not available.'));
      return;
    }

    $endpoint_url = $selected_endpoint['url'];
    $endpoint_name = $selected_endpoint['name'];

    try {
      // Use the selected endpoint for the balance check.
      $balance_array = $this->solanaClient->getBalance($address, $endpoint_url);
      $lamports = $balance_array['value'] ?? null;
      
      if (is_int($lamports)) {
        $sol = $lamports / 1_000_000_000; // 1 SOL = 10^9 lamports
        $this->messenger()->addStatus($this->t('The balance for account @address is @sol SOL (@lamports lamports). <br />RPC Endpoint: @endpoint', [
          '@address' => $address,
          '@sol' => number_format($sol, 9),
          '@lamports' => number_format($lamports),
          '@endpoint' => $endpoint_name . ' (' . $endpoint_url . ')',
        ]));
      }
      else {
        $this->messenger()->addWarning($this->t('Could not determine the balance. The account may not exist or an RPC error occurred.'));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An unexpected error occurred: @message', ['@message' => $e->getMessage()]));
    }
    $form_state->setRebuild(TRUE);
  }

}