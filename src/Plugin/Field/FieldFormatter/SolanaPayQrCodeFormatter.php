<?php

namespace Drupal\solana_integration\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\solana_integration\Service\SolanaClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'solana_pay_qr_code' formatter.
 *
 * @FieldFormatter(
 * id = "solana_pay_qr_code",
 * label = @Translation("Solana Pay QR Code"),
 * field_types = {
 * "decimal",
 * "float",
 * "integer",
 * }
 * )
 */
class SolanaPayQrCodeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The Solana client service.
   *
   * @var \Drupal\solana_integration\Service\SolanaClient
   */
  protected $solanaClient;

  /**
   * Constructs a new SolanaPayQrCodeFormatter object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, SolanaClient $solana_client) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->solanaClient = $solana_client;
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

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      $amount = (float) $item->value;

      if ($amount <= 0) {
        continue;
      }

      $reference = '';
      $label = $entity->label();
      $message = $this->t('Payment for @label', ['@label' => $label]);

      $payment_request_url = $this->solanaClient->generatePaymentRequest($amount, $label, $message, $reference);

      if (!$payment_request_url) {
        $elements[$delta] = [
          '#markup' => $this->t('Solana Pay is not configured. Please set the merchant wallet address in the settings.'),
        ];
        continue;
      }

      $verification_url = Url::fromRoute('solana_integration.payment_verify', [
        'reference' => $reference,
        'amount' => $amount,
      ])->toString();

      $unique_id = 'solana-pay-qr-' . $entity->id() . '-' . $delta;

      $elements[$delta] = [
        '#theme' => 'solana_pay_qr_code',
        '#unique_id' => $unique_id,
        '#amount' => $amount,
        '#attached' => [
          'library' => ['solana_integration/solana_pay_qr'],
          'drupalSettings' => [
            'solanaPay' => [
              $unique_id => [
                'paymentRequestUrl' => $payment_request_url,
                'verificationUrl' => $verification_url,
              ],
            ],
          ],
        ],
      ];
    }

    return $elements;
  }
}
