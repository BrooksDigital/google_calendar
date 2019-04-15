<?php

namespace Drupal\datetime;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for date interval from two date field items.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - start date: The field containing the start of the date interval.
 *  - end date: The field containing the end of the date interval.
 *  - end set: Boolean, true if end date is defined.
 */
class EventDurationComputed extends TypedData {

  /**
   * Cached computed date.
   *
   * @var \DateInterval|null
   */
  protected $dateinterval = NULL;
  protected $start_field = NULL;
  protected $end_field = NULL;
  protected $endset_field = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->start_field = $definition->getSetting('start date');
    if (!$this->start_field) {
      throw new \InvalidArgumentException("The definition's 'start date' key must specify the name of the start-date field to be computed.");
    }
    $this->end_field = $definition->getSetting('end date');
    if (!$this->end_field) {
      throw new \InvalidArgumentException("The definition's 'end date' key must specify the name of the end-date field to be computed.");
    }
    $this->endset_field = $definition->getSetting('end set');
    if (!$this->endset_field) {
      throw new \InvalidArgumentException("The definition's 'end set' key must specify the name of the end-is-specified field.");
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getValue() {
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    try {
      $start_timestamp = $item->get($this->start_field);
    }
    catch (\Exception $e) {
      throw new MissingDataException('Undefined start date field for calculated duration.')
    }
    try {
      $end_timestamp = $item->get($this->end_field);
      $end_set = $item->get($this->endset_field);
    }
    catch (\Exception $e) {
      // We need one or the other of the end fields...
      throw new MissingDataException('Undefined end date field for calculated duration.')
    }

    $tz_utc = new \DateTimeZone('UTC');
    try {
      $start_date = new \DateTime($start_timestamp, $tz_utc);
      $end_date = new \DateTime($end_timestamp, $tz_utc);
    }
    catch (\Exception $e) {
      throw new MissingDataException('Bad data for start or end date field.');
    }
    if ($end_set && $end_date) {
      $duration = $end_date->diff($start_date);
    }
    else {
      $duration = new \DateInterval('PT0S');
    }
    return $duration->format();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->date = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
