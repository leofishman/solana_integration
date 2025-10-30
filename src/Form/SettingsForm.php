<?php

namespace Drupal\solana_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Solana Integration settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['solana_integration.settings'];
  }

  public function getFormId() {
    return 'solana_integration_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('solana_integration.settings');

     $form['solana_pay_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Solana Pay Configuration'),
    ];

    $form['solana_pay_section']['merchant_wallet_address'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Merchant Wallet Address'),
        '#description' => $this->t('The public key of the wallet that will receive payments.'),
        '#default_value' => $config->get('merchant_wallet_address'),
        '#maxlength' => 44,
        '#size' => 45,
    ];

    $endpoints = $config->get('endpoints') ?? [];
    $default_endpoint = $config->get('default_endpoint') ?? 'mainnet';

    // Endpoints configuration section.
    $form['endpoints_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('RPC Endpoints Configuration'),
      '#description' => $this->t('Configure which Solana RPC endpoints are available and which one to use by default.'),
    ];

    // Create checkboxes for enabling/disabling endpoints.
    $enabled_endpoints = [];
    $endpoint_options = [];
    
    foreach ($endpoints as $key => $endpoint) {
      if (!empty($endpoint['enabled'])) {
        $enabled_endpoints[$key] = $key;
      }
      $endpoint_options[$key] = $endpoint['name'] . ' (' . $endpoint['url'] . ')';
    }

    $form['endpoints_section']['enabled_endpoints'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled endpoints'),
      '#options' => $endpoint_options,
      '#default_value' => $enabled_endpoints,
      '#description' => $this->t('Select which endpoints are available for use.'),
    ];

    // Create select for default endpoint (only show enabled ones).
    $form['endpoints_section']['default_endpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Default endpoint'),
      '#options' => $endpoint_options,
      '#default_value' => $default_endpoint,
      '#required' => TRUE,
      '#description' => $this->t('The default endpoint to use for Solana JSON-RPC requests.'),
    ];

    // Endpoint management section.
    $form['endpoint_management'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Manage Endpoints'),
      '#description' => $this->t('Edit or delete existing endpoints. Official Solana endpoints are installed by default but can be modified or removed.'),
    ];

    foreach ($endpoints as $key => $endpoint) {
      $form['endpoint_management'][$key] = [
        '#type' => 'fieldset',
        '#title' => $endpoint['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['endpoint_management'][$key]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $endpoint['name'],
        '#required' => TRUE,
      ];

      $form['endpoint_management'][$key]['url'] = [
        '#type' => 'url',
        '#title' => $this->t('URL'),
        '#default_value' => $endpoint['url'],
        '#required' => TRUE,
        '#description' => $this->t('The JSON-RPC endpoint URL.'),
      ];

      // Add delete button for all endpoints.
      $form['endpoint_management'][$key]['delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Delete this endpoint'),
        '#default_value' => FALSE,
      ];
    }

    // Add custom endpoint section.
    $form['add_custom_endpoint'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Custom Endpoint'),
      '#description' => $this->t('Add a new custom RPC endpoint.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['add_custom_endpoint']['custom_endpoint_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint name'),
      '#description' => $this->t('A human-readable name for this endpoint.'),
    ];

    $form['add_custom_endpoint']['custom_endpoint_key'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Endpoint machine name'),
      '#description' => $this->t('A unique machine-readable name for this endpoint. Use lowercase letters, numbers, and underscores only.'),
      '#machine_name' => [
        'exists' => [$this, 'endpointExists'],
        'source' => ['add_custom_endpoint', 'custom_endpoint_name'],
      ],
      '#required' => FALSE,
    ];

    $form['add_custom_endpoint']['custom_endpoint_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint URL'),
      '#description' => $this->t('The JSON-RPC endpoint URL.'),
    ];

    $form['add_custom_endpoint']['custom_endpoint_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable this endpoint'),
      '#default_value' => TRUE,
    ];


    $form['facilitator_url'] = [
      '#type' => 'url',
      '#title' => 'Facilitator URL',
      '#default_value' => $config->get('facilitator_url') ?: \Drupal::request()->getSchemeAndHttpHost(),
      '#description' => 'Leave blank to use built-in facilitator.',
    ];

    $form['default_price'] = [
      '#type' => 'number',
      '#title' => 'Default Price (USDC)',
      '#step' => 0.001,
      '#default_value' => $config->get('default_price') ?: 0.01,
    ];

    $form['enabled_paths'] = [
      '#type' => 'textarea',
      '#title' => 'Protected Paths (one per line, fnmatch)',
      '#default_value' => implode("\n", $config->get('enabled_paths') ?: []),
      '#description' => 'e.g. /premium/*',
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * Checks if an endpoint with the given key already exists.
   *
   * @param string $key
   *   The endpoint machine name to check.
   *
   * @return bool
   *   TRUE if the endpoint exists, FALSE otherwise.
   */
  public function endpointExists($key) {
    $config = $this->config('solana_integration.settings');
    $endpoints = $config->get('endpoints') ?? [];
    return isset($endpoints[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    
    $enabled_endpoints = array_filter($form_state->getValue('enabled_endpoints'));
    $default_endpoint = $form_state->getValue('default_endpoint');
    
    // Ensure at least one endpoint is enabled.
    if (empty($enabled_endpoints)) {
      $form_state->setErrorByName('enabled_endpoints', $this->t('At least one endpoint must be enabled.'));
    }
    
    // Ensure the default endpoint is enabled.
    if ($default_endpoint && !isset($enabled_endpoints[$default_endpoint])) {
      $form_state->setErrorByName('default_endpoint', $this->t('The default endpoint must be enabled.'));
    }

    // Validate custom endpoint addition.
    $custom_key = $form_state->getValue('custom_endpoint_key');
    $custom_name = $form_state->getValue('custom_endpoint_name');
    $custom_url = $form_state->getValue('custom_endpoint_url');

    // If any custom endpoint field is filled, all must be filled.
    $has_custom_data = !empty($custom_key) || !empty($custom_name) || !empty($custom_url);
    
    if ($has_custom_data) {
      if (empty($custom_key)) {
        $form_state->setErrorByName('custom_endpoint_key', $this->t('Endpoint machine name is required when adding a custom endpoint.'));
      }
      if (empty($custom_name)) {
        $form_state->setErrorByName('custom_endpoint_name', $this->t('Endpoint name is required when adding a custom endpoint.'));
      }
      if (empty($custom_url)) {
        $form_state->setErrorByName('custom_endpoint_url', $this->t('Endpoint URL is required when adding a custom endpoint.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    $config = $this->configFactory->getEditable('solana_integration.settings');
    $endpoints = $config->get('endpoints') ?? [];
    $enabled_endpoints = array_filter($form_state->getValue('enabled_endpoints'));
    
    // Track endpoints marked for deletion.
    $endpoints_to_delete = [];
    
    // Update endpoint enabled status and details.
    foreach ($endpoints as $key => $endpoint) {
      // Check if this endpoint should be deleted.
      if ($form_state->hasValue($key)) {
        $endpoint_values = $form_state->getValue($key);
        
        // Delete endpoint if delete checkbox is checked.
        if (!empty($endpoint_values['delete'])) {
          $endpoints_to_delete[] = $key;
          continue;
        }
        
        // Update endpoint details if they were modified.
        if (isset($endpoint_values['name'])) {
          $endpoints[$key]['name'] = $endpoint_values['name'];
        }
        if (isset($endpoint_values['url'])) {
          $endpoints[$key]['url'] = $endpoint_values['url'];
        }
      }
      
      // Update enabled status.
      if (isset($endpoints[$key])) {
        $endpoints[$key]['enabled'] = isset($enabled_endpoints[$key]);
      }
    }
    
    // Remove deleted endpoints.
    foreach ($endpoints_to_delete as $key) {
      unset($endpoints[$key]);
    }
    
    // Add new custom endpoint if provided.
    $custom_key = $form_state->getValue('custom_endpoint_key');
    $custom_name = $form_state->getValue('custom_endpoint_name');
    $custom_url = $form_state->getValue('custom_endpoint_url');
    $custom_enabled = $form_state->getValue('custom_endpoint_enabled');
    
    if (!empty($custom_key) && !empty($custom_name) && !empty($custom_url)) {
      $endpoints[$custom_key] = [
        'name' => $custom_name,
        'url' => $custom_url,
        'enabled' => (bool) $custom_enabled,
      ];
      
      $this->messenger()->addStatus($this->t('Endpoint "%name" has been added.', ['%name' => $custom_name]));
    }
    
    $config
      ->set('endpoints', $endpoints)
      ->set('default_endpoint', $form_state->getValue('default_endpoint'))
      ->set('merchant_wallet_address', $form_state->getValue('merchant_wallet_address'))
      ->set('wallet_address', $form_state->getValue('merchant_wallet_address')) // TODO: use merchant_wallet_address for x402 for now
      ->set('facilitator_url', rtrim($form_state->getValue('facilitator_url'), '/'))
      ->set('default_price', $form_state->getValue('default_price'))
      ->set('enabled_paths', array_filter(preg_split('/\r\n|\r|\n/', $form_state->getValue('enabled_paths'))))      
      ->save();
  }
}

