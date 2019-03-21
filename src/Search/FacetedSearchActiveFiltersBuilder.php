<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\Core\Url;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface;
use Drupal\cgk_elastic_api\Search\Facet\HierarchicalFacetValueInterface;

/**
 * Builds a render array for displaying the active search filters.
 */
class FacetedSearchActiveFiltersBuilder {

  /**
   * The route name.
   *
   * @var string
   */
  private $route;

  /**
   * The facet metadata storage.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface
   */
  private $metaDataStorage;

  /**
   * The url query builder.
   *
   * @var \Drupal\cgk_elastic_api\Search\SearchQueryBuilder
   */
  private $queryBuilder;

  /**
   * ActiveFiltersBuilder constructor.
   *
   * @param string $route
   *   The route name.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface $metaDataStorage
   *   The facet value meta data storage.
   * @param \Drupal\cgk_elastic_api\Search\SearchQueryBuilderInterface $queryBuilder
   *   The url query builder.
   */
  public function __construct(
    string $route,
    FacetValueMetaDataStorageInterface $metaDataStorage,
    SearchQueryBuilderInterface $queryBuilder
  ) {
    $this->route = $route;
    $this->metaDataStorage = $metaDataStorage;
    $this->queryBuilder = $queryBuilder;
  }

  /**
   * Builds a render array for displaying the active search filters.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchAction|\Drupal\cgk_elastic_api\Search\SearchActionInterface $searchAction
   *   The current search action.
   *
   * @return array|null
   *   Either a render array or NULL if there are no active filters.
   */
  public function build(FacetedSearchAction $searchAction): ?array {
    $activeFilters = [];

    foreach ($searchAction->getChosenFacetValues() as $facet => $facet_values) {
      $items = [];
      foreach ($facet_values as $facet_value) {
        if ($facet_value instanceof HierarchicalFacetValueInterface) {
          $ancestors = $facet_value->ancestors();
          $items = array_merge($items, $ancestors);
        }

        $items[] = $facet_value;
      }

      $uniqueItems = [];
      while ($item = array_shift($items)) {
        foreach ($items as $key => $otherItem) {
          if ($item == $otherItem) {
            unset($items[$key]);
          }
        }

        $uniqueItems[] = $item;
      }

      foreach ($uniqueItems as $item) {
        $facet_id = $item->value();

        $facetMetaData = $this->metaDataStorage->load($facet_id);

        if (!$facetMetaData) {
          continue;
        }

        $searchActionWithoutThisValue = $searchAction->withoutFacetValue(
          $facet,
          $item
        );

        // If the facet has a parent, activate it for the new search action.
        if ($item instanceof HierarchicalFacetValueInterface) {
          $parent = $item->parent();
          if ($parent && !$searchActionWithoutThisValue->getChosenFacetValues()->values($facet)->contains($parent)) {
            $searchActionWithoutThisValue = $searchActionWithoutThisValue->withFacetValue($facet,
              $parent);
          }
        }

        $url_options = [
          'query' => $this->queryBuilder->buildFacetedQuery(
            $searchActionWithoutThisValue
          ),
        ];

        $activeFilters[] = [
          '#type' => 'link',
          '#url' => Url::fromRoute($this->route, [], $url_options),
          '#title' => $facetMetaData->label(),
          '#attributes' => [
            'class' => [
              'u-hidden-mobile',
              'active-filters-filter',
            ],
            'title' => "Filter {$facetMetaData->label()} verwijderen",
            'aria-label' => $facetMetaData->label(),
            'data-drupal-facet-item-facet' => $facet,
            'data-drupal-facet-item-value' => $item->value(),
          ],
        ];
      }
    }

    if (!empty($activeFilters)) {
      $activeFilters = [
        '#type' => 'container',
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => 'Gekozen filters:',
          '#attributes' => [
            'class' => 'u-hidden-mobile',
          ],
        ],
        'items' => $activeFilters,
        '#attributes' => [
          'class' => [
            'active-filters',
          ],
        ],
      ];

      $url_options = ['query' => $this->queryBuilder->buildFacetedQuery($searchAction->withoutFacets())];
      $activeFilters['remove-all'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute($this->route, [], $url_options),
        '#attributes' => [
          'class' => [
            'active-filters-remove-all',
          ],
          'aria-label' => 'Alle filters verwijderen',
        ],
        '#title' => 'Alle filters verwijderen',
      ];

      return $activeFilters;
    }
    else {
      return NULL;
    }
  }

}
