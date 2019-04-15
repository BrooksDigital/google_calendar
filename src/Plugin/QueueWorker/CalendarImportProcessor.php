<?php
/**
 * Created by PhpStorm.
 * User: dtrafton
 * Date: 1/9/18
 * Time: 11:18 AM
 */

namespace Drupal\google_calendar\Plugin\QueueWorker;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_calendar\GoogleCalendarImport;
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
   * The importer class.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportEvents
   */
  protected $calendarImport;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, GoogleCalendarImportEvents $calendar_import) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->calendarImport = $calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\google_calendar\GoogleCalendarImportEvents $importer */
    $importer = $container->get('google_calendar.import_events');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $importer
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($calendar) {
    $this->calendarImport->import($calendar);
  }

}