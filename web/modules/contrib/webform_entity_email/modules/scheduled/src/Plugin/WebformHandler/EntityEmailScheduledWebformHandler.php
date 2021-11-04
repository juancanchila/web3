<?php

namespace Drupal\webform_entity_email_scheduled\Plugin\WebformHandler;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_entity_email\EntityEmailTrait;
use Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "entity_email_scheduled",
 *   label = @Translation("Entity email scheduled"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a rendered entity by email, scheduled"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class EntityEmailScheduledWebformHandler extends ScheduleEmailWebformHandler {

  use EntityEmailTrait;

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getState();
    $message = $this->getMessage($webform_submission);
    if (!empty($message)) {
      if (in_array($state, $this->configuration['states'])) {
        $this->scheduleMessage($webform_submission);
      }
      elseif ($this->configuration['unschedule']) {
        $this->unscheduleMessage($webform_submission);
      }
    }
  }

}
