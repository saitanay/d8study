<?php

/**
 * @file
 * Contains \Drupal\migrate_api\SchemaFormBuilderInterface.
 */

namespace Drupal\migrate_api;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds forms from schema plugin ids.
 */
interface SchemaFormBuilderInterface {

  /**
   * They key for the root of the form.
   */
  const ROOT_CONTEXT_KEY = 'root';

  /**
   * Get a form from a schema plugin id.
   *
   * @param string $schema_plugin_id
   *   The schema plugin id.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state the form will become a part of.
   *
   * @return array
   *   Form elements.
   */
  public function getFormArray($schema_plugin_id, FormStateInterface $form_state);

}
