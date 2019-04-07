<?php

namespace Drupal\google_calendar;


use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Drupal\user\Entity\User;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Exception;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\Entity\GoogleCalendarEvent;

use DateTime;
use DateTimeZone;

/**
 * Class GoogleCalendarImportEvents.
 */
class GoogleCalendarImportEvents {

  /**
   * @var int stats
   */
  protected $modify_events = 0;
  protected $saved_events = 0;
  protected $created_events = 0;
  protected $page_count = 0;
  protected $new_events = 0;

  /**
   * @var int Maximum pages to import at once.
   */
  protected $maxPages = 2;

  /**
   * Google Calendar service definition.
   *
   * @var \Google_Service_Calendar
   */
  protected $service;


  /**
   * Logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  protected $config;

  /**
   * EntityTypeManager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function getStatNewEvents() { return $this->new_events; }
  public function getStatModifyEvents() { return $this->modify_events; }
  public function getStatCreatedEvents() { return $this->created_events; }
  public function getStatSavedEvents() { return $this->saved_events; }
  public function getStatPageCount() { return $this->page_count; }

  /**
   * GoogleCalendarImport constructor.
   *
   * @param \Google_Client $googleClient
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(Google_Client $googleClient, ConfigFactory $config,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {

    $this->service = new Google_Service_Calendar($googleClient);
    $this->config = $config->getEditable('google_calendar.last_imports');
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('google_calendar');
  }

  public function import(GoogleCalendar $calendar, $ignoreSyncToken = FALSE) {

    $calendarId = $calendar->getGoogleCalendarId();
    $configKey = "config_for_calendar_$calendarId";
    $syncToken = $ignoreSyncToken ? NULL : $this->config->get($configKey);

    $googleCalendar = $this->service->calendars->get($calendarId);

    // init dummy page token
    $nextPageToken = NULL;
    // Stats
    $this->new_events = 0;
    $this->modify_events = 0;
    $this->saved_events = 0;
    $this->created_events = 0;

    // Page count limit.
    $this->pageCount = 0;

    do {
      $page = $this->getPage($calendarId, $syncToken, $nextPageToken);

      if (!$page) {
        return FALSE;
      }

      $nextPageToken = $page->nextPageToken;
      $nextSyncToken = $page->nextSyncToken;
      $items = $page->getItems();
      if (count($items) > 0) {
        $this->syncEvents($items, $calendar, $googleCalendar->getTimeZone());
      }
      $this->pageCount++;
    } while ($nextPageToken && $this->pageCount < $this->maxPages);

    //set sync token
    $this->config->set($configKey, $nextSyncToken);
    $this->config->save();

    $this->logger->info("Calendar: @calendar imported successfully.", [
      '@calendar' => $calendar->label()
    ]);

    return $calendar;
  }

  /**
   * Request a page of calendar events for a calendar-id
   *
   * @param string $calendarId
   *   Calendar identifier.
   * @param string $syncToken
   *   Token obtained from the nextSyncToken field returned on the last page of
   *   results from the previous list request.
   * @param string $pageToken
   *   Token specifying which result page to return. Optional.
   *
   * @return bool|\Google_Service_Calendar_Events
   *
   * @see https://developers.google.com/calendar/v3/reference/events/list
   */
  private function getPage($calendarId, $syncToken, $pageToken = NULL) {

    // also 'showDeleted', 'q', 'timeMax', 'timeZone', 'updatedMin', 'maxResults'.
    // default maxResults is 250 per page.

    $opts = [
      'pageToken' => $pageToken,
      'singleEvents' => TRUE,  // expand recurring events into instances.
    ];

    if ($syncToken) {
      $opts['syncToken'] = $syncToken;
    }
    else {
      $opts['orderBy'] = 'startTime';  // or 'updated'; 'startTime' requires 'singleEvents'=true
      $opts['timeMin'] = date(DateTime::RFC3339, strtotime("-1 day"));
    }

    try {
      $response = $this->service->events->listEvents($calendarId, $opts);
    }
    catch (Google_Service_Exception $e) {
      // Catch token expired and re-pull
      if ($e->getCode() == 410) {
        $response = $this->getPage($calendarId, NULL);
      }
      else {
        $response = FALSE;
      }
    }

    return $response;
  }

  /**
   * Given a list of events, add or update the corresponding Calendar Entities.
   *
   * @param \Google_Service_Calendar_Event[] $events
   * @param GoogleCalendar $calendar
   * @param string $timezone
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function syncEvents($events, $calendar, $timezone) {

    // Get list of event Ids
    $eventIds = [];
    foreach ($events as $event) {
      $eventIds[] = $event['id'];
    }
    $this->new_events += count($eventIds);

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager
      ->getStorage('google_calendar_event');

    // Query to get list of existing events
    $query = $storage
      ->getQuery('AND')
      ->condition('event_id', $eventIds, 'IN');

    $drupalEventIds = $query->execute();

    $drupalEvents = GoogleCalendarEvent::loadMultiple($drupalEventIds);

    // Index the existing event nodes by Google Calendar Id for easier lookup
    $indexedEvents = [];
    foreach ($drupalEvents as $event) {
      $indexedEvents[$event->getGoogleEventId()] = $event;
    }

    $this->modify_events = +count($indexedEvents);

    // Iterate over events and update Drupal nodes accordingly
    foreach ($events as $event) {
      // Get the event node
      $eventEntity = $indexedEvents[$event['id']] ?? NULL;

      // Cutoff for deleted events
      if ($event['status'] == 'cancelled') {
        if ($eventEntity) {
          // if event is cancelled and we have an associated event node, remove it
          $eventEntity->delete();
        }
        continue;
      }

      // Handle created or updated dates: Google supplies values such as
      //   "2010-01-09T16:06:35.311Z"
      // which is almost but not quite RFC3339/ISO8601: 3 digit fractional
      // seconds is neither RFC3339_EXTENDED nor RFC3339 compatible,
      // though it is a perfectly valid date representation.
      //
      // NB: Event dates are not stored with sub-second accuracy and so do
      // not suffer this problem.
      $createdDate = DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $event['created']);
      if (is_object($createdDate) && $createdDate->format('Y') > 1970) {
        $createdDate = $createdDate->getTimestamp();
      }
      else {
        $createdDate = 0;
      }

      $updatedDate = DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $event['updated']);
      if (is_object($updatedDate) && $updatedDate->format('Y') > 1970) {
        $updatedDate = $updatedDate->getTimestamp();
      }
      else {
        $updatedDate = 0;
      }

      // For start and end the 'date' value is set only when there is no time
      // component for the event, so check 'date' first, then if not set get
      // both date and time from 'dateTime'.
      $startDate = $event['start']['date'] ?
        new DateTime($event['start']['date'], new DateTimeZone($timezone))
        : DateTime::createFromFormat(DateTime::ISO8601, $event['start']['dateTime']);
      $startDate = $startDate->setTimezone(new DateTimeZone('UTC'))->getTimestamp();

      $endDate = $event['end']['date'] ?
        new DateTime($event['end']['date'], new DateTimeZone($timezone))
        : DateTime::createFromFormat(DateTime::ISO8601, $event['end']['dateTime']);
      $endDate = $endDate->setTimezone(new DateTimeZone('UTC'))->getTimestamp();

      // If possible, assign the drupal owner of this entity from the organiser email.
      $user_email = user_load_by_mail($event['organizer']->email);
      if ($user_email) {
        $user_id = $user_email->id();
      }
      else {
        $user_id = User::getAnonymousUser()->id();
      }

      // Config fields
      $fields = [

        'user_id' => [
          'target_id' => $user_id,
        ],

        'name' => $event['summary'],

        'event_id' => [
          'value' => $event['id'],
        ],

        'ical_id' => [
          'value' => $event['iCalUID'],
        ],

        'google_link' => [
          'uri' => $event['htmlLink'],
          'utitleri' => $event['summary'],
        ],

        'calendar' => [
          'target_id' => $calendar->id(),
        ],

        'start_date' => [
          'value' => $startDate,
        ],

        'end_date' => [
          'value' => $endDate,
        ],

        'description' => [
          'value' => $event['description'],
          'format' => 'basic_html',
        ],

        'location' => [
          'value' => $event['location'],
        ],

        'locked' => [
          'value' => $event['locked'] ?? FALSE,
        ],

        'etag' => [
          'value' => $event['etag'],
        ],

        'transparency' => [
          'value' => $event['transparency'],
        ],

        'visibility' => [
          'value' => $event['visibility'],
        ],

        'guests_invite_others' => [
          'value' => $event['guestsCanInviteOthers'],
        ],

        'guests_modify' => [
          'value' => $event['guestsCanModify'],
        ],

        'guests_see_invitees' => [
          'value' => $event['guestsCanSeeOtherGuests'],
        ],

        'state' => [
          'value' => $event['status'],
        ],

        'organizer' => [
          'value' => $event['organizer']->displayName,
        ],

        'organizer_email' => [
          'value' => $event['organizer']->email,
        ],

        'creator' => [
          'value' => $event['creator']->displayName,
        ],

        'creator_email' => [
          'value' => $event['creator']->email,
        ],

        'created' => [
          'value' => $createdDate,
        ],

        'updated' => [
          'value' => $updatedDate,
        ],

      ];


      if (!$eventEntity) {
        $eventEntity = GoogleCalendarEvent::create($fields);
        $this->created_events++;
      }
      else {
        // Update the existing node in place
        foreach ($fields as $key => $value) {
          $eventEntity->set($key, $value);
        }
        $this->saved_events++;
      }

      // Save it!
      $eventEntity->save();
    }

    $this->logger->info('Sync "@calendar": @new_events fetched, @created_events created, @modify_events to update and @saved_events updated.',
                        [
                          '@calendar' => $calendar->getName(),
                          '@new_events' => $this->new_events,
                          '@modify_events' => $this->modify_events,
                          '@created_events' => $this->created_events,
                          '@saved_events' => $this->saved_events,
                        ]);
  }

}
