<?php

namespace Drupal\google_calendar\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Google Calendar Event entities.
 */
class GoogleCalendarEventViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here. Something like:
//    $data['google_calendar_event']['table']['join']['google_calendar_field_data'] = [
//      'left_field' => 'id',
//      'field' => 'google_calendar',
//    ];

    return $data;
  }

}
