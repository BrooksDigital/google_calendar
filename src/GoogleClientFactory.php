<?php

namespace Drupal\google_calendar;

use Google_Client;
use GuzzleHttp\Client;
/**
 * Class GoogleClient.
 *
 * @package Drupal\google_calendar
 */
class GoogleClientFactory {

  /**
   * Return a configured Client object.
   */
  public function get() {
    $client = new Google_Client();

    $client->setAuthConfig( __DIR__ . '/../client_secret.json');
    $client->setScopes([
      'https://www.googleapis.com/auth/calendar',
      'https://www.googleapis.com/auth/drive',
      'https://www.googleapis.com/auth/drive.file',
      'https://www.googleapis.com/auth/youtube.force-ssl'
    ]);


    // config HTTP client and config timeout
    $client->setHttpClient(new Client([
      'timeout' => 10,
      'connect_timeout' => 10,
      'verify' => false
    ]));
    
    return $client;
  }
}
