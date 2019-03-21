<?php

namespace Drupal\cgk_elastic_api\Search\Facet\Control;

use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Interface for composite facet control services.
 *
 * Composite facets should implement this interface,
 * to create more complex filters to be added to the search query,
 * and to correctly parse them.
 */
interface CompositeFacetControlInterface {

  /**
   * Parse a result to a list of facet items with their respective result count.
   *
   * @param array $facetItems
   *   A list of facet items for this facet.
   *
   * @return array
   *   List of facet items with their result count.
   */
  public function parseResult(array $facetItems): array;

  /**
   * Build a filter to be used in the search query.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection $selected_values
   *   The selected values.
   *
   * @return array
   *   The built facet filter.
   */
  public function buildFacetFilter(FacetValuesCollection $selected_values): array;

  /**
   * Build a list of chosen facet values from the HTTP request query.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   The HTTP request query.
   * @param string $facetId
   *   Id of the facet.
   *
   * @return array
   *   List of facet values.
   */
  public function buildFacetValuesFromQuery(ParameterBag $query, string $facetId): array;

}
