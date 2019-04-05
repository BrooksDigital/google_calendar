<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google Calendar Event entities.
 *
 * @ingroup google_calendar
 */
interface GoogleCalendarEventInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Google Calendar Event name.
   *
   * @return string
   *   Name of the Google Calendar Event.
   */
  public function getName();

  /**
   * Sets the Google Calendar Event name.
   *
   * @param string $name
   *   The Google Calendar Event name.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setName($name);

  /**
   * Gets the Google Calendar Event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar Event.
   */
  public function getCreatedTime();

  /**
   * Sets the Google Calendar Event creation timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar Event creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Google Calendar Event published status indicator.
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Google Calendar Event.
   *
   * @param bool $published
   *   TRUE to set this Google Calendar Event to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setPublished($published);

}
