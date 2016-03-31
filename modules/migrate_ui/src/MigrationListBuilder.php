<?php

/**
 * @file
 * Contains \Drupal\migrate_ui\MigrationListBuilder.
 */

namespace Drupal\migrate_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class MigrationListBuilder extends ConfigEntityListBuilder {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The request object for filters.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder, Request $request) {
    parent::__construct($entity_type, $storage);
    $this->formBuilder = $form_builder;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('form_builder'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['title'] = $this->t('Migration name');
    $row['source'] = $this->t('Source name');
    $row['destination'] = $this->t('Destination name');
    $row['tags'] = $this->t('Tags');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\migrate\Entity\Migration $entity */
    $row['title']['data'] = $entity->label();
    $row['source']['data'] = $entity->getSourcePlugin()->getPluginId();
    $row['destination']['data'] = $entity->getDestinationPlugin()->getPluginId();
    $row['tags']['data'] = implode(', ', $entity->get('migration_tags') ?: []);
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No migrations are available. <a href="@link">Add migration</a>.', [
      '@link' => Url::fromRoute('migrate_ui.create_migration')->toString()
    ]);

    $migration_tags = [];
    $migrations = $this->storage->loadMultipleOverrideFree($this->getFilteredEntityIds(TRUE));
    uasort($migrations, array($this->entityType->getClass(), 'sort'));

    foreach ($migrations as $migration) {
      if ($tags = $migration->get('migration_tags')) {
        $migration_tags = array_merge($tags, $migration_tags);
      }
    }

    // Add our filter form.
    $build['filter'] = $this->formBuilder->getForm('Drupal\migrate_ui\Form\FilterForm', ['tags' => $migration_tags]);
    $build['filter']['#weight'] = -1;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getFilteredEntityIds();
  }

  /**
   * Gets the entities for this list.
   *
   * @param bool $disable_filters
   *   TRUE if we want to disable the filters otherwise FALSE.
   *
   * @return array
   *   An array of entities.
   */
  protected function getFilteredEntityIds($disable_filters = FALSE) {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // If we have a filter, apply in to the query.
    if (($filter = $this->request->query->get('filter')) && !$disable_filters) {
      $query->condition('migration_tags.*', [$filter], 'IN');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
