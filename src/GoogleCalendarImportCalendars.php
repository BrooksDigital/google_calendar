<?php

namespace Drupal\google_calendar;

use DateTime;
use DateTimeZone;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Google_Client;
use Google_Service_Calendar;
use Drupal\user\Entity\User;
use Google_Service_Exception;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\Entity\GoogleCalendarEvent;

/**
 * Class GoogleCalendarImportCalendars.
 */
class GoogleCalendarImportCalendars {

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

  /**
   * @var
   */
  protected $config;

  /**
   * EntityTypeManager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('google_calendar');
    $this->config = $config->get('google_calendar');
  }

  public function import() {


    return TRUE;
  }

}
