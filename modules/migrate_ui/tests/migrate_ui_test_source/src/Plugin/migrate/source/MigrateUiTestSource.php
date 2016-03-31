<?php

/**
 * @file
 * Contains \Drupal\migrate_ui_test_source\Plugin\migrate\source\MigrateUiTestSource.
 */

namespace Drupal\migrate_ui_test_source\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Test UI source.
 *
 * @MigrateSource(
 *   id = "migrate_ui_test_source"
 * )
 */
class MigrateUiTestSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'id' => t('ID'),
      'field_name' => t('Field Name'),
      'type' => t('Type'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator(array(array('id' => '')));
  }

  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return 1;
  }

}
