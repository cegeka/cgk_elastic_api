<?php

namespace Drupal\cgk_elastic_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SearchForm.
 */
class SearchForm extends FormBase {

  /**
   * The route name of the autocomplete endpoint.
   *
   * @var string
   */
  protected $autocompleteRouteName = 'cgk_elastic_api.autocomplete';

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'cgk_elastic_api_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param bool $add_inline_autocomplete
   *   Boolean to indicate if the autocomplete suffix should be added.
   * @param string $custom_keyword_id
   *   Custom id for the keyword field.
   * @param string|null $custom_form_id
   *   Custom id for the form.
   * @param bool $ajaxForm
   *   Boolean indicating if this form should support ajax submits.
   * @param string $redirectRouteName
   *   Name of the route to redirect to on form submit.
   * @param array $additionalQueryParams
   *   Additional query parameters to include in the redirect.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, bool $add_inline_autocomplete = FALSE, string $custom_keyword_id = NULL, string $custom_form_id = NULL, bool $ajaxForm = FALSE, string $redirectRouteName = 'cgk_elastic_api.search', array $additionalQueryParams = []) {
    $form_state->set('redirectRouteName', $redirectRouteName);
    $form_state->set('additionalQueryParams', $additionalQueryParams);
    $keyword = $this->getRequest()->get('keyword');

    if ($ajaxForm) {
      $form['#attributes']['data-ajax-search-form'] = 1;
    }

    if (!is_null($custom_keyword_id)) {
      $form['#attributes']['id'] = $custom_form_id;
    }

    $form['keyword'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $this->t('What are you searching for?'),
        'autocomplete' => 'off',
      ],
      '#default_value' => $keyword,
      '#prefix' => '<span class="glyphicon glyphicon-search" aria-hidden="true"></span>',
    ];

    if ($add_inline_autocomplete) {
      $form['keyword']['#suffix'] = '<div class="search-autocomplete-inline"></div>';
      $form['#attached']['library'][] = 'cgk_elastic_api/inline-autocomplete';
      $form['#attached']['drupalSettings']['cgk_elastic_api']['header_form_class'] = Html::getClass($this->getFormId());
      $form['#attached']['drupalSettings']['cgk_elastic_api']['autocomplete_endpoint'] = Url::fromRoute($this->autocompleteRouteName)->toString();
    }

    if (!is_null($custom_keyword_id)) {
      $form['keyword']['#id'] = $custom_keyword_id;
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#id' => 'edit-submit-search-submit',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $routeName = $form_state->get('redirectRouteName');
    $queryParams = $form_state->get('additionalQueryParams');
    $queryParams['keyword'] = $form_state->getValue('keyword');

    $form_state->setRedirect($routeName, [], [
      'query' => $queryParams,
    ]);
  }

}
