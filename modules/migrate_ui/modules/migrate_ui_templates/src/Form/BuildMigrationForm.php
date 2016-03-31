<?php

/**
 * @file
 * Contains \Drupal\migrate_ui_templates\Form\BuildMigrationForm.
 */

namespace Drupal\migrate_ui_templates\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\MigrateTemplateStorage;
use Drupal\migrate\MigrationBuilder;
use Drupal\migrate\MigrationStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The BuildMigrationForm class.
 */
class BuildMigrationForm extends ConfirmFormBase {

  /**
   * The migration template storage.
   *
   * @var \Drupal\migrate\MigrateTemplateStorage
   */
  protected $templateStorage;

  /**
   * The migration builder plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrateBuilderInterface
   */
  protected $migrateBuilder;

  /**
   * The migration entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $migrateStorage;

  /**
   * MigrateUiTemplateController constructor.
   *
   * @param \Drupal\migrate\MigrateTemplateStorage $migrate_template_storage
   *   The migration template storage.
   */
  public function __construct(EntityStorageInterface $migration_storage, MigrateTemplateStorage $migrate_template_storage, MigrationBuilder $migrate_builder) {
    $this->migrateStorage = $migration_storage;
    $this->templateStorage = $migrate_template_storage;
    $this->migrateBuilder = $migrate_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('migration'),
      $container->get('migrate.template_storage'),
      $container->get('migrate.migration_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you wanted to build this template into a migration?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('migrate_ui_templates.list_templates');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_ui_templates.build_migration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $template_name = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Deny the form entirely if a migration with this name already exists.
    if ($migration = $this->migrateStorage->load($template_name)) {
      unset($form['actions']);

      drupal_set_message($this->t('You cannot rebuild this template, migration already <a href=":exists">exists</a>', [
        ':exists' => $migration->toUrl()->toString(),
      ]), 'error');
    }

    // @todo, handle migrations that need a source configured?

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $template_name = NULL) {
    $template_name = $form_state->getBuildInfo()['args'][0];
    $template = $this->templateStorage->getTemplateByName($template_name);
    $migrations = $this->migrateBuilder->createMigrations([$template]);

    foreach ($migrations as $migration) {
      try {
        $migration->save();
      }
      catch (PluginNotFoundException $e) {
        // @todo, handle this better
        drupal_set_message($e->getMessage(), 'error');
      }
    }

    drupal_set_message($this->t('The @template template has been built into a migration', [
      '@template' => $template_name,
    ]));

    $form_state->setRedirect('migrate_ui_templates.list_templates');
  }

}
