<?php
/**
 * @file
 * Contains \Drupal\voya_im_representative_block\Plugin\Block\RepresentativeBlock.
 */

namespace Drupal\voya_im_representative_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "my_voya_representative_block",
 *   admin_label = @Translation("Representative Block"),
 *   category = @Translation("Blocks")
 * )
 */
class RepresentativeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['anonymous_title'] = array(
      '#title' => $this->t('Anonymous block title'),
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['anonymous_title']) ? $this->configuration['anonymous_title'] : '',
      '#description' => $this->t('Title for anonymous user.'),
    );
    $form['anonymous_body'] = array(
      '#title' => $this->t('Anonymous block body'),
      '#type' => 'text_format',
      '#default_value' => isset($this->configuration['anonymous_body']) ? $this->configuration['anonymous_body'] : '',
      '#rows' => 4,
      '#format' => 'full_html',
      '#description' => $this->t('Body for anonymous user.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['anonymous_title'] = $form_state->getValue('anonymous_title');
    $this->configuration['anonymous_body'] = $form_state->getValue('anonymous_body');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (\Drupal::currentUser()->isAuthenticated()) {
      // $curr_user = user_load_by_mail(\Drupal::currentUser()->getEmail());
      // return ['#markup' => 'Hello logged in user!'];
      $build = [
        '#theme' => 'representative_authenticated_block',
        '#block_title' => 'My Representatives',
        '#wholesaler_name' => 'Troy D. Chakarun CPWA',
        '#wholesaler_title' => 'SVP, National Sales Manager - Private Wealth & Advisory',
        '#wholesaler_phone' => '(415) 232-3234',
        '#wholesaler_email' => 'gulab.bisht@gmail.com',
      ];
    }
    else {
      $representative_block_storage_settings = \Drupal::entityTypeManager()->getStorage('block')->load('representativeblock')->get('settings');
      //kint($representative_block_storage_settings);


      $build = [
        '#markup' => $representative_block_storage_settings['anonymous_body']['value'],
      ];
    }
    return $build;
  }

}
