<?php

namespace Drupal\webform_summation_field\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a webform element for an webform_summation_field.
 *
 * @FormElement("webform_summation_field")
 */
class WebformSummationField extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#size' => 60,
      '#value' => 0,
      '#pre_render' => [
        [$class, 'preRenderWebformSummationFieldElement'],
      ],

      '#theme' => 'webform_summation_field',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformSummationFieldElement($element) {
    $element['#theme_wrappers'][] = 'form_element';
    $element['#wrapper_attributes']['id'] = $element['#id'] . '--wrapper';
    $element['#wrapper_attributes']['style'] = 'display: none';
    $element['#attributes']['id'] = $element['#id'];
    $element['#attributes']['name'] = $element['#name'];
    $element['#attributes']['value'] = $element['#value'];

    // Add class name to wrapper attributes.
    $class_name = str_replace('_', '-', $element['#type']);
    static::setAttributes($element, ['js-' . $class_name, $class_name]);
    return $element;
  }

}
