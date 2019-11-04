<?php

namespace Drupal\cgk_elastic_api\Search\Facet\Control;

use Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface;
use Drupal\cgk_elastic_api\Search\SearchResult;

/**
 * Interface for facet control services.
 *
 * Each facet should implements this interface,
 * even if they implement \CompositeFacetControlInterface,
 * in order to render the visual representation of the facet.
 */
interface FacetControlInterface {

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
  public function build(string $facet, FacetedSearchActionInterface $searchAction, SearchResult $result): array;

  /**
   * Get the Drupal field (machine) name.
   *
   * See the configured field in search_api.
   *
   * @return string
   *   The field's machine name.
   */
  public function getFieldName(): string;

  /**
   * Indicate if the facet should be added to the query aggregations.
   *
   * Some facets should not necessarily be added to an elastic query's
   * aggregations, for example facets relying on custom forms.
   *
   * @return bool
   *   TRUE if it should be added, FALSE if not.
   */
  public function addToAggregations(): bool;

}
