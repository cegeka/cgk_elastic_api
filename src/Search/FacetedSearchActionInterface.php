<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;

/**
 * Base interface for search actions.
 */
interface FacetedSearchActionInterface {

  /**
   * Get the available facets.
   *
   * @return string[]
   *   The available facets.
   */
  public function getAvailableFacets(): array;

  /**
   * Get the chosen facet values.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetCollection
   *   The chosen facet values.
   */
  public function getChosenFacetValues(): FacetCollection;

  /**
   * Checks if the given facet value was chosen.
   *
   * @param string $facet
   *   The facet id.
   * @param string $value
   *   The facet value.
   *
   * @return bool
   *   True if the facet value was chosen.
   */
  public function facetValueWasChosen(string $facet, string $value): bool;

  /**
   * Get a copy of the search action with a facet value removed.
   *
   * @param string $facet
   *   The facet id.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $facetValue
   *   The facet value.
   *
   * @return \Drupal\cgk_elastic_api\Search\FacetedSearchAction
   *   A new search action.
   */
  public function withoutFacetValue(string $facet, FacetValueInterface $facetValue): FacetedSearchAction;

  /**
   * Get a copy of the search action with a facet value added.
   *
   * @param string $facet
   *   The facet id.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $value
   *   The facet value.
   *
   * @return \Drupal\cgk_elastic_api\Search\FacetedSearchAction|static
   *   The new search action.
   */
  public function withFacetValue(string $facet, FacetValueInterface $value): FacetedSearchAction;

  /**
   * Get a copy of the search action, with all chosen facets removed.
   *
   * @return static
   *   The new search action.
   */
  public function withoutFacet(string $facet): FacetedSearchActionInterface;

  /**
   * Get a copy of the search action with the specified values for a facet.
   *
   * @param string $facet
   *   The facet id.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection $facetValues
   *   The facet values.
   *
   * @return static
   *   The new search action.
   */
  public function withFacet(string $facet, FacetValuesCollection $facetValues): FacetedSearchActionInterface;

  /**
   * Get a copy of the search action, with all chosen facets removed.
   *
   * @return \Drupal\cgk_elastic_api\Search\FacetedSearchAction
   *   A new search action.
   */
  public function withoutFacets(): FacetedSearchActionInterface;

  /**
   * Get the result set page size.
   *
   * @return int
   *   The page size
   */
  public function getSize(): int;

  /**
   * Get the result set offset.
   *
   * @return int
   *   The offset.
   */
  public function getFrom(): int;

  /**
   * Creates a new action with a different result set offset.
   *
   * @param int $from
   *   The offset.
   *
   * @return static
   *   Action with the new offset.
   */
  public function from(int $from): FacetedSearchActionInterface;

  /**
   * Get the next offset.
   *
   * @return int
   *   The next offset.
   */
  public function nextFrom(): int;

  /**
   * Checks if there are more pages.
   *
   * @param int $total_results
   *   Total results in a result set.
   *
   * @return bool
   *   TRUE if there are more pages, FALSE if not.
   */
  public function hasMorePages(int $total_results): bool;

}
