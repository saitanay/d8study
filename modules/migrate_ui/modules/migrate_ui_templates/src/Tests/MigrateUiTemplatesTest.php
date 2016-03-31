<?php

/**
 * @file
 * Contains \Drupal\migrate_ui_templates\Tests\MigrateUiTemplatesTest.
 */

namespace Drupal\migrate_ui_templates\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @group migrate_ui_templates
 */
class MigrateUiTemplatesTest extends WebTestBase {

  /**
   * Enable the required modules.
   *
   * @var array
   */
  public static $modules = [
    'migrate',
    'migrate_ui_templates',
    'migrate_ui_test_source',
    'migrate_ui_templates_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser(['administer migration templates']);
    $this->drupalLogin($account);
  }

  /**
   * Test that the templates listing view works.
   */
  public function testTemplatesList() {
    $this->drupalGet('/admin/config/migrate/templates');
    $this->assertResponse(200);
    $this->assertText('Test Template 1');

    // Click build on the first link which will be our test migration.
    $this->clickLink('Build migration');
    $this->assertResponse(200);
    $this->assertText('Are you sure you wanted to build this template into a migration');

    // Confirm the form and then assert the migration is created.
    $this->drupalPostForm(NULL, [], 'Confirm');
    $this->assertText('The migrate_ui_templates_test_template template has been built into a migration');
    $this->assertUrl('/admin/config/migrate/templates');

    // Go back to the build form and ensure we cannot build it again.
    $this->drupalGet(sprintf('/admin/config/migrate/templates/%s/build', 'migrate_ui_templates_test_template'));
    $this->assertText('You cannot rebuild this template, migration already exists');
    $this->assertNoField('edit-submit');
  }

}
