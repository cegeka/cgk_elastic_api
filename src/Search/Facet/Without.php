<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Facet filter which excludes a specific facet.
 */
class Without {

  /**
   * The facet to exclude.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface
   */
  private $facetToExclude;

  /**
   * Without constructor.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $facetToExclude
   *   The facet to exclude.
   */
  public function __construct(FacetValueInterface $facetToExclude) {
    $this->facetToExclude = $facetToExclude;
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(FacetValueInterface $facetValue) {
    return !$facetValue->matches($this->facetToExclude);
  }

}
