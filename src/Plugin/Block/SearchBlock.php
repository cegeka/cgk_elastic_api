<?php

namespace Drupal\cgk_elastic_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\cgk_elastic_api\Form\SearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a management block.
 *
 * @Block(
 *   id = "cgk_elastic_api",
 *   admin_label = @Translation("Search form"),
 * )
 */
class SearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formbuilder;

  /**
   * SearchBlock constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formbuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formbuilder = $formbuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $form = $this->formbuilder->getForm(SearchForm::class, TRUE, 'site-search', 'cgk-header-search-form');
    $form['actions']['submit']['#id'] = 'do-search';
    $form['keyword']['#prefix'] = '';
    $form['keyword']['#attributes']['placeholder'] = '';
    $form['#attributes']['class'] = ['cgk-search-form'];

    return [
      'form' => $form,
    ];
  }

}
