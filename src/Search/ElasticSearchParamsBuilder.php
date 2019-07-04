<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\Control\CompositeFacetControlInterface;
use Drupal\cgk_elastic_api\Search\Facet\Control\FacetControlInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\SortOption\SortOptionCollection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;

/**
 * Builds parameters to pass with an ElasticSearch search request.
 */
class ElasticSearchParamsBuilder {

  /**
   * Index id.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected $index;

  /**
   * The lang code of the current language.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Index factory adapter.
   *
   * @var \Drupal\cgk_elastic_api\Search\IndexFactoryAdapter
   */
  protected $indexFactoryAdapter;

  /**
   * ElasticSearchParamsBuilder constructor.
   */
  public function __construct(IndexInterface $index, LanguageManagerInterface $languageManager, IndexFactoryAdapter $indexFactoryAdapter) {
    $this->index = $index;
    $this->langcode = $languageManager->getCurrentLanguage()->getId();
    $this->indexFactoryAdapter = $indexFactoryAdapter;
  }

  /**
   * Builds ElasticSearch parameters for the current search action.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The current search action to build the parameters for.
   *
   * @return array
   *   Parameters to pass to the ElasticSearch client.
   */
  public function build(FacetedSearchActionInterface $searchAction): array {
    $standard_filters = $this->getStandardFilters();

    if ($searchAction instanceof FacetedKeywordSearchAction && $keyword = $searchAction->getKeyword()) {
      $standard_filters[] = [
        [
          'function_score' => [
            'functions' => [
              [
                'filter' => [
                  'multi_match' => [
                    'query' => $keyword,
                    'fields' => [
                      'title^5',
                      'title.ngram',
                      'body^5',
                      'body.ngram',
                    ],
                  ],
                ],
                'weight' => 2,
              ],
            ],
            'score_mode' => 'multiply',
          ],
        ],
        [
          'bool' => [
            'should' => [
              'match' => [
                'custom_all' => $keyword,
              ],
            ],
          ],
        ],
      ];
    }

    $params = [
      'body' => [
        '_source' => FALSE,
        'from' => $searchAction->getFrom(),
        'size' => $searchAction->getSize(),
        'query' => [
          'bool' => [
            'must' => $standard_filters,
          ],
        ],
      ],
    ];

    $chosenFacetValues = $searchAction->getChosenFacetValues();

    if (count($searchAction->getAvailableFacets())) {
      $params['body']['aggs'] = $this->buildAggregations($searchAction, $chosenFacetValues);
    }

    if (!$chosenFacetValues->isEmpty()) {
      $post_filter = $this->buildFacetFilters($chosenFacetValues);
      $params['body']['post_filter'] = ['bool' => ['must' => $post_filter]];
    }

    $chosenSort = $searchAction->getChosenSort();
    if (!$chosenSort->isEmpty()) {
      $params['body']['sort'] = $this->buildSort($chosenSort);
    }

    $index = $this->getIndexName($this->index);
    $params['index'] = $index;

    return $params;
  }

  /**
   * Get standard filters for the search query.
   *
   * Returns a list of standard filters, such as language, published state,
   * and content type.
   *
   * @return array
   *   Standard filters.
   */
  protected function getStandardFilters() {
    return [
      [
        'term' => [
          'langcode' => $this->langcode,
        ],
      ],
      [
        'term' => [
          'status' => 1,
        ],
      ],
    ];
  }

  /**
   * Builds a filter for a given set of facet values.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetCollection $facet_values
   *   Facet values.
   *
   * @return array
   *   Array to be used as an ElasticSearch filter.
   */
  protected function buildFacetFilters(FacetCollection $facet_values): array {
    $post_filter = [];
    /* @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface[] $selected_values */
    foreach ($facet_values as $facet => $selected_values) {
      $facetControlService = \Drupal::service('cgk_elastic_api.facet_control.' . $facet);
      if ($facetControlService instanceof CompositeFacetControlInterface) {
        $facet_post_filter = $facetControlService->buildFacetFilter($selected_values);
      }
      elseif ($facetControlService instanceof FacetControlInterface) {
        $facet_post_filter = [];
        foreach ($selected_values as $selected_value) {
          $facet_post_filter[] = [
            'term' => [
              $facetControlService->getFieldName() => $selected_value->value(),
            ],
          ];
        }
      }

      if (count($facet_post_filter) > 1) {
        $post_filter[] = [
          'bool' => [
            'should' => $facet_post_filter,
          ],
        ];
      }
      elseif (count($facet_post_filter) === 1) {
        $post_filter[] = reset($facet_post_filter);
      }
    }

    return $post_filter;
  }

  /**
   * Build aggregations for an elastic query.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The search action to get available facets from.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetCollection $chosenFacetValues
   *   Chosen facet values.
   *
   * @return array
   *   List of aggregations.
   */
  protected function buildAggregations(FacetedSearchActionInterface $searchAction, FacetCollection $chosenFacetValues) {
    $aggregations = [];

    foreach ($searchAction->getAvailableFacets() as $facet) {
      /** @var \Drupal\cgk_elastic_api\Search\Facet\Control\FacetControlInterface $facetControlService */
      $facetControlService = \Drupal::service('cgk_elastic_api.facet_control.' . $facet);
      if (!$facetControlService->addToAggregations()) {
        continue;
      }

      $aggregation_facet_values = $chosenFacetValues->without($facet);
      // Use a sub-aggregation & apply the the filter of all other facets on it.
      if (!$aggregation_facet_values->isEmpty()) {
        $agg_filter = $this->buildFacetFilters($aggregation_facet_values);

        $aggregations[$facet] = [
          'filter' => ['bool' => ['must' => $agg_filter]],
          'aggs' => [
            'filtered' => [
              'terms' => [
                'field' => $facetControlService->getFieldName(),
                'size' => 999,
              ],
            ],
          ],
        ];
      }
      else {
        $aggregations[$facet] = [
          'terms' => [
            'field' => $facetControlService->getFieldName(),
            'size' => 999,
          ],
        ];
      }
    }

    return $aggregations;
  }

  /**
   * Build the sort for the query.
   *
   * @param \Drupal\cgk_elastic_api\Search\SortOption\SortOptionCollection $chosenSort
   *   List of selected sort options.
   *
   * @return array
   *   The built sort query.
   */
  private function buildSort(SortOptionCollection $chosenSort): array {
    $sort = [];

    foreach ($chosenSort as $sortOption => $sortParameters) {
      $serviceId = 'cgk_elastic_api.sort_option.' . $sortOption;
      if (\Drupal::hasService($serviceId)) {
        /** @var \Drupal\cgk_elastic_api\Search\SortOption\SortOptionInterface $sortOptionService */
        $sortOptionService = \Drupal::service('cgk_elastic_api.sort_option.' . $sortOption);
        $sort[] = $sortOptionService->buildSortQuery($sortParameters);
      }
    }

    if (count($sort) > 1) {
      $sort = array_merge(...$sort);
    }

    return $sort;
  }

  /**
   * Get the name of the index.
   *
   * @param \Drupal\search_api\Entity\Index $index
   *   The index.
   *
   * @return string
   *   The index name.
   */
  protected function getIndexName(Index $index) {
    return $this->indexFactoryAdapter->getIndexName($index);
  }

}
