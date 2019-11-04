<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\SortOption\SortOptionCollection;

/**
 * Class FacetedKeywordSearchAction.
 */
class FacetedKeywordSearchAction extends FacetedSearchAction {

  /**
   * The keyword.
   *
   * @var string
   */
  protected $keyword;

  /**
   * FacetedKeywordSearchAction constructor.
   */
  public function __construct(
    int $size,
    string $keyword = NULL,
    FacetCollection $facetValues = NULL,
    array $availableFacets = [],
    SortOptionCollection  $sortValues = NULL) {
    parent::__construct($size, $facetValues, $availableFacets, $sortValues);
    $this->keyword = $keyword;
  }

  /**
   * Get the keyword.
   *
   * @return string
   *   The keyword.
   */
  public function getKeyword() {
    return $this->keyword;
  }

}
