<?php

/**
 * @file
 * Contains \Drupal\migrate_ui\Form\MigrationEntityFormBase.
 */

namespace Drupal\migrate_ui\Form;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate_api\SchemaFormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrationEntityFormBase extends EntityForm {

  /**
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $sourcePluginManager;

  /**
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The schema form builder.
   *
   * @var \Drupal\migrate_api\SchemaFormBuilderInterface
   */
  protected $schemaFormBuilder;

  /**
   * Constructs a new form to create and configure a migration.
   *
   * @param \Drupal\migrate\Plugin\MigratePluginManager $source_plugin_manager
   *  The migrate plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $process_plugin_manager
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, MigratePluginManager $source_plugin_manager, MigratePluginManager $process_plugin_manager, SchemaFormBuilderInterface $schema_form_builder) {
    $this->sourcePluginManager = $source_plugin_manager;
    $this->processPluginManager = $process_plugin_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->schemaFormBuilder = $schema_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.migrate.source'),
      $container->get('plugin.manager.migrate.process'),
      $container->get('migrate_api.schema_form_builder')
    );
  }

  /**
   * Get a constant value from the migration given the key name.
   *
   * @param string $key
   *   The key for the constant in the source config.
   *
   * @return string|NULL
   */
  protected function getConstant($key) {
    $source = $this->entity->get('source');
    return isset($source['constants'][$key]) ? $source['constants'][$key] : NULL;
  }

  /**
   * Gets the source key given the destination key.
   *
   * @param string $destination_key
   *   The destination key to retrieve the source key.
   *
   * @return string|array
   */
  protected function getProcessGivenKey($destination_key) {
    $process = $this->entity->getProcess();
    return isset($process[$destination_key]) ? $process[$destination_key] : [];
  }

  /**
   * Gets the source fields for the currently selected source.
   *
   * @return array
   *   An array of fields keyed by machine id and values are labels.
   */
  protected function getSourceFields() {
    $fields = array_keys($this->entity->getSourcePlugin()->fields());
    return array_combine($fields, $fields);
  }

  /**
   * Gets an array of source plugins.
   *
   * @return array
   *   An array of available source plugins key and value is the plugin id.
   */
  protected function getSources() {
    $sources = array_keys($this->sourcePluginManager->getDefinitions());
    asort($sources);
    return array_combine($sources, $sources);
  }

  /**
   * Gets the destination entity type.
   *
   * @return string|NULL
   *   The entity type of NULL.
   */
  protected function getEntityType() {
    return isset($this->entity->get('destination')['plugin']) ? explode(':', $this->entity->getDestinationPlugin()->getPluginId())[1] : NULL;
  }

  /**
   * Gets the source plugin id.
   *
   * @return string|NULL
   *   The source plugin id.
   */
  protected function getSourcePluginId() {
    return isset($this->entity->get('source')['plugin']) ? $this->entity->get('source')['plugin'] : NULL;
  }

  /**
   * Gets the destination fields.
   *
   * @return array
   *   An array of destination fields we can import into.
   */
  protected function getDestinationFields() {
    // If the migration doesn't have an entity type or a bundle then we can't
    // really help.
    if (!$entity_type_id = $this->getEntityType()) {
      return [];
    }

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($entity_type instanceof ConfigEntityTypeInterface) {
      return $entity_type->getPropertiesToExport();
    }
    else {
      $field_map = $this->entityFieldManager->getFieldMap();
      $bundle = $this->getBundle();

      // Filter out the fields for our specific bundle.
      $fields = array_filter($field_map[$entity_type_id], function($field_info) use($bundle) {
        return in_array($bundle, $field_info['bundles']);
      });

      $fields = array_keys($fields);
      return array_combine($fields, $fields);
    }
  }

  /**
   * Gets the source value for the given destination field.
   *
   * We try to always store the source values as a pipeline and with the
   * normalised format eg.
   *
   * destination_field_name:
   *   -
   *     plugin: get
   *     source: source_field_name
   *
   * Given this format, we always have the same structure regardless of the
   * processing pipeline.
   *
   * Note, if we don't get an array, we simply return $process for the case
   * where the migration is using the short syntax, eg.
   *
   * destination_field: source_field
   *
   * @param string $destination_field
   *   The destination field we're importing into.
   *
   * @return NULL|string
   */
  protected function getSourceValue($destination_field) {
    $process = $this->getProcessGivenKey($destination_field);
    if (is_array($process)) {
      return !empty($process[0]['source']) ? $process[0]['source'] : NULL;
    }
    return $process;
  }

  /**
   * Gets the bundles for the given entity type.
   *
   * @return array
   *   An array with bundles as keys and the bundle label as values.
   */
  protected function getEntityBundles() {
    $bundles = [];
    foreach ($this->entityManager->getBundleInfo($this->getEntityType()) as $bundle => $info) {
      $bundles[$bundle] = $info['label'];
    }
    return $bundles;
  }

  /**
   * Gets the bundle for the current migration.
   *
   * @return null|string
   *   The bundle or NULL if there isn't one selected.
   */
  protected function getBundle() {
    $bundles = $this->getEntityBundles();
    return count($bundles) === 1 ? key($bundles) : $this->getConstant('bundle');
  }

}
