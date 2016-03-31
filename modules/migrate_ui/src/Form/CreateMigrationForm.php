<?php

namespace Drupal\migrate_ui\Form;

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\Core\Url;

class CreateMigrationForm extends MigrationEntityFormBase {

  /**
   * Builds the entity from the form_state values.
   *
   * @param string $entity_type_id
   *   The entity type id, should always be "migration" here.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function buildEntityFromForm($entity_type_id, MigrationInterface $migration, &$form, FormStateInterface $form_state) {
    $migration->set('source', ['plugin' => $form_state->getValue('source')]);
    $migration->set('label', $form_state->getValue('migration_name'));
    $this->processMigrationValues($migration, $form_state->getValues());

    // Trim the tags and set them onto the migration entity.
    $tags = $form_state->getValue('migration_tags');
    $tags = $tags ? array_map('trim', explode(',', $tags)) : [];
    $migration->set('migration_tags', $tags);

    // If we have the entity type then setup the destination.
    $entity_type = $form_state->getValue('destination_entity_type');
    if ($entity_type) {
      $migration->set('destination', ['plugin' => "entity:$entity_type"]);

      // If we have a bundle then we store that value as a constant to be mapped
      // to the appropriate bundle key.
      if ($bundle = $form_state->getValue('destination_bundle')) {
        $source = $migration->get('source');
        $source['constants']['bundle'] = $bundle;
        $migration->set('source', $source);
        $migration->setProcessOfProperty($this->entityTypeManager->getDefinition($entity_type)->getKey('bundle'), 'constants/bundle');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, MigrationInterface $migration = NULL) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    $form['#id'] = 'migration-form';
    $form['#entity_builders'] = [[$this, 'buildEntityFromForm']];

    $form['migration_details'] = [
      '#type' => 'fieldset',
      '#tree' => FALSE,
      '#title' => $this->t('Migration details'),
    ];
    $form['migration_details']['migration_name'] = [
      '#type' => 'textfield', 
      '#title' => $this->t('Migration name'),
      '#required' => TRUE,
      '#default_value' => $this->entity->label(),
    ];
    $form['migration_details']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'exists' => ['Drupal\migrate\Entity\Migration', 'load'],
        'source' => ['migration_details', 'migration_name'],
      ],
    ];
    $form['migration_details']['migration_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Migration Tags'),
      '#default_value' => implode(', ', $this->entity->get('migration_tags') ?: []),
      '#description' => $this->t('A comma separated list of tags'),
    ];

    $form['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
      '#tree' => FALSE,
      '#attributes' => ['class' => ['form--inline']],
    ];
    $form['configuration']['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Source'),
      '#options' => $this->getSources(),
      '#required' => TRUE,
      '#default_value' => $this->getSourcePluginId(),
      '#ajax' => array(
        'callback' => [static::class, 'ajaxUpdateMigrateConfig'],
        'wrapper' => 'migrate-config-table',
        'progress' => ['type' => 'throbber'],
      ),
    ];

    $entity_type_labels = $this->entityManager->getEntityTypeLabels();
    asort($entity_type_labels);
    $form['configuration']['destination_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination entity type'),
      '#options' => $entity_type_labels,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select - '),
      '#default_value' => $this->getEntityType(),
      '#submit' => [static::class, 'ajaxUpdateEntityTypeSubmit'],
      '#ajax' => array(
        'callback' => [static::class, 'ajaxUpdateEntityTypeCallback'],
        'wrapper' => 'migration-form',
        'progress' => ['type' => 'throbber'],
      ),
    ];
    $bundles = $this->getEntityBundles();
    $bundles = array_map('trim', $bundles);
    $selected_bundle = $this->getBundle();
    $form['configuration']['destination_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination entity bundle'),
      '#options' => $bundles,
      '#required' => TRUE,
      // If it isn't in the array of bundles the user just changed the entity
      // type and we need them to reselect.
      '#default_value' => array_key_exists($selected_bundle, $bundles) ? $selected_bundle : NULL,
      '#prefix' => '<div id="migrate-destination-bundle">',
      '#suffix' => '</div>',
      '#ajax' => array(
        'callback' => '::ajaxUpdateMigrateConfig',
        'wrapper' => 'migrate-config-table',
        'progress' => ['type' => 'throbber'],
      ),
    ];

    // Build the table that allows configuration of destination and source
    // fields.
    $form['migrate_config'] = $this->getMigrationConfig($this->getEntityType(), $selected_bundle, $this->getSourcePluginId(), $form_state);

    return $form;
  }

  /**
   * We transform the migrate_config part of the form into a table.
   *
   * @TODO, this belongs in something #theme related.
   *
   * @param array $form
   *   The constructed form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array.
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    $header = [
      $this->t('Destination Field'),
      $this->t('Source Field'),
      $this->t('Processing'),
    ];
    $rows = [];

    // Loop over migrate_config and convert it into a table.
    $is_new = $this->entity->isNew();
    foreach (Element::children($form['migrate_config']) as $destination_field) {
      if ($is_new) {
        $link = ['#markup' => $this->t('Please save the migration first.')];
      }
      else {
        $link = new Link('Edit', new Url('migrate_ui.migration_process', [
          'migration' => $this->entity->id(),
          'destination_field' => $destination_field,
        ], [
          'attributes' => [
            'class' => 'use-ajax',
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => '700']),
          ],
        ]));
      }

      $rows[] = [
        ['data' => $destination_field],
        ['data' => $form['migrate_config'][$destination_field], 'class' => ['form--inline']],
        ['data' => $link],
      ];
    }

    // If we have table rows then override migrate_config to be a theme_table()
    // representation of the form.
    if (!empty($rows)) {
      $form['migrate_config'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#prefix' => '<div id="migrate-config-table">',
        '#suffix' => '</div>',
        '#weight' => 10,
      ];
    }

    return parent::afterBuild($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('destination_bundle') === '_none') {
      $form_state->setErrorByName('destination_bundle', $this->t('Bundle cannot be empty'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.migration.edit_form', ['migration' => $this->entity->id()]);
    drupal_set_message($this->t('Migration @migration has been created', ['@migration' => $this->entity->label()]));
  }

  /**
   * Ajax submit handler for when we update the destination entity type.
   */
  public static function ajaxUpdateEntityTypeSubmit(&$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback to update the entire form.
   */
  public static function ajaxUpdateEntityTypeCallback($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * AJAX callback to update the migration configuration table.
   */
  public static function ajaxUpdateMigrateConfig($form, FormStateInterface $form_state) {
    return $form['migrate_config'];
  }

  /**
   * Copy the values from the migrate config table into the migration.
   *
   * @param array $form_state_values
   *   An array of values directly from the form state.
   */
  protected function processMigrationValues(MigrationInterface $migration, $form_state_values) {
    $source = $migration->get('source');
    if (isset($form_state_values['migrate_config'])) {
      foreach ($form_state_values['migrate_config'] as $destination_field => $source_data) {
        if ($this->isComplexProcess($destination_field)) {
          continue;
        }

        if ($source_data['source_field'] !== '_none') {
          $migration->setProcessOfProperty($destination_field, ['plugin' => 'get', 'source' => $source_data['source_field']]);
        }

        // If we have a constant then set it. This will override the source
        // field that was selected.
        if (!empty($source_data['constant'])) {
          $source['constants'][$destination_field] = $source_data['constant'];
          $migration->setProcessOfProperty($destination_field, ['plugin' => 'get', 'source' => 'constants/' . $destination_field]);
        }
      }
    }
    $migration->set('source', $source);
  }

  /**
   * Gets a table that represents the migration process configuration.
   *
   * @param string $entity_type
   *   The entity type the Migration will import into.
   * @param string $bundle
   *   The selected bundle for the given entity type.
   * @param string $source_id
   *   The plugin id for the migration source.
   *
   * @return array
   *   An array representing a theme_table() structure to create the migration.
   */
  protected function getMigrationConfig($entity_type, $bundle, $source_id, FormStateInterface $form_state) {
    $destination_fields = $this->getDestinationFields();
    if (empty($destination_fields)) {
      return [
        '#markup' => empty($bundle) ? $this->t('Nothing to configure.') : $this->t('The @bundle bundle does not have any fields.', ['@bundle' => $bundle]),
        '#prefix' => '<div id="migrate-config-table">',
        '#suffix' => '</div>',
      ];
    }

    $migrate_config = [];
    foreach ($destination_fields as $field) {
      $data = [];
      $default_value = $this->getSourceValue($field);

      // If we have a complex value for the source data then we don't allow
      // changes here and simply render an overview with a message.
      if ($this->isComplexProcess($field)) {
        // Source values support both arrays and single values.
        $default_value = is_array($default_value) ? implode(', ', $default_value) : $default_value;

        $data['source_data']['source_field'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Source field'),
          '#default_value' => $default_value,
          '#disabled' => TRUE,
          '#description' => $this->t('Single edit mode not available when using advanced process configuration.'),
        ];
      }
      else {
        $data['source_data']['source_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Source field'),
          '#default_value' => $default_value,
          '#options' => ['_none' => 'n/a'] + (empty($source_id) ? [] : $this->getSourceFields()),
        ];
        // Add a textbox to allow constant values to be added.
        $data['source_data']['constant'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Constant value'),
          '#default_value' => $this->getConstant($field),
          '#size' => 30,
          '#prefix' => '<div class="form-item">Or</div>',
        ];
      }

      $migrate_config[$field] = $data['source_data'];
    }

    return $migrate_config;
  }

  protected function isComplexProcess($destination_field) {
    $process = $this->getProcessGivenKey($destination_field);
    return (isset($process[0]['source']) && is_array($process[0]['source'])) || count($process) > 1;
  }

}
