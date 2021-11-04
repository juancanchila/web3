<?php

namespace Drupal\webform_handler_compare_fields\Plugin\WebformHandler;

/*
 *  Written By Kevin Finkenbinder while working for MSU.
 *
 *  COPYRIGHT © 2019
 *  MICHIGAN STATE UNIVERSITY BOARD OF TRUSTEES
 *  ALL RIGHTS RESERVED
 *
 *  PERMISSION IS GRANTED TO USE, COPY, CREATE DERIVATIVE WORKS AND
 *  REDISTRIBUTE THIS SOFTWARE AND SUCH DERIVATIVE WORKS FOR ANY PURPOSE, SO
 *  LONG AS THE NAME OF MICHIGAN STATE UNIVERSITY IS NOT USED IN ANY
 *  ADVERTISING OR PUBLICITY PERTAINING TO THE USE OR DISTRIBUTION OF THIS
 *  SOFTWARE WITHOUT SPECIFIC, WRITTEN PRIOR AUTHORIZATION.  IF THE ABOVE
 *  COPYRIGHT NOTICE OR ANY OTHER IDENTIFICATION OF MICHIGAN STATE UNIVERSITY
 *  IS INCLUDED IN ANY COPY OF ANY PORTION OF THIS SOFTWARE, THEN THE
 *  DISCLAIMER BELOW MUST ALSO BE INCLUDED.
 *
 *  THIS SOFTWARE IS PROVIDED AS IS, WITHOUT REPRESENTATION FROM MICHIGAN
 *  STATE UNIVERSITY AS TO ITS FITNESS FOR ANY PURPOSE, AND WITHOUT WARRANTY
 *  BY MICHIGAN STATE UNIVERSITY OF ANY KIND, EITHER EXPRESS OR IMPLIED,
 *  INCLUDING WITHOUT LIMITATION THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *  AND FITNESS FOR A PARTICULAR PURPOSE. THE MICHIGAN STATE UNIVERSITY BOARD
 *  OF TRUSTEES SHALL NOT BE LIABLE FOR ANY DAMAGES, INCLUDING SPECIAL,
 *  INDIRECT, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, WITH RESPECT TO ANY CLAIM
 *  ARISING OUT OF OR IN CONNECTION WITH THE USE OF THE SOFTWARE, EVEN IF IT
 *  HAS BEEN OR IS HEREAFTER ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 */

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "compare_fields_validator",
 *   label = @Translation("Validate Entries by Comparing 2 fields"),
 *   category = @Translation("Validation"),
 *   description = @Translation("This validates two webform fields by ensuring that the comparison relationship (e.g. greater than) applies properly."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class CompareFieldsWebformHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('logger.factory'),
    $container->get('config.factory'),
    $container->get('entity_type.manager'),
    $container->get('webform_submission.conditions_validator'),
    $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $config = $this->configuration;
    // Fetch the Compare Fields label.
    $summary = [
      '#markup' => $config['left_side'] . ' ' . $config['operator'] . ' ' . $config['right_side'] . ' as ' . $config['datatype'],
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    // Message.
    $form['compare'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Comparison settings'),
      '#tree' => TRUE,
    ];
    $form['compare']['left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name of field for left side of comparison.'),
      '#default_value' => $this->configuration['left_side'],
      '#required' => TRUE,
    ];
    $form['compare']['operator'] = [
      '#type' => 'textfield',
      '#title' => $this->t(
      'Comparison Operator (must be a <a href="@url">PHP comparison opeartor</a>.',
      ['@url' => Url::fromUri('https://www.php.net/manual/en/language.operators.comparison.php')]
      ),
      '#default_value' => $this->configuration['operator'],
      '#required' => TRUE,
    ];
    $form['compare']['right'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name of field for right side of comparison.'),
      '#default_value' => $this->configuration['right_side'],
      '#required' => TRUE,
    ];
    $form['compare']['datatype'] = [
      '#type' => 'radios',
      '#title' => $this->t('Translate and compare value as this data type.'),
      '#default_value' => $this->configuration['datatype'],
      '#options' => [
        'datetime' => 'Date/DateTime',
        'string' => 'String',
        'number' => 'Numeric (float/int)',
        'bool' => 'Boolean (true/false)',
      ],
      '#required' => TRUE,
    ];
    $form['compare']['errorField'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field to highlight if validation fails.'),
      '#description' => $this->t('If left blank, will default to be the same as the left side of the comparison.  If you wish to highlight both sides of a comparison, nest them in a container or fieldset and then put the machine name of that container/fieldset in this field.'),
      '#default_value' => $this->configuration['errorField'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::validateConfigurationForm($form, $formState);
    $values = $formState->getValues();
    // Ensure operator is valid.
    if (!in_array($values['compare']['operator'], [
      '<=>',
      '==',
      '===',
      '!=',
      '<>',
      '!==',
      '<',
      '>',
      '<=',
      '>=',
    ])) {
      $formState->setErrorByName('compare][operator', $this->t('%operator is not a valid php comparison operator.', ['%operator' => $values['compare']['operator']]));
    }

    // Ensure left and right side values are valid 'machine names'.
    foreach ([
      'left' => $values['compare']['left'],
      'right' => $values['compare']['right'],
      'errorField' => $values['compare']['errorField'],
    ] as $side => $side_id) {
      // Verify that the field id not only consists of replacement tokens.
      if (preg_match('@^_+$@', $side_id)) {
        $formState
          ->setErrorByName('compare][' . $side, t('The %side name (%side_id) must contain unique characters.',
        ['%side' => $side, '%side_id' => $side_id]));
      }

      // Verify that the field_id contains no disallowed characters.
      if (preg_match('@^(?:[^a-z0-9_])+$@', $side_id)) {
        $formState
          ->setErrorByName('compare][' . $side, t('The %side name (%side_id) name must contain only lowercase letters, numbers, and underscores.',
            ['%side' => $side, '%side_id' => $side_id]));
      }
    }

    $formState->setValues($values);
    $this->applyFormStateToConfiguration($formState);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);
    $this->configuration['left_side'] = $formState->getValue(['compare', 'left']);
    $this->configuration['operator'] = $formState->getValue(['compare', 'operator']);
    $this->configuration['right_side'] = $formState->getValue(['compare', 'right']);
    $this->configuration['datatype'] = $formState->getValue(['compare', 'datatype']);
    if (!empty($formState->getValue(['compare', 'errorField']))) {
      $this->configuration['errorField'] = $formState->getValue(['compare', 'errorField']);
    }
    else {
      $this->configuration['errorField'] = $this->configuration['left_side'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webform_submission) {
    $this->compareFields($formState);
  }

  /**
   * Validate fields.
   */
  private function compareFields(FormStateInterface $formState) {
    $left_id = $this->configuration['left_side'];

    $left_val = $left_orig = $formState->getValue($left_id);
    $operator = $this->configuration['operator'];
    $right_id = $this->configuration['right_side'];
    $right_val = $right_orig = $formState->getValue($right_id);
    $datatype = $this->configuration['datatype'];
    $errorField = $this->configuration['errorField'];

    // Convert values as needed.
    switch ($datatype) {
      case 'datetime':
        $left_val = strtotime($left_orig);
        $right_val = strtotime($right_orig);
        break;

      case 'number':
        $left_val = (float) $left_orig;
        $right_val = (float) $right_orig;
        break;

      case 'bool':
        $left_val = (bool) $left_orig;
        $right_val = (bool) $right_orig;
        break;

    }

    // Validate based on operator.
    switch ($operator) {
      case '<':
        if (!($left_val < $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must be less than %right_id (e.g. %left_orig < %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '<=':
        if (!($left_val <= $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must be less than or equal to %right_id (e.g. %left_orig <= %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '>':
        if (!($left_val > $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must be greater than %right_id (e.g. %left_orig > %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '>=':
        if (!($left_val >= $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must be greater than or equal to %right_id (e.g. %left_orig >= %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '==':
        if (!($left_val == $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must equal %right_id (e.g. %left_orig == %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '===':
        if (!($left_val < $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must equal %right_id (e.g. %left_orig === %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '!=':
        if (!($left_val != $right_val)) {
          $formState->setErrorByName(
          $errorField,
          $this->t('The value of %left_id must not equal %right_id (e.g. %left_orig != %right_orig)',
          [
            '%left_id' => $left_id,
            '%right_id' => $right_id,
            '%left_orig' => $left_orig,
            '%right_orig' => $right_orig,
          ]));
        }
        break;

      case '!==':
        if (!($left_val !== $right_val)) {
          $formState->setErrorByName(
            $errorField,
            $this->t('The value of %left_id must not equal %right_id (e.g. %left_orig !== %right_orig)',
            [
              '%left_id' => $left_id,
              '%right_id' => $right_id,
              '%left_orig' => $left_orig,
              '%right_orig' => $right_orig,
            ]));
        }
        break;

    }
  }

}
