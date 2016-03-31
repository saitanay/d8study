<?php

/**
 * @file
 * Contains \Drupal\migrate_ui\Form\FilterForm
 */

namespace Drupal\migrate_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * The FilterForm class.
 */
class FilterForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_ui.filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = [
      '#title' => $this->t('Filters'),
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $tags = $form_state->getBuildInfo()['args'][0]['tags'];
    $form['filters']['tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Tags'),
      '#options' => ['_none' => '- Select - '] + array_combine($tags, $tags),
      '#weight' => -1,
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#weight' => -1,
    ];
    $form['filters']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#weight' => -1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $op = (string) $form_state->getTriggeringElement()['#value'];
    if ('Reset' === $op) {
      $form_state->setRedirect('entity.migration.collection');
    }
    elseif ($tag = $form_state->getValue('tag')) {
      $form_state->setRedirect('entity.migration.collection', [], [
        'query' => ['filter' => $tag],
      ]);
    }
  }
}
