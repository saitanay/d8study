<?php

/**
 * @file
 * Contains Drupal\migrate_ui\Tests\AdvancedProcessTest.
 */

namespace Drupal\migrate_ui\Tests;

use Drupal\migrate\Entity\Migration;

/**
 * @group migrate_ui
 */
class AdvancedProcessTest extends MigrateUiTestBase {

  /**
   * Enable the required modules.
   *
   * @TODO, remove dependency on migrate_drupal after
   * https://www.drupal.org/node/2560795
   *
   * @var array
   */
  public static $modules = [
    'migrate',
    'node', // We're using the node destination.
    'migrate_drupal',
    'migrate_ui',
    'migrate_ui_test_source',
  ];

  /**
   * The migration entity.
   *
   * @var \Drupal\migrate\Entity\Migration
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer migrations']);
    $this->drupalLogin($admin_user);

    // Create a migration with the basic properties.
    $this->migration = $this->getTestMigration();
  }

  /**
   * Test our advanced processing form.
   */
  public function testAdvancedProcessing() {
    // Visit the processing form for a field.
    $this->drupalGet(sprintf('/admin/config/migrate/migrations/process/%s/%s', $this->migration->id(), 'nid'));

    // Select 3 process plugins.
    $this->drupalPostAjaxForm(NULL, ['source_field[]' => 'id', 'plugin_selection' => 'concat'], ['op' => 'Configure']);
    $this->drupalPostAjaxForm(NULL, ['source_field[]' => 'id', 'plugin_selection' => 'callback'], ['op' => 'Configure']);
    $this->drupalPostAjaxForm(NULL, ['source_field[]' => 'id', 'plugin_selection' => 'extract'], ['op' => 'Configure']);

    // Assert the expected structure.
    $this->assertFieldByName('process_pipeline[0][pipeline_step][plugin]', 'concat');
    $this->assertFieldByName('process_pipeline[0][pipeline_step][plugin_config][delimiter]', '', 'Concat configuration form appears');
    $this->assertFieldByName('process_pipeline[1][pipeline_step][plugin]', 'callback');
    $this->assertFieldByName('process_pipeline[1][pipeline_step][plugin_config][callback]', '', 'Callback configuration form appears');
    $this->assertFieldByName('process_pipeline[2][pipeline_step][plugin]', 'extract');
    $this->assertFieldByName('process_pipeline[2][pipeline_step][plugin_config][default]', '', 'Extract configuration form appears');

    // Test that we can remove items. Remove 'callback', and then asset we only
    // have concat and extract.
    $this->drupalPostAjaxForm(NULL, ['source_field[]' => 'id'], ['remove_2' => 'Remove']);
    $this->assertFieldByName('process_pipeline[0][pipeline_step][plugin]', 'concat');
    $this->assertFieldByName('process_pipeline[0][pipeline_step][plugin_config][delimiter]', '', 'Concat configuration form appears');
    $this->assertFieldByName('process_pipeline[1][pipeline_step][plugin]', 'extract');
    $this->assertFieldByName('process_pipeline[1][pipeline_step][plugin_config][default]', '', 'Extract configuration form appears');

    // Save the form and asset the correct structure.
    $this->drupalPostForm(NULL, ['source_field[]' => 'id'], 'Save');
    $this->migration = Migration::load($this->migration->id());

    $expected = [
      'nid' => [
        ['plugin' => 'get', 'source' => 'id'],
        ['plugin' => 'concat', 'delimiter' => ''],
        ['plugin' => 'extract', 'default' => ''],
      ],
    ];
    $this->assertEqual($expected, $this->migration->getProcess());
  }

  /**
   * Test we can re-order the steps from the UI.
   */
  public function testReOrdering() {
    // Visit the processing form for a field.
    $this->drupalGet(sprintf('/admin/config/migrate/migrations/process/%s/%s', $this->migration->id(), 'nid'));

    // Select 2 process plugins and save the form.
    $this->drupalPostAjaxForm(NULL, ['source_field[]' => 'id', 'plugin_selection' => 'concat'], ['op' => 'Configure']);
    $this->drupalPostAjaxForm(NULL, ['source_field[]' => 'id', 'plugin_selection' => 'callback'], ['op' => 'Configure']);
    $this->drupalPostForm(NULL, ['source_field[]' => 'id'], 'Save');

    // Visit the process form and asset they're in the order we created them.
    $this->drupalGet(sprintf('/admin/config/migrate/migrations/process/%s/%s', $this->migration->id(), 'nid'));
    $this->assertFieldByName('process_pipeline[0][pipeline_step][plugin]', 'concat');
    $this->assertFieldByName('process_pipeline[1][pipeline_step][plugin]', 'callback');

    // Save the form with new weights.
    $this->drupalPostForm(NULL, [
      'source_field[]' => 'id',
      'process_pipeline[1][weight]' => 0,
    ], 'Save');

    // Assert they're now re-ordered.
    $this->drupalGet(sprintf('/admin/config/migrate/migrations/process/%s/%s', $this->migration->id(), 'nid'));
    $this->assertFieldByName('process_pipeline[1][pipeline_step][plugin]', 'concat');
    $this->assertFieldByName('process_pipeline[0][pipeline_step][plugin]', 'callback');
  }

}
