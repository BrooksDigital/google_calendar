<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google Calendar entities.
 *
 * @ingroup google_calendar
 */
interface GoogleCalendarInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Google Calendar name.
   *
   * @return string
   *   Name of the Google Calendar.
   */
  public function getName();

  /**
   * Sets the Google Calendar name.
   *
   * @param string $name
   *   The Google Calendar name.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setName($name);

  /**
   * Gets the Google Calendar creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar.
   */
  public function getCreatedTime();

  /**
   * Sets the Google Calendar creation timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Google Calendar published status indicator.
   *
   * Unpublished Google Calendar are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Google Calendar.
   *
   * @param bool $published
   *   TRUE to set this Google Calendar to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setPublished($published);

}
