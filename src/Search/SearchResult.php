<?php

namespace Drupal\cgk_elastic_api\Search;

/**
 * Value object representing the search results.
 */
class SearchResult {

  /**
   * Total count of search results.
   *
   * @var int
   */
  private $total;

  /**
   * Counts of facet values, indexed by the facet id.
   *
   * @var array
   */
  private $facetCounts;

  /**
   * IDs of search hits.
   *
   * @var string[]
   */
  private $hits;

  /**
   * SearchResult constructor.
   *
   * @param int $total
   *   Total count of search results.
   * @param array $hits
   *   IDs of search hits.
   * @param array $facetCounts
   *   Counts of facet values, indexed by the facet id.
   */
  public function __construct(int $total, array $hits, array $facetCounts) {
    $this->hits = $hits;
    $this->facetCounts = $facetCounts;
    $this->total = $total;
  }

  /**
   * Get the total count of results.
   *
   * @return int
   *   Total amount of results.
   */
  public function getTotal(): int {
    return $this->total;
  }

  /**
   * Get the count of facet values.
   *
   * @return array
   *   Counts of facet values, indexed by the facet id.
   */
  public function getFacetCounts($facet = NULL): array {
    if (is_null($facet)) {
      return $this->facetCounts;
    }

    if (!isset($this->facetCounts[$facet])) {
      return NULL;
    }

    return $this->facetCounts[$facet];
  }

  /**
   * Get the hits.
   *
   * @return array
   *   IDs of the hits.
   */
  public function getHits(): array {
    return $this->hits;
  }

}
