<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Google Calendar entities.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $message = $this->getDeletionMessage();

    // Also delete events associated with this calendar.
    $eventsToDelete = \Drupal::entityTypeManager()
      ->getStorage('google_calendar_event')
      ->loadByProperties(['calendar' => $entity->id()]);

    foreach ($eventsToDelete as $event) {
      $event->delete();
    }

    // Finally, delete the parent calendar.
    $entity->delete();
    $form_state->setRedirectUrl($this->getRedirectUrl());

    $this->messenger()->addStatus($message);
    $this->logDeletionMessage();
  }

}
