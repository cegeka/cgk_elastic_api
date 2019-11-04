<?php

namespace Drupal\cgk_elastic_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SynonymForm.
 */
class SynonymForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synonym_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['synonyms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Synonyms'),
      '#description' => $this->t('Comma-separated list of synonyms. 1 group of synonyms per line. E.g.<br />subsidiedb, subsidiedatabank<br />kmo-portefeuille, kmoportefeuille<br /><br />For performance reasons, the list of synonyms is updated daily at midnight.'),
      '#default_value' => $this->config('cgk_elastic_api.synonym_settings')
        ->get('synonyms'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cgk_elastic_api.synonym_settings')
      ->set('synonyms', $form_state->getValue('synonyms'))
      ->save();

    drupal_set_message($this->t('Synonyms saved successfully, and will be synced with the search engine tonight.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cgk_elastic_api.synonym_settings'];
  }

}
