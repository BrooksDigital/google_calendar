<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Google Calendar import forms.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarImportCalendarsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_import_calendars_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\google_calendar\Entity\GoogleCalendar */
    $form = parent::buildForm($form, $form_state);

    $entities = \Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);
    foreach ($entities as $entity) {
      $index[$entity->id()] = $entity->id();
    }
    $imported = [];
    $toimport = [];

    $list = $this->service->calendarList->listCalendarList();

    $items = $list->getItems();
    /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
    foreach ($items as $calendar) {
//      $cal = [
//        'id' => $calendar->getId(),
//        'primary' => $calendar->getPrimary() ? 'Yes' : 'No',
//        'name' => $calendar->getSummary(),
//        'desc' => $calendar->getDescription(),
//        'locn' => $calendar->getLocation(),
//        'colour' => $calendar->getForegroundColor() . ' on ' . $calendar->getBackgroundColor(),
//      ];
      if (array_key_exists($calendar->getId(), $index)) {
        $imported[] = $calendar->getId();
      }
      else {
        $toimport[] = $calendar->getId();
      }
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Status'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No calendars are available.'),
    ];
    foreach ($items as $calendar) {
      $id = $calendar->getId();
      /* Build Status */
      if (isset($imported[$id])) {
        $status = t('Imported');
      }
      elseif (isset($toimport[$id])) {
        $status = t('Not Imported');
      }
      else {
        $status = t('Not Known');
      }

      /* Build links */
      $links = [];
      $links['import'] = [
        'title' => t('Import Calendar'),
        'url' => Url::fromRoute('google_calendar.calendar_import', ['calendar' => $calendar->getId()]),
      ];
      $links['drop'] = [
        'title' => t('Drop Calendar'),
        'url' => Url::fromRoute('google_calendar.calendar_drop', ['calendar' => $calendar->getId()]),
      ];

      // Build the table row.
      $row = [];
      /* Name */
      $row[] = $calendar->getSummary();
      /* Status */
      $row[] = $status;
      /* Operations */
      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }
//
//    $form['actions']['submit'] = [
//      '#type' => 'submit',
//      '#value' => t('Import Calendars'),
//    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $form_state->setRedirect('entity.google_calendar.collection');
  }
}
