<?php

/**
 * @file
 * Contains \Drupal\migrate_api\SchemaFormBuilder.
 */

namespace Drupal\migrate_api;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds forms from schema.
 */
class SchemaFormBuilder implements SchemaFormBuilderInterface {

  use StringTranslationTrait;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $configManager;

  /**
   * Methods responsible for handling schema when keys are present in a subtree.
   *
   * @var array
   */
  protected $schemaKeyHandlers = [
    'type' => 'typeHandler',
    'mapping' => 'mappingHandler',
    'sequence' => 'sequenceHandler',
  ];

  /**
   * An array of schema types mapped to form elements.
   *
   * @var array
   */
  protected $primativeTypeMap = [
    // Basic primatives.
    'string' => 'textfield',
    'boolean' => 'checkbox',
    'integer' => 'number',
    'email' => 'textfield',
    'float' => 'textfield',
    'uri' => 'textfield',
    'label' => 'textfield',
    'text' => 'textfield',
    'path' => 'textfield',
    'date_format' => 'textfield',
    'color_hex' => 'textfield',
    // Container types.
    'mapping' => 'fieldset',
    'sequence' => 'fieldset',
  ];

  /**
   * A map to track AJAX IDs.
   *
   * @var array
   */
  protected $ajaxIdMap = [];

  /**
   * Create an instance of SchemaFormBuilder.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $config_manager
   *   The typed data config manager.
   */
  public function __construct(TypedConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormArray($schema_plugin_id, FormStateInterface $form_state) {
    $this->ajaxIdMap = [];
    $plugin = $this->configManager->get($schema_plugin_id);
    $form = [];
    $this->processSchema($plugin->getDataDefinition(), SchemaFormBuilderInterface::ROOT_CONTEXT_KEY, $form, $form_state);
    return $form[SchemaFormBuilderInterface::ROOT_CONTEXT_KEY];
  }

  /**
   * Process a tree or subtree of a schema plugin definition.
   *
   * @param array|object $schema
   *   A schema plugin array definition or ArrayAccess implementing object.
   * @param string $context
   *   The key for the form subtree currently being built.
   * @param array $form
   *   The form or form subtree to attach elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form the subtree is attached to, for AJAX reasons.
   */
  protected function processSchema($schema, $context, &$form, FormStateInterface $form_state) {
    foreach ($this->schemaKeyHandlers as $schema_key => $schema_handler) {
      if (isset($schema[$schema_key])) {
        static::$schema_handler($schema, $context, $form, $form_state);
      }
    }
  }

  /**
   * Handle building a form when something has a "type".
   *
   * @param array $schema
   *   A schema plugin definition or subtree.
   * @param string $context
   *   The key for the form subtree currently being built.
   * @param array $form
   *   The form or form subtree to attach elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form the subtree is attached to, for AJAX reasons.
   */
  protected function typeHandler($schema, $context, &$form, FormStateInterface $form_state) {
    if ($context === SchemaFormBuilderInterface::ROOT_CONTEXT_KEY) {
      return;
    }
    // If we don't have a way to handle a type, perhaps it can be resolved to
    // something more primative. @todo investigate moving resolving a type to
    // the most primatve types.
    if (empty($this->primativeTypeMap[$schema['type']]) && strpos($schema['type'], '%') === FALSE) {
      // @todo, find out why TypedConfigManager squashes ancesty information.
      $type_definition = $this->configManager->getDefinitions()[$schema['type']];
      // Preserve labels when resolving parents.
      if (!isset($type_definition['label']) && isset($schema['label'])) {
        $type_definition['label'] = $schema['label'];
      }
      $this->processSchema($type_definition, $context, $form, $form_state);
    }
    else {
      $form[$context] = [
        '#title' => isset($schema['label']) ? $schema['label'] : '',
        '#type' => !empty($this->primativeTypeMap[$schema['type']]) ? $this->primativeTypeMap[$schema['type']] : 'textfield',
      ];
    }
  }

  /**
   * Handle building a form when a mapping is encountered.
   *
   * @param array $schema
   *   A schema plugin definition or subtree.
   * @param string $context
   *   The key for the form subtree currently being built.
   * @param array $form
   *   The form or form subtree to attach elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form the subtree is attached to, for AJAX reasons.
   */
  protected function mappingHandler($schema, $context, &$form, FormStateInterface $form_state) {
    // Resolve all the keys in a map.
    foreach ($schema['mapping'] as $mapping_key => $mapping) {
      $this->processSchema($mapping, $mapping_key, $form[$context], $form_state);
    }
  }

  /**
   * Handle building a form when a sequence is encountered.
   *
   * @param array $schema
   *   A schema plugin definition or subtree.
   * @param string $context
   *   The form subtree currently being built.
   * @param array $form
   *   The form or form subtree to attach elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form the subtree is attached to, for AJAX reasons.
   */
  protected function sequenceHandler($schema, $context, &$form, $form_state) {
    $unique_ajax_key = $this->uniqueAjaxId($schema, $context);
    foreach (range(0, $form_state->get(['schema_form_deltas', $unique_ajax_key]) ?: 0) as $delta) {
      $this->processSchema($schema['sequence'], $delta, $form[$context], $form_state);
    }
    $form[$context]['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another'),
      '#name' => $unique_ajax_key,
      '#submit' => [[static::class, 'sequenceHandlerSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'sequenceHandlerAjax'],
        'wrapper' => $unique_ajax_key,
      ]
    ];
    $form[$context]['#prefix'] = "<div id=\"$unique_ajax_key\">";
    $form[$context]['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Handle form submission aspect of the sequence "Add another" form.
   */
  public static function sequenceHandlerSubmit(array &$form, FormStateInterface $form_state) {
    $add_button = $form_state->getTriggeringElement();
    $deltas_key = ['schema_form_deltas', $add_button['#name']];
    $deltas = $form_state->get($deltas_key) ?: 0;
    $form_state->set($deltas_key, ++$deltas);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Handle the AJAX response aspect of the "Add another" sequence form.
   */
  public static function sequenceHandlerAjax(array $form, FormStateInterface $form_state) {
    $add_button = $form_state->getTriggeringElement();
    $parents = $add_button['#array_parents'];
    array_pop($parents);
    $form_fragment = NestedArray::getValue($form, $parents);
    return $form_fragment;
  }

  /**
   * Get a unique AJAX ID for use in the form AJAX handlers.
   *
   * A unique ID must be provided for a group of elements for AJAX to work. The
   * unique ID has to be set on the element and remain exactly the same for the
   * specific element every time the form is rebuilt, even after additional form
   * elements have been created using the 'Add another' button.
   *
   * Since form elements are being generated from a schema definition, there
   * could be a number of similar or identical subtrees of schema defintion.
   * Because processing is recursive, the only kind of context that is present
   * is the subtree that is being built into a form. This makes the task of
   * creating a unique ID for that specific element difficult.
   *
   * This solution prevents conflicts, but is also not a complete solution. In
   * the case of nested sequences, where new form elements are being AJAXed
   * whose schema match the schema of a completely unrelated subtree, IDs will
   * conflict. A good example is 'third_party_settings', which is scattered
   * throughout schema definitions, making it very difficult to create a stable
   * and unique ID. Despite issues, this implementation works for all of some
   * complicated definitions such as 'core.entity_view_display.*.*.*'.
   *
   * @param array $schema
   *   The schema which is being processed.
   * @param string $context
   *   The form subtree currently being built.
   *
   * @return string
   *   A unique key to use for AJAX callbacks.
   */
  protected function uniqueAjaxId($schema, $context) {
    // @todo, web test this and investigate a better solution.
    $subtree_key = md5(serialize($schema)) . $context;
    if (!isset($this->ajaxIdMap[$subtree_key])) {
      $this->ajaxIdMap[$subtree_key] = 0;
    }
    else {
      $this->ajaxIdMap[$subtree_key]++;
    }
    return $subtree_key . $this->ajaxIdMap[$subtree_key];
  }

}
