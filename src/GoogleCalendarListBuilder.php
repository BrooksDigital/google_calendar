<?php

namespace Drupal\google_calendar;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Google Calendar entities.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Google Calendar ID');
    $header['last_sync'] = $this->t('Last synced');
    $header['number'] = $this->t('Events Loaded');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity \Drupal\google_calendar\Entity\GoogleCalendar */
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('google_calendar_event');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('calendar', $entity->id());
    $num = $query->count()->execute();

    $last_synced = \Drupal::service('date.formatter')->format($entity->getLastSyncTime(), 'medium');

    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'view.google_calendar_events.calendar_list',
      ['google_calendar' => $entity->id()]
    );
    $row['id'] = $entity->getGoogleCalendarId();
    $row['last_sync'] = $last_synced;
    $row['number'] = $num;
    return $row + parent::buildRow($entity);
  }

}
