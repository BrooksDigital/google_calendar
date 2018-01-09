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

    $secret_uri = \Drupal::config('google_calendar.default')->get('secret_file_uri');
    $secret_file = \Drupal::service('file_system')->realpath($secret_uri);
    $client->setAuthConfig($secret_file);
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
