<?php

/**
 * @file
 * Contains \Drupal\migrate_ui\Controller\MigrationController.
 */

namespace Drupal\migrate_ui\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;

class MigrationController extends ControllerBase {

  /**
   * Gets the process configuration title.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   * @param string $destination_field
   *   The field name we're configuring.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The markup object.
   */
  public function processConfigurationTitle($migration, $destination_field) {
    return new FormattableMarkup('Configure %migration migration process for %field', ['%migration' => $migration->label(), '%field' => $destination_field]);
  }

}
