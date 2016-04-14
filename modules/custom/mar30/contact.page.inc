<?php

/**
 * @file
 * Contains contact.page.inc.
 *
 * Page callback for Contact entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Contact templates.
 *
 * Default template: contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_contact(array &$variables) {
  // Fetch Contact Entity Object.
  $contact = $variables['elements']['#contact'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
