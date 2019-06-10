<?php
/**
 * Created by PhpStorm.
 * User: dtrafton
 * Date: 1/9/18
 * Time: 11:18 AM
 */

namespace Drupal\google_calendar\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_calendar\GoogleCalendarImportEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 *
 * @QueueWorker(
 *   id = "google_calendar_import_processor",
 *   title = "Google Calendar Import Processor",
 *   cron = {"time" = 60}
 * )
 */
class CalendarImportProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Storage for the class which will actually process each item.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportEvents
   */
  protected $calendarImport;

  /**
   * Constructs a CalendarImportProcessor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param GoogleCalendarImportEvents $calendar_import
   *   Class to perform the item processing.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GoogleCalendarImportEvents $calendar_import) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->calendarImport = $calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_calendar.sync_events')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($calendar) {
    $this->calendarImport->import($calendar);
  }

}