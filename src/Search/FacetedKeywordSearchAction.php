<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;

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
  public function __construct(int $size, string $keyword = NULL, FacetCollection $facetValues = NULL, array $availableFacets = []) {
    parent::__construct($size, $facetValues, $availableFacets);
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
