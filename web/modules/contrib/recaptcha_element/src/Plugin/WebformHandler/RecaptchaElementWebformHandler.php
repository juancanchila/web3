<?php

namespace Drupal\recaptcha_element\Plugin\WebformHandler;

use Drupal\recaptcha_element\Element\RecaptchaElement;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform submission handler plugin.
 *
 * @WebformHandler(
 *   id = "recaptcha_element",
 *   label = @Translation("reCAPTCHA Element"),
 *   category = @Translation("recaptcha_element"),
 *   description = @Translation("Adds reCaptcha protection to the webform."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class RecaptchaElementWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'element_name' => NULL,
      'recaptcha' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#after_build'][] = [static::class, 'afterBuildConfigurationForm'];

    $form['element_name_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the element name.'),
      '#description' => $this->t('When checked, a custom element name can be provided for the hidden input element used to store reCAPTCHA tokens, otherwise the system name of this handler will be used.'),
    ];

    $form['element_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element name'),
      '#pattern' => '^[a-z0-9_]*$',
      '#description' => $this->t('Unique element name. Please enter only lower-case letters, numbers and underscores.'),
    ];

    $form['recaptcha_defaults'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use reCAPTCHA Element defaults.'),
      '#description' => $this->t('When checked, the reCAPTCHA Element defaults will be used.'),
      '#default_value' => empty($this->configuration['recaptcha']),
    ];

    $form['recaptcha'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('reCAPTCHA'),
      '#tree' => TRUE,
    ];

    $form['recaptcha'] = RecaptchaElement::buildConfigurationForm($form['recaptcha'], $this->configuration['recaptcha'] ?? []);

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * After build callback for the configuration form.
   */
  public static function afterBuildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element_name_override = sprintf(':input[name="%s"]', $form['element_name_override']['#name']);
    $element_name_override_checked = [
      $element_name_override => ['checked' => TRUE],
    ];

    $form['element_name']['#states']['enbled'] = $element_name_override_checked;
    $form['element_name']['#states']['visible'] = $element_name_override_checked;

    $recaptcha_defaults = sprintf(':input[name="%s"]', $form['recaptcha_defaults']['#name']);
    $recaptcha_defaults_checked = [
      $recaptcha_defaults => ['checked' => TRUE],
    ];

    $form['recaptcha']['#states']['disabled'] = $recaptcha_defaults_checked;
    $form['recaptcha']['#states']['invisible'] = $recaptcha_defaults_checked;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['element_name'] = empty($values['element_name_override']) && !empty($values['element_name'])
      ? $values['element_name']
      : NULL;
    $this->configuration['recaptcha'] = empty($values['recaptcha_defaults']) ? $values['recaptcha'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $element_name = empty($this->configuration['element_name'])
      ? $this->getHandlerId()
      : $this->configuration['element_name'];

    $form[$element_name] = [
      '#type' => 'recaptcha_element',
      '#recaptcha' => $this->configuration['recaptcha'],
    ];

    return $form;
  }

}
