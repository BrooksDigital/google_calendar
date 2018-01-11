<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Google Calendar entity.
 *
 * @ingroup google_calendar
 *
 * @ContentEntityType(
 *   id = "google_calendar",
 *   label = @Translation("Google Calendar"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\google_calendar\GoogleCalendarListBuilder",
 *     "views_data" = "Drupal\google_calendar\Entity\GoogleCalendarViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "add" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "edit" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "delete" = "Drupal\google_calendar\Form\GoogleCalendarDeleteForm",
 *     },
 *     "access" = "Drupal\google_calendar\GoogleCalendarAccessControlHandler",
 *     "route_provider" = {
 *        "html" = "Drupal\google_calendar\GoogleCalendarHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "google_calendar",
 *   data_table = "google_calendar_field_data",
 *   admin_permission = "administer google calendar entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/calendar/{google_calendar}",
 *     "add-form" = "/admin/google-calendar/calendar/add",
 *     "edit-form" = "/admin/google-calendar/calendar/{google_calendar}/edit",
 *     "delete-form" = "/admin/google-calendar/calendar/{google_calendar}/delete",
 *     "collection" = "/admin/google-calendar/calendar",
 *   },
 *   field_ui_base_route = "google_calendar.settings"
 * )
 */
class GoogleCalendar extends ContentEntityBase implements GoogleCalendarInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  public function getGoogleCalendarId(){
    return $this->get('calendar_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Google Calendar entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['calendar_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Google Calendar ID'))
      ->setDescription(t('The ID of the calendar in google.  This can be obtained form the "Integrate Calendar" section of your calendar\'s settings.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Google Calendar is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
