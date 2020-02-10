<?php

namespace Drupal\cgk_elastic_api\Search\SortOption;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Interface SortOptionInterface.
 */
interface SortOptionInterface {

  /**
   * Build a render array to represent the sort option.
   *
   * @param string $sortOptionId
   *   (Machine) name of the sort option.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   The HTTP request query.
   * @param string $route
   *   Route used for urls.
   *
   * @return array
   *   A render array.
   */
  public function buildRenderable(string $sortOptionId, ParameterBag $query, string $route): array;

  /**
   * Build the option to be used in an ES query.
   *
   * @param array $sortParameters
   *   Sort parameters for the sort option.
   *
   * @return array
   *   The sort option with its values.
   */
  public function buildSortQuery(array $sortParameters): array;

}
