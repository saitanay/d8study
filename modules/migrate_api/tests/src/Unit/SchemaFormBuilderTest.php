<?php

/**
 * @file
 * Contains Drupal\Tests\migrate_api\Unit\SchemaFormBuilderTest.
 */

namespace Drupal\Tests\migrate_api\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Test that the schema form builder works.
 *
 * @group migrate_api
 */
class SchemaFormBuilderTest extends UnitTestCase {

  /**
   * A data provider containing schema definitions and forms.
   *
   * @return array
   *   An array of test cases.
   */
  public function schemaDefinitionsWithExpectedForms() {
    return [
      'Simple element' => [
        [
          'type' => 'product',
          'mapping' => [
            'title' => [
              'label' => 'Product Title',
              'type' => 'string',
            ],
          ],
        ],
        [
          'title' => [
            '#title' => 'Product Title',
            '#type' => 'textfield',
          ],
        ],
      ],
      'Nested mappings' => [
        [
          'type' => 'product',
          'mapping' => [
            'pricing' => [
              'label' => 'Pricing',
              'type' => 'mapping',
              'mapping' => [
                'price' => [
                  'label' => 'Price',
                  'type' => 'integer',
                ],
                'currency' => [
                  'label' => 'Currency',
                  'type' => 'string',
                ],
              ],
            ],
          ],
        ],
        [
          'pricing' => [
            '#title' => 'Pricing',
            '#type' => 'fieldset',
            'price' => [
              '#title' => 'Price',
              '#type' => 'number',
            ],
            'currency' => [
              '#title' => 'Currency',
              '#type' => 'textfield',
            ]
          ],
        ],
      ],
      'Simple sequence' => [
        [
          'type' => 'product',
          'label' => 'Product',
          'mapping' => [
            'reviews' => [
              'type' => 'sequence',
              'label' => 'Reviews',
              'sequence' => [
                'type' => 'string',
                'label' => 'Review',
              ],
            ],
          ],
        ],
        [
          'reviews' => [
            '#type' => 'fieldset',
            '#title' => 'Reviews',
            '0' => [
              '#type' => 'textfield',
              '#title' => 'Review',
            ],
            'add' => [
              '#type' => 'submit',
              '#value' => 'Add another',
              '#name' => 'ajax_id',
              '#submit' => [
                ['class', 'sequenceHandlerSubmit']
              ],
              '#ajax' => [
                'callback' => ['class', 'sequenceHandlerAjax'],
                'wrapper' => 'ajax_id',
              ]
            ],
            '#prefix' => '<div id="ajax_id">',
            '#suffix' => '</div>',
          ],
        ],
      ],
      'Mapping and nested sequence' => [
        [
          'type' => 'product',
          'label' => 'Product',
          'mapping' => [
            'reviews' => [
              'type' => 'sequence',
              'label' => 'Reviews',
              'sequence' => [
                'label' => 'Review',
                'type' => 'mapping',
                'mapping' => [
                  'name' => [
                    'type' => 'string',
                    'label' => 'Username',
                  ],
                  'rating' => [
                    'label' => 'Rating',
                    'type' => 'integer',
                  ],
                  'comments' => [
                    'type' => 'sequence',
                    'label' => 'Comments',
                    'sequence' => [
                      'type' => 'string',
                      'label' => 'Comment',
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        [
          'reviews' => [
            '#type' => 'fieldset',
            '#title' => 'Reviews',
            '0' => [
              '#title' => 'Review',
              '#type' => 'fieldset',
              'name' => [
                '#title' => 'Username',
                '#type' => 'textfield',
              ],
              'rating' => [
                '#title' => 'Rating',
                '#type' => 'number',
              ],
              'comments' => [
                '#type' => 'fieldset',
                '#title' => 'Comments',
                '0' => [
                  '#title' => 'Comment',
                  '#type' => 'textfield',
                ],
                'add' => [
                  '#type' => 'submit',
                  '#value' => 'Add another',
                  '#name' => 'ajax_id',
                  '#submit' => [
                    [
                      'class',
                      'sequenceHandlerSubmit',
                    ]
                  ],
                  '#ajax' => [
                    'callback' => ['class', 'sequenceHandlerAjax'],
                    'wrapper' => 'ajax_id',
                  ]
                ],
                '#prefix' => '<div id="ajax_id">',
                '#suffix' => '</div>',
              ],
            ],
            'add' => [
              '#type' => 'submit',
              '#value' => 'Add another',
              '#name' => 'ajax_id',
              '#submit' => [['class', 'sequenceHandlerSubmit']],
              '#ajax' => [
                'callback' => ['class', 'sequenceHandlerAjax'],
                'wrapper' => 'ajax_id',
              ]
            ],
            '#prefix' => '<div id="ajax_id">',
            '#suffix' => '</div>',
          ],
        ],
      ],
    ];
  }

  /**
   * Test that the schema form builder works.
   *
   * @dataProvider schemaDefinitionsWithExpectedForms
   */
  public function testSchemaFormBuilder($schema_definition, $form_element) {
    $schema_manager = $this->getMockSchemaFormBuilder($schema_definition);
    $form_array = $schema_manager->getFormArray('id', $this->getMock('Drupal\Core\Form\FormStateInterface'));
    // Squash and alter certain types of data from the form definition because
    // they are supurflous to the testing and make writing the data provider
    // more difficult.
    array_walk_recursive($form_array, function (&$value, $key) use ($schema_manager) {
      if ($schema_manager instanceof $value) {
        $value = 'class';
      }
    });
    $this->assertEquals($form_element, $form_array);
  }

  /**
   * Get a mock schema manager which will always serve a specified definition.
   */
  protected function getMockSchemaFormBuilder($definition) {
    $data = $this->getMock('Drupal\Core\TypedData\TraversableTypedDataInterface');
    $data->method('getDataDefinition')->willReturn($definition);
    $manager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');
    $manager->method('get')->willReturn($data);
    $form_builder = $this->getMockBuilder('Drupal\migrate_api\SchemaFormBuilder');
    $form_builder->setConstructorArgs([$manager]);
    $form_builder->setMethods(['uniqueAjaxId', 't']);
    $form_builder_mock = $form_builder->getMock();
    $form_builder_mock->method('uniqueAjaxId')->willReturn('ajax_id');
    $form_builder_mock->method('t')->willReturnCallback(function ($string) {
      return $string;
    });
    return $form_builder_mock;
  }
}
