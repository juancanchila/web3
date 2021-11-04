<?php

namespace Drupal\recaptcha_element\Element;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Hidden;
use Drupal\Core\Render\Markup;
use ReCaptcha\ReCaptcha;

/**
 * Provides a Recaptcha form element.
 *
 * Properties:
 * - #recaptcha: An associative array of recaptcha element settings.Possible
 *   values:
 *   - action: a string defining the name of the action to verify.
 *     For more information see:
 *     https://developers.google.com/recaptcha/docs/v3#actions
 *   - threshold: a decimal between 0.0 and 1.0 which defines the minimal
 *     recaptcha score. 1.0 is very likely a good interaction, 0.0 is very
 *     likely a bot.
 *     For more information see:
 *     https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
 *   - verify_hostname: a boolean whether or not to verify the hostname of the
 *     site where the reCAPTCHA was solved.
 *
 * @FormElement("recaptcha_element")
 */
class RecaptchaElement extends Hidden {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [static::class, 'processRecaptcha'],
      ],
      '#element_validate' => [
        [static::class, 'validateRecaptcha'],
      ],
      '#pre_render' => [
        [static::class, 'preRenderHidden'],
      ],
      '#theme' => 'input__hidden',
      '#recaptcha' => [],
    ];
  }

  /**
   * Process callback for recaptcha elements.
   */
  public static function processRecaptcha(&$element) {
    $config = \Drupal::config('recaptcha_element.settings');

    if (!$config->get('enabled')) {
      $element['#access'] = FALSE;
      return $element;
    }

    $element['#recaptcha'] = array_merge(
      $config->get('element_defaults') ?: [],
      $element['#recaptcha'] ?? []
    );

    $element['#attributes']['data-recaptcha-element'] = '';
    $element['#attributes']['data-recaptcha-element-action'] = $element['#recaptcha']['action'];
    $element['#attributes']['data-recaptcha-element-site-key'] = $config->get('site_key');

    $element['#attached']['library'][] = 'recaptcha_element/recaptcha_element';

    return $element;
  }

  /**
   * Validate callback for recaptcha elements.
   */
  public static function validateRecaptcha(&$element, FormStateInterface $form_state) {
    $config = \Drupal::config('recaptcha_element.settings');
    if (!$config->get('enabled')) {
      return;
    }

    $request = \Drupal::request();

    $recaptcha = new Recaptcha($config->get('secret_key'));
    $recaptcha
      ->setExpectedAction($element['#recaptcha']['action'])
      ->setScoreThreshold($element['#recaptcha']['threshold']);

    if ($element['#recaptcha']['verify_hostname']) {
      $recaptcha->setExpectedHostname($request->getHost());
    }

    $recaptcha_response = $recaptcha->verify($element['#value'], $request->getClientIp());

    if (!$recaptcha_response->isSuccess()) {
      $form_state->setError($element, Markup::create(Xss::filterAdmin($element['#recaptcha']['error_message'])));
    }

    \Drupal::service('recaptcha_element.logger')->log($recaptcha_response);
  }

  /**
   * Builds a configuration form for the settings of a recaptcha elements.
   *
   * @param array $form
   *   The form array to which to attach the configuration form elements.
   * @param array|null $configuration
   *   The configuration defaults used to populate the form elements.
   *
   * @return array
   *   The configuration form.
   */
  public static function buildConfigurationForm(array $form = [], array $configuration = NULL) {
    $form['action'] = [
      '#type' => 'textfield',
      '#title' => t('reCAPTCHA action'),
      '#default_value' => $configuration['action'] ?? NULL,
      '#description' => t('The reCAPTCHA action to use and verify. See the <a href=":url">reCAPTCHA documentation</a> for more information.', [
        ':url' => 'https://developers.google.com/recaptcha/docs/v3#actions',
      ]),
    ];

    $form['threshold'] = [
      '#type' => 'number',
      '#title' => t('reCAPTCHA score threshold'),
      '#max' => 1,
      '#min' => 0,
      '#step' => 0.1,
      '#default_value' => $configuration['threshold'] ?? NULL,
      '#description' => t('reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). Based on the score, you can decide when reCAPTCHA verification should fail. See the <a href=":url">reCAPTCHA documentation</a> for more information.', [
        ':url' => 'https://developers.google.com/recaptcha/docs/v3#interpreting_the_score',
      ]),
    ];

    $form['verify_hostname'] = [
      '#type' => 'checkbox',
      '#title' => t('Local domain name validation'),
      '#default_value' => $configuration['threshold'] ?? NULL,
      '#description' => t('Checks the hostname on your server when verifying a solution. Enable this validation only, if <em>Verify the origin of reCAPTCHA solutions</em> is unchecked for your key pair. Provides crucial security by verifying requests come from one of your listed domains.'),
    ];

    $form['error_message'] = [
      '#type' => 'textarea',
      '#title' => t('Error message'),
      '#description' => t('This message will be displayed to the user when reCAPTCHA verification fails.'),
      '#default_value' => $configuration['error_message'] ?? NULL,
    ];

    return $form;
  }

}
