<?php

/**
 * @file
 * Contains \Drupal\migrate_ui\Tests\MigrateUiTestBase.
 */

namespace Drupal\migrate_ui\Tests;

use Drupal\migrate\Entity\Migration;
use Drupal\simpletest\WebTestBase;

/**
 * The MigrateUiTestBase class.
 */
abstract class MigrateUiTestBase extends WebTestBase {

  /**
   * Gets a migration entity for testing.
   *
   * @param string $source
   *   (optional) The source plugin name.
   * @param string $destination
   *   (optional) The destination plugin name.
   *
   * @return \Drupal\migrate\Entity\Migration
   *   A migration entity.
   */
  protected function getTestMigration($source = '', $destination = '') {
    $migration_name = strtolower($this->randomMachineName());
    $migration = Migration::create([
      'id' => $migration_name,
      'label' => $migration_name,
      'migration_tags' => [],
      'source' => [
        'plugin' => $source ?: 'migrate_ui_test_source',
        'constants' => [
          'bundle' => 'article',
        ],
      ],
      'process' => [],
      'destination' => [
        'plugin' => $destination ?: 'entity:node',
      ],
    ]);
    $migration->save();
    return $migration;
  }

  /**
   * Asserts a string does exist in the haystack.
   *
   * @param string $needle
   *   The string to search for.
   * @param string $haystack
   *   The string to search within.
   * @param string $message
   *   The message to log.
   *
   * @return bool
   *   TRUE if it was found otherwise FALSE.
   */
  protected function assertContains($needle, $haystack, $message = '') {
    if (empty($message)) {
      $message = t('%needle was found within %haystack', array('%needle' => $needle, '%haystack' => $haystack));
    }
    return $this->assertTrue(stripos($haystack, $needle) !== FALSE, $message);
  }

  /**
   * Asserts a string does not exist in the haystack.
   *
   * @param string $needle
   *   The string to search for.
   * @param string $haystack
   *   The string to search within.
   * @param string $message
   *   The message to log.
   *
   * @return bool
   *   TRUE if it was not found otherwise FALSE.
   */
  protected function assertNotContains($needle, $haystack, $message = '') {
    if (empty($message)) {
      $message = t('%needle was not found within %haystack', array('%needle' => $needle, '%haystack' => $haystack));
    }
    return $this->assertTrue(stripos($haystack, $needle) === FALSE, $message);
  }

}
