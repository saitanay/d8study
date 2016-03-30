<?php

/**
 * @file
 * Contains \Drupal\migrate_plus\Event\MigratePrepareRowEvent.
 */

namespace Drupal\migrate_plus\Event;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a prepare-row event for event listeners.
 */
class MigratePrepareRowEvent extends Event {

  /**
   * Row object.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $source;

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a prepare-row event object.
   *
   * @param \Drupal\migrate\Row $row
   *   Row of source data to be analyzed/manipulated.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   Source plugin that is the source of the event.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   Migration entity.
   */
  public function __construct(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
    $this->row = $row;
    $this->source = $source;
    $this->migration = $migration;
  }

  /**
   * Gets the row object.
   *
   * @return \Drupal\migrate\Row
   *   The row object about to be imported.
   */
  public function getRow() {
    return $this->row;
  }

  /**
   * Gets the source plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The source plugin firing the event.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Gets the migration entity.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface
   *   The migration entity being imported.
   */
  public function getMigration() {
    return $this->migration;
  }

}
