<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Interface for facet values.
 */
interface FacetValueInterface {

  /**
   * Whether this value matches another one.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $other
   *   Other facet value to compare with.
   *
   * @return bool
   *   Wether it matches or not.
   */
  public function matches(FacetValueInterface $other): bool;

  /**
   * Get the value as a string.
   *
   * @return string
   *   The value as a string.
   */
  public function value(): string;

}
