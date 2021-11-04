<?php

namespace Drupal\webform_entity_email;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allow a webform email handler to send node as email.
 *
 * @package Drupal\webform_entity_email
 */
trait EntityEmailTrait {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Used to get the available displays for the current entity type.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'entity_email' => [
        'element_name' => '',
        'entity_type' => '',
        'view_mode' => '',
        'theme' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['message']);

    $form['entity_email'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity email'),
    ];

    $form['entity_email']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#default_value' => $this->configuration['entity_email']['entity_type'],
    ];

    $this->buildAjaxElementTrigger('webform-entity-email-view-modes', $form['entity_email']['entity_type']);
    $this->buildAjaxElementUpdate('webform-entity-email-view-modes', $form['entity_email']);

    foreach ($this->entityTypeManager->getDefinitions() as $key => $definition) {
      if (is_subclass_of($definition->getClass(), ContentEntityBase::class)) {
        $form['entity_email']['entity_type']['#options'][$key] = $definition->getLabel();
      }
    }

    $form['entity_email']['view_mode'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];

    if (!empty($this->configuration['entity_email']['entity_type'])) {
      $available_displays = $this->entityDisplayRepository->getViewModes($this->configuration['entity_email']['entity_type']);
      if (!empty($available_displays)) {
        $displays_options = [];
        foreach ($available_displays as $key => $display) {
          $displays_options[$key] = $display['label'];
        }
        $form['entity_email']['view_mode'] = [
          '#type' => 'select',
          '#title' => $this->t('View mode'),
          '#options' => $displays_options,
          '#default_value' => $this->configuration['entity_email']['view_mode'],
        ];
      }
    }
    $this->buildAjaxElementWrapper('webform-entity-email-view-modes', $form['entity_email']['view_mode']);

    $form['entity_email']['element_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity field'),
      '#default_value' => $this->configuration['entity_email']['element_name'],
    ];
    foreach ($this->getWebform()->getElementsInitializedAndFlattened() as $key => $element) {
      $form['entity_email']['element_name']['#options'][$key] = $element['#title'];
    }

    $form['entity_email']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['entity_email']['theme'],
      '#options' => $this->themeManager->getThemeNames(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();

    $nid = !empty($data[$this->configuration['entity_email']['element_name']]) ? $data[$this->configuration['entity_email']['element_name']] : NULL;
    if (!empty($nid)) {
      $entity = $this->entityTypeManager->getStorage($this->configuration['entity_email']['entity_type'])->load($nid);
      $current_language = $this->languageManager->getCurrentLanguage()->getId();
      if ($entity instanceof TranslatableInterface && $entity->hasTranslation($current_language)) {
        $entity = $entity->getTranslation($current_language);
      }
      $body_render = $this->entityTypeManager->getViewBuilder($this->configuration['entity_email']['entity_type'])->view($entity, $this->configuration['entity_email']['view_mode']);
      $message = parent::getMessage($webform_submission);
      $message['subject'] = $entity->label();
      $message['body'] = $this->themeManager->renderPlain($body_render, $this->configuration['entity_email']['theme']);
      return $message;
    }

  }

}
