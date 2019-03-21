<?php

namespace Drupal\cgk_elastic_api\Search;

/**
 * Interface for search URL query builders.
 */
interface SearchQueryBuilderInterface {

  /**
   * Builds a URL query array for a given search action.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction $searchAction
   *   The current search action.
   *
   * @return array
   *   The URL query array
   */
  public function buildKeywordQuery(FacetedKeywordSearchAction $searchAction): array;

  /**
   * Builds a URL query array for a given search action with support for facets.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchAction $searchAction
   *   The current search action.
   *
   * @return array
   *   The URL query array
   */
  public function buildFacetedQuery(FacetedSearchAction $searchAction): array;

}
