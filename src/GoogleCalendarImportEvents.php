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

  protected $maxPages = 2;

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

    $pageCount = 0;
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
      $pageCount++;
    } while ($nextPageToken && $pageCount < $this->maxPages);

    //set sync token
    $this->config->set($configKey, $nextSyncToken);
    $this->config->save();

    $this->logger->info("Calendar: @calendar imported successfully.", [
      '@calendar' => $calendar->label()
    ]);

    return TRUE;
  }

  private function getPage($calendarId, $syncToken, $pageToken = NULL) {
    $opts = [
      'pageToken' => $pageToken,
      'singleEvents' => TRUE,
//        'fields' => 'nextPageToken,nextSyncToken,items('
//            .'id,status,summary,description,notes'
//            .',location,start,end,attachments'
//            .',recurrence,recurringEventId,transparency,attendees,conference'
//            .',htmlLink,endTimeUnspecified,guestsCanInviteOthers,guestsCanModify'
//            .',guestsCanSeeOtherGuests,privateCopy,locked,reminders'
//            .',creator,organizer,extendedProperties'
//          .')'
    ];

    if ($syncToken) {
      $opts['syncToken'] = $syncToken;
    }
    else {
      $opts['orderBy'] = 'startTime';
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

  private function syncEvents($events, $calendar, $timezone) {

    // Get list of event Ids
    $eventIds = [];
    foreach ($events as $event) {
      $eventIds[] = $event['id'];
    }
    $new_events = count($eventIds);

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

    $modify_events = count($indexedEvents);
    $saved_events = 0;
    $created_events = 0;

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
        $created_events++;
      }
      else {
        // Update the existing node in place
        foreach ($fields as $key => $value) {
          $eventEntity->set($key, $value);
        }
        $saved_events++;
      }

      // Save it!
      $eventEntity->save();
    }

    $this->logger->info("Sync @calendar: @new_events fetched from Google, @created_events created, @modify_events to update and @saved_events updated.",
                        [
                          '@calendar' => $calendar->label(),
                          '@new_events' => $new_events,
                          '@modify_events' => $modify_events,
                          '@created_events' => $created_events,
                          '@saved_events' => $saved_events,
                        ]);
  }

}
