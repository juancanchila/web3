<?php

namespace Drupal\recaptcha_element\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\recaptcha_element\Element\RecaptchaElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the Recaptcha Element module.
 */
class RecaptchaElementSettingsForm extends ConfigFormBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a RecaptchaElementSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library discovery service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscoveryInterface $library_discovery) {
    parent::__construct($config_factory);
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recapcha_element_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['recaptcha_element.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recaptcha_element.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable reCAPTCHA integration'),
      '#description' => $this->t('When disabled recaptcha elements are not displayed and not verified. This for example allows you to disable reCAPTCHA integration on development and/or test environments.'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site key'),
      '#default_value' => $config->get('site_key'),
      '#maxlength' => 40,
      '#description' => $this->t('The site key given to you when you <a href=":url">register for reCAPTCHA</a>.', [
        ':url' => 'https://www.google.com/recaptcha/admin',
      ]),
      '#required' => TRUE,
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $config->get('secret_key'),
      '#maxlength' => 40,
      '#description' => $this->t('The secret key given to you when you <a href=":url">register for reCAPTCHA</a>.', [
        ':url' => 'https://www.google.com/recaptcha/admin',
      ]),
      '#required' => TRUE,
    ];

    $form['element_defaults'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recaptcha Element defaults'),
      '#tree' => TRUE,
    ];

    $form['element_defaults'] = RecaptchaElement::buildConfigurationForm($form['element_defaults'], $config->get('element_defaults'));

    $form['logging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Logging'),
    ];
    $form['logging']['log_successes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log successful reCAPTCHA responses'),
      '#description' => $this->t('When checked, successful reCAPTCHA responses will be logged with log level %log_level.', [
        '%log_level' => RfcLogLevel::getLevels()[RfcLogLevel::INFO],
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('recaptcha_element.settings');

    // If the site key changed, then rebuild site libraries.
    if ($config->get('site_key') !== $values['site_key']) {
      $this->libraryDiscovery->clearCachedDefinitions();
    }

    $config
      ->set('enabled', $values['enabled'])
      ->set('site_key', $values['site_key'])
      ->set('secret_key', $values['secret_key'])
      ->set('element_defaults', $values['element_defaults'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
