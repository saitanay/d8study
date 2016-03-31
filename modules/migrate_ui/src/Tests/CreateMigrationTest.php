<?php

/**
 * @file
 * Contains Drupal\migrate_ui\Tests\CreateMigrationTest.
 */

namespace Drupal\migrate_ui\Tests;

use Drupal\migrate\Entity\Migration;

/**
 * Tests the Create Migration form.
 *
 * @group migrate_ui
 */
class CreateMigrationTest extends MigrateUiTestBase {

  /**
   * Enable the required modules.
   *
   * @var array
   */
  public static $modules = [
    'migrate_ui',
    'node',
    'migrate_ui_test_source',
  ];

  /**
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->contentType = $this->drupalCreateContentType(['type' => 'article']);
    $admin_user = $this->drupalCreateUser(['administer migrations']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Check that we can create a new migration and then update it.
   */
  function testCreateMigration() {
    // Create the initial migration entity.
    $migration_name = strtolower($this->randomMachineName());
    $edit = [
      'source' => 'migrate_ui_test_source',
      'id' => $migration_name,
      'migration_name' => $migration_name,
      'destination_entity_type' => 'node',
    ];
    $this->drupalPostAjaxForm('/admin/config/migrate/migrations/create', $edit, 'destination_entity_type');
    $edit += [
      'destination_bundle' => $this->contentType->id(),
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'destination_bundle');

    // Before we've save the migration we cannot edit the process.
    $this->assertContains('Please save the migration first.', $this->getTableText(1, 2));

    // Save the migration.
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText("Migration $migration_name has been created");

    // Visit the migration list and make sure it exists.
    $this->drupalGet('/admin/config/migrate/migrations');
    $this->assertText($migration_name);
    $this->clickLink('Edit');

    // Test that we can update the migration.
    $this->assertContains('Edit', $this->getTableText(1, 2));
    $this->clickLink('Edit');

    // Select multiple fields.
    $this->drupalPostForm($this->getUrl(), [
      'source_field[]' => ['field_name', 'type'],
    ], 'Save');
    $this->assertText("Migration $migration_name process has been updated.");

    // Make sure the middle column now has the warning text saying to use the
    // advanced edit.
    $this->assertContains('Single edit mode not available when using advanced process configuration.', $this->getTableText(1, 1));

    // Edit the field again back to one source field, make sure the error
    // warning is gone and that the select box has the single value.
    $this->clickLink('Edit');
    $this->drupalPostForm($this->getUrl(), [
      'source_field[]' => ['field_name'],
    ], 'Save');
    $this->assertNotContains('Single edit mode not available when using advanced process configuration.', $this->getTableText(1, 1));
    $this->assertFieldByName('migrate_config[nid][source_field]', 'field_name');
  }

  /**
   * Test a migration that goes into a config entity.
   */
  public function testConfigMigration() {
    $migration = $this->getTestMigration('', 'entity:action');
    $this->drupalGet($migration->toUrl()->toString());
    $this->assertFieldByName('destination_bundle', 'action');

    // Assert some known fields form config entity types.
    $this->assertText('third_party_settings');
    $this->assertText('dependencies');

    // Change the entity type, and then ensure that the bundle updates and the
    // mapping table.
    $this->drupalPostAjaxForm(NULL, ['destination_entity_type' => 'menu',], 'destination_entity_type');
    $this->assertFieldByName('destination_bundle', 'menu');

    // Assert fields specific to the menu config type.
    $this->assertText('locked');
  }

  /**
   * Ensure constants can be configured via the UI.
   */
  public function testConstants() {
    $migration = $this->getTestMigration();
    $constant_value = $this->randomString();
    $this->drupalPostForm('/admin/config/migrate/migrations/edit/' . $migration->id(), [
      'migrate_config[nid][constant]' => $constant_value,
    ], 'Save');

    /** @var \Drupal\migrate\Entity\Migration $migration */
    $migration = Migration::load($migration->id());

    // Assert the constant is in the source.
    $expected = [
      'plugin' => 'migrate_ui_test_source',
      'constants' => [
        'bundle' => 'article',
        'nid' => $constant_value,
      ],
    ];
    $this->assertEqual($expected, $migration->get('source'));

    // Assert the constant is used in the process array.
    $expected = [
      'nid' => [[
        'plugin' => 'get',
        'source' => 'constants/nid',
      ]],
      'type' => [[
        'plugin' => 'get',
        'source' => 'constants/bundle',
      ]],
    ];
    $this->assertEqual($expected, $migration->getProcess());
  }

  /**
   * Test we can add tags to a migration.
   */
  public function testMigrationTags() {
    $migration = $this->getTestMigration();
    $this->drupalPostForm('/admin/config/migrate/migrations/edit/' . $migration->id(), [
      'migration_tags' => 'Tag 1, Tag2, Tag 3 ',
    ], 'Save');
    /** @var \Drupal\migrate\Entity\Migration $migration */
    $migration = Migration::load($migration->id());

    $expected = ['Tag 1', 'Tag2', 'Tag 3'];
    $this->assertEqual($expected, $migration->get('migration_tags'));

    // Test that not adding any tags ends up with an empty array.
    $this->drupalPostForm('/admin/config/migrate/migrations/edit/' . $migration->id(), [
      'migration_tags' => '',
    ], 'Save');
    /** @var \Drupal\migrate\Entity\Migration $migration */
    $migration = Migration::load($migration->id());

    $this->assertEqual([], $migration->get('migration_tags'));
  }

  /**
   * Gets the text from the process column for the given row.
   *
   * @param int $row_num
   *   The row number we want the process text for.
   *
   * @return string
   *   A string in the last column which represents the process link/text.
   */
  protected function getTableText($row_num, $col_num) {
    $rows = $this->cssSelect('#migrate-config-table tr');
    return (string) $rows[$row_num]->td[$col_num]->asXml();
  }

}
