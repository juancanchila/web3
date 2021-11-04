<?php

namespace Drupal\webform_entity_email\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_entity_email\EntityEmailTrait;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "entity_email",
 *   label = @Translation("Entity email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a rendered entity by email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class EntityEmailWebformHandler extends EmailWebformHandler {

  use EntityEmailTrait;

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PMD.BooleanArgumentFlag)
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if ($this->configuration['states'] && in_array($state, $this->configuration['states'])) {
      $message = $this->getMessage($webform_submission);
      if (!empty($message)) {
        $this->sendMessage($webform_submission, $message);
      }
    }
  }

}
