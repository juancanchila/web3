<?php

namespace Drupal\webform_calculation\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform Calculation handler.
 *
 * @WebformHandler(
 *   id = "webform_calculation",
 *   label = @Translation("Webform Calculation"),
 *   category = @Translation("Webform Calculation"),
 *   description = @Translation("Webform Calculation submission handler."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformCalculationWebformHandler extends WebformHandlerBase {

  /**
   * The input that is used as default values after the form has been submitted.
   */
  protected $formUserInput;

  /* @var WebformSubmissionInterface $submission */
  protected $submission;

  /**
   * Webform element setting to check while looking for the field with formula.
   * The setting should be set to the field (e.g. on the Advanced tab in section
   * with custom properties).
   */
  private const CALCULATION_SETTING = '#calculation';

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    if (!$this->formUserInput) {
      return;
    }
    // Set submitted values to use them as default ones (the form is rebuilt
    // while being submitted).
    $form_state->setUserInput($this->formUserInput);

    // Set evaluated data to the end of the form.
    $form['evaluated_result'] = [];
    foreach ($this->submission->getWebform()->getElementsDecoded() as $name => $element) {
      if (empty($element[self::CALCULATION_SETTING])) {
        continue;
      }

      $form['evaluated_result'][] = [
        '#markup' => $this->submission->getElementData($name)[0]['result'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->formUserInput = $form_state->getUserInput();
    $this->submission = $webform_submission;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webformSubmission) {
    $calculationElements = $this->getCalculationElements($webformSubmission);
    if (!$calculationElements) {
      return;
    }

    // Unset elements with formula from the list as they are not available
    // during evaluations.
    $extractableVariables = array_diff_key($webformSubmission->getData(), $calculationElements);
    $files = \Drupal::service('file_system')->scanDirectory(WEBFORM_CALCULATION_LIBRARY, '/\.php$/');
    foreach ($files as $file => $data) {
      include_once $file;
    }

    foreach ($calculationElements as $element) {
      $evaluatedData = $this->evaluateElement($webformSubmission, $element, $extractableVariables);
      $webformSubmission->setElementData($element, [$evaluatedData]);
    }
  }

  /**
   * Helper method to retrieve Webform elements that provide formula.
   */
  protected function getCalculationElements(WebformSubmissionInterface $webformSubmission) {
    $calculationElements = [];
    foreach ($webformSubmission->getWebform()->getElementsDecoded() as $name => $element) {
      if (!empty($element[self::CALCULATION_SETTING])) {
        $calculationElements[$name] = $name;
      }
    }

    return $calculationElements;
  }

  /**
   * Populates Webform element with evaluated result.
   *
   * Uses formula from the element.
   */
  protected function evaluateElement(WebformSubmissionInterface $webformSubmission, $element, $extractableVariables) {
    $evaluation = $webformSubmission->getData()[$element][0];
    $result = [
      '#submission' => $webformSubmission,
      '#result' => $this->evaluateFormula($evaluation['formula'], $extractableVariables),
      '#element' => $element,
      '#theme' => ['webform_calculation'],
    ];
    $evaluation['result'] = \Drupal::service('renderer')->render($result);

    return $evaluation;
  }

  /**
   * Evaluates formula from the Webform element.
   */
  protected function evaluateFormula($formula, $extractableVariables) {
    $matches = [];
    if (!preg_match('/^(new)?\s?'.
      // Expression to match class or function name. Allows usage of spaces
      // before braces.
      '([a-zA-Z_]+?[a-zA-Z_0-9]*)\s*' .
      '(' .
      // Match single argument, may be represented as an array, like
      // [$var, $another_var] or ['first_field' => $first_field,
      // 'second_field' => $second_field].
      '(\(\[?([\$\'\"][a-zA-Z_]+[a-zA-Z\d_\s\$\'\"=>]*)*?\]?\);)' .
      '|' .
      // Match multiple arguments where each argument may be represented as
      // an array.
      '(\(' .
      '\[?([\$\'\"][a-zA-Z_]+[a-zA-Z\d_\s\$\'\"\s=>]*,?\s*)+?\]?,\s*\[?([\$\'\"][a-zA-Z_]+[a-zA-Z\d_\s\$\'\"=>]*)\]?' .
      '\);)' .
      ')$/', $formula, $matches)) {

      \Drupal::logger('webform_calculation')->error('Can not parse Calculation field input.');
      return NULL;
    }

    // Extract submitted values, so that they are available in the eval
    // statement.
    extract($extractableVariables, EXTR_SKIP);

    // If the field contains 'new' at the beginning then it is an object
    // instantiation.
    if ($matches[1] === 'new') {
      $className = $matches[2];
      // Use autoload to check class existence as the class may not be
      // instantiated at this moment.
      if (!class_exists($className, TRUE)) {
        \Drupal::logger('webform_calculation')->error('Class %class_name does not exist.', [
          '%class_name' => $className,
        ]);

        return NULL;
      }
    }
    else {
      // Otherwise it is a function call.
      $function_name = $matches[2];
      if (!function_exists($function_name)) {
        \Drupal::logger('webform_calculation')->error('Function %function_name does not exist.', [
          '%function_name' => $function_name,
        ]);

        return NULL;
      }
    }

    $preparedSignature = 'return ' . $formula;
    // Hide Notice message as there may be some undefined variable used by the
    // user.
    $prevErrorReporting = error_reporting();
    error_reporting(E_ALL ^ E_NOTICE);
    $result = eval($preparedSignature);
    // Revert back previous error_reporting setting.
    error_reporting($prevErrorReporting);
    return $result;
  }

}
