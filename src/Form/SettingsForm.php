<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Class TestSettingsForm.
 *
 * @ingroup test
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.

    $test = "test";

    $secret = $form_state->getValue('client_secret');
    $file = File::load( $secret[0] );
    $file->setPermanent();
    $file->save();

    //Save the uri to settings
    $this->config('google_calendar.default')
      ->set('secret_file_uri', $file->getFileUri())
      ->save();

    drupal_set_message($this->t("Client Secret file uploaded successfully"));
  }

  /**
   * Defines the settings form for Test entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['client_secret'] = array(
      '#type' => 'managed_file',
      '#title' => t('Upload Client Secret File'),
      '#upload_location' => 'private://google-calendar/',
      '#default_value' => "",
      '#description' => t('Client Secret JSON file.'),
      '#upload_validators' => array(
        'file_validate_extensions' => array('json')
      ),
    );


    return parent::buildForm($form, $form_state);;
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['google_calendar.default'];
  }
}
