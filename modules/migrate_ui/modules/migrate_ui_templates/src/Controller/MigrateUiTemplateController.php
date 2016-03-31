<?php

/**
 * @file
 * Contains \Drupal\migrate_ui_templates\Controller\MigrateUiTemplateController.
 */

namespace Drupal\migrate_ui_templates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\migrate\MigrateTemplateStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The MigrateUiTemplateController class.
 */
class MigrateUiTemplateController extends ControllerBase {

  /**
   * The migration template storage.
   *
   * @var \Drupal\migrate\MigrateTemplateStorage
   */
  protected $templateStorage;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * MigrateUiTemplateController constructor.
   *
   * @param \Drupal\migrate\MigrateTemplateStorage $migrate_template_storage
   *   The migration template storage.
   */
  public function __construct(FormBuilderInterface $form_builder, MigrateTemplateStorage $migrate_template_storage) {
    $this->templateStorage = $migrate_template_storage;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('migrate.template_storage')
    );
  }

  /**
   * List the migration templates.
   */
  public function listMigrationTemplates() {
    $migration_templates = $this->templateStorage->getAllTemplates();

    $rows = [];
    foreach ($migration_templates as $template) {
      // Calculate the available actions.
      if (empty($template['builder'])) {
        $action = new Link($this->t('Build migration'), new Url('migrate_ui.build_template', ['template_name' => $template['id']]));
      }
      else {
        $action = $this->t('Not supported, <a href=":why" target="_blank">why?</a>', [':why' => 'https://www.drupal.org/node/2629340']);
      }

      $rows[] = [
        $template['label'],
        $template['source']['plugin'],
        $template['destination']['plugin'],
        implode(', ', $template['migration_tags']),
        $action,
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Source'),
        $this->t('Destination'),
        $this->t('Migration Tags'),
        $this->t('Actions'),
      ],
      '#rows' => $rows,
    ];
  }

}
