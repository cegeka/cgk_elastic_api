<?php

namespace Drupal\cgk_elastic_api\Search\Facet\Control;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataTreeStorageInterface;
use Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface;
use Drupal\cgk_elastic_api\Search\SearchResult;

/**
 * Class TermFacetBase.
 *
 * This can be used to create a simple/typical "terms" facet.
 */
abstract class TermFacetBase implements FacetControlInterface {

  use StringTranslationTrait;

  const SORT_ALPHABETICAL = 0;
  const SORT_FACET_COUNT = 1;
  const SORT_TERM_WEIGHT = 2;

  /**
   * The term storage.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataTreeStorageInterface
   */
  protected $facetValueMetaDataTreeStorage;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The term view builder.
   *
   * @var \Drupal\taxonomy\TermViewBuilder
   */
  protected $termViewBuilder;

  /**
   * Sort method for facet values.
   *
   * @var int
   */
  protected $facetValuesSortMethod;

  /**
   * Boolean indicating if multiple values can be selected.
   *
   * @var bool
   */
  protected $canSelectMultiple;

  /**
   * Boolean indicating if the facet should enable hierarchical values.
   *
   * Will print child terms of active facets.
   *
   * @var bool
   */
  protected $enableHierarchy;

  /**
   * Boolean indicating if empty facets with count 0 should be printed.
   *
   * Will print facets that have a count of 0.
   *
   * @var bool
   */
  protected $includeEmptyFacets;

  /**
   * Constructor.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataTreeStorageInterface $facetValueMetaDataTreeStorage
   *   The facet value meta data tree storage.
   * @param string $routeName
   *   The route name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(FacetValueMetaDataTreeStorageInterface $facetValueMetaDataTreeStorage, string $routeName, EntityTypeManagerInterface $entityTypeManager) {
    $this->facetValueMetaDataTreeStorage = $facetValueMetaDataTreeStorage;
    $this->routeName = $routeName;
    $this->termViewBuilder = $entityTypeManager->getViewBuilder('taxonomy_term');
    $this->facetValuesSortMethod = self::SORT_ALPHABETICAL;
    $this->canSelectMultiple = TRUE;
    $this->enableHierarchy = FALSE;
    $this->includeEmptyFacets = TRUE;
  }

  /**
   * Build a render array with the faceting UI.
   *
   * @param string $facet
   *   The facet.
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The active search action.
   * @param \Drupal\cgk_elastic_api\Search\SearchResult $result
   *   The search result.
   *
   * @return array
   *   A render array.
   */
  public function build(string $facet, FacetedSearchActionInterface $searchAction, SearchResult $result): array {
    $terms = $this->facetValueMetaDataTreeStorage->loadTopLevel();
    $facetCounts = $result->getFacetCounts($facet);

    return $this->buildFacetsFromTerms($facet, $terms, $facetCounts, $searchAction, $this->getFacetTitle());
  }

  /**
   * Build a list of facets from terms.
   *
   * @param string $facet
   *   The facet.
   * @param array $terms
   *   Terms to be used as facet values.
   * @param array $facetCounts
   *   Counts of facet values.
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The active search action.
   * @param string $facetTitle
   *   Title of the facet.
   * @param bool $excludeWrapperAttributes
   *   FALSE if wrapper attributes for the facet should be excluded.
   *
   * @return array
   *   Render array - list of facets.
   */
  protected function buildFacetsFromTerms(string $facet, array $terms, array $facetCounts, FacetedSearchActionInterface $searchAction, string $facetTitle, $excludeWrapperAttributes = FALSE, $alwaysShowChildren = FALSE) {
    $terms = $this->sortTerms($terms, $this->facetValuesSortMethod, $facetCounts);

    $values = [];
    foreach ($terms as $termId => $term) {
      /** @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData $term */
      $title = $term->label();
      $isActive = $searchAction->facetValueWasChosen($facet, $termId);

      $facetAttributes = [
        'data-drupal-facet-item-id' => $facet,
        'data-drupal-facet-item-value' => $termId,
        'id' => sprintf('%s-%s', $facet, $termId),
      ];

      // When there is no matching term returned in the Elasticsearch
      // aggregation, still allow to deselect the facet value.
      if (isset($facetCounts[$termId]) || $isActive) {
        $count = $facetCounts[$termId] ?? 0;

        if ($isActive) {
          $facetAttributes['class'] = ['is-active'];
          $facetAttributes['checked'] = 'checked';
        }

        $value = [
          '#type' => 'checkbox',
          '#wrapper_attributes' => ['class' => ['facet-item']],
          '#attributes' => $facetAttributes,
          '#children' => [
            '#theme' => 'cgk_elastic_api_facets_result_item',
            '#is_active' => $isActive,
            '#value' => $title,
            '#show_count' => TRUE,
            '#count' => $count,
            '#for' => $facetAttributes['id'],
          ],
        ];

        if ($this->enableHierarchy) {
          $children = $this->buildChildren($termId, $facet, $facetCounts, $searchAction);
          if ($children) {
            $value['#children']['#has_children'] = TRUE;
          }
          if ($alwaysShowChildren) {
            $value['#children']['#children'] = $children;
          } else {
            if ($isActive) {
              $value['#children']['#children'] = $children;
            }
          }
        }

        $values[] = $value;
      }
      elseif ($this->includeEmptyFacets) {
        $facetAttributes['disabled'] = 'disabled';

        $values[] = [
          '#type' => 'checkbox',
          '#wrapper_attributes' => ['class' => ['facet-item']],
          '#attributes' => $facetAttributes,
          '#children' => [
            '#theme' => 'cgk_elastic_api_facets_result_item',
            '#is_active' => FALSE,
            '#value' => $title,
            '#show_count' => TRUE,
            '#count' => 0,
          ],
        ];
      }

    }

    $facetListAttributes = [
      'class' => ['facet', "facet-$facet"],
      'data-facet' => $facet,
      'data-facet-list' => 1,
    ];

    if ($this->hasEnabledHierarchy()) {
      $facetListAttributes['data-facet-hierarchy'] = 1;
      $facetListAttributes['class'][] = 'has-hierarchy';

      if ($this->canSelectMultiple) {
        $facetListAttributes['data-facet-hierarchy-multiple'] = 1;

      }
    }

    if (!$this->canSelectMultiple) {
      $facetListAttributes['data-facet-single'] = 1;
    }

    if ($excludeWrapperAttributes) {
      $facetListAttributes = [];
    }

    return [
      '#theme' => 'cgk_elastic_api_facets_item_list',
      '#items' => $values,
      '#title' => $facetTitle,
      '#attributes' => $facetListAttributes,
      '#wrapper_attributes' => ['class' => ["facet-wrapper-$facet"]],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];
  }

  /**
   * Build children for a parent facet item.
   *
   * @param int $parentId
   *   Id of the term to get children for.
   * @param string $facet
   *   The facet.
   * @param array $facetCounts
   *   Counts of facet values.
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The search action.
   *
   * @return array|null
   *   NULL if there are no children, of a render array of facets.
   */
  private function buildChildren(int $parentId, string $facet, array $facetCounts, FacetedSearchActionInterface $searchAction) {
    $terms = $this->facetValueMetaDataTreeStorage->loadChildren($parentId);

    if (empty($terms)) {
      return NULL;
    }

    return $this->buildFacetsFromTerms($facet, $terms, $facetCounts, $searchAction, '', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function addToAggregations(): bool {
    return TRUE;
  }

  /**
   * Get the facet title.
   *
   * @return string
   *   Title for the facet.
   */
  protected function getFacetTitle() {
    return sprintf('<h2>%s</h2>', $this->t('Facet'));
  }

  /**
   * Set the vocabulary to be used.
   *
   * @param string $vocabularyId
   *   Id of the vocabulary.
   */
  protected function setVocabulary(string $vocabularyId) {
    $this->facetValueMetaDataTreeStorage->setVocabulary($vocabularyId);
  }

  /**
   * Set the sort method for facet values.
   *
   * @param int $sortMethod
   *   Sort method for facet values.
   *
   * @throws \Exception
   *   If an unknown sort method is used.
   */
  protected function setfacetValuesSortMethod(int $sortMethod) {
    if (!in_array($sortMethod, [
      self::SORT_ALPHABETICAL,
      self::SORT_FACET_COUNT,
      self::SORT_TERM_WEIGHT,
    ])) {
      throw new \Exception('Sort method not supported. Must be one of [self::SORT_ALPHABETICAL, self::SORT_FACET_COUNT, self::SORT_TERM_WEIGHT]');
    }

    $this->facetValuesSortMethod = $sortMethod;
  }

  /**
   * Sets if multiple facet values can be selected or not.
   *
   * @param bool $multiple
   *   TRUE if multiple values can be selected, false otherwise.
   */
  protected function setCanSelectMultiple(bool $multiple) {
    $this->canSelectMultiple = $multiple;
  }

  public function getCanSelectMultiple() {
    return $this->canSelectMultiple;
  }

  /**
   * Sets if child facet terms should be printed for active facets.
   *
   * @param bool $enabled
   *   TRUE if children should be printed, FALSE otherwise.
   */
  protected function setEnableHierarchy(bool $enabled) {
    $this->enableHierarchy = $enabled;
  }

  /**
   * Sets if facet should print facets that are empty with count 0.
   *
   * @param bool $enabled
   *    TRUE if empty facets should be printed, FALSE otherwise.
   */
  protected function setIncludeEmptyFacets(bool $enabled) {
    $this->includeEmptyFacets = $enabled;
  }

  /**
   * Check if the facet has hierarchy enabled.
   *
   * @return bool
   *   TRUE if the facet has hierarchy enabled, FALSE otherwise.
   */
  public function hasEnabledHierarchy() {
    return $this->enableHierarchy;
  }

  /**
   * Sort terms by the configured sort method.
   *
   * @param array $terms
   *   Terms to sort.
   * @param int $sortMethod
   *   Sort method to be used.
   * @param array $facetCounts
   *   Facet counts.
   *
   * @return array
   *   Sorted terms.
   */
  protected function sortTerms(array $terms, int $sortMethod, array $facetCounts) {
    uasort($terms, function ($termA, $termB) use ($sortMethod, $facetCounts) {
      /** @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData $termA */
      /** @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData $termB */
      switch ($sortMethod) {
        case self::SORT_TERM_WEIGHT:
          return $termA->getOriginalObject()->getWeight() <=> $termB->getOriginalObject()->getWeight();

        case self::SORT_FACET_COUNT:
          $countA = $facetCounts[$termA->getOriginalObject()->id()] ?? 0;
          $countB = $facetCounts[$termB->getOriginalObject()->id()] ?? 0;

          if ($countA === $countB) {
            return $termA->label() <=> $termB->label();
          }

          return $countB <=> $countA;

        case self::SORT_ALPHABETICAL:
        default:
          return $termA->label() <=> $termB->label();
      }
    });

    return $terms;
  }

}
