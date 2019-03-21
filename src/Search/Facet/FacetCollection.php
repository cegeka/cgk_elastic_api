<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

use ArrayIterator;
use IteratorAggregate;

/**
 * Collection of facets.
 */
class FacetCollection implements IteratorAggregate {

  /**
   * The values.
   *
   * @var array|FacetValuesCollection[]
   */
  private $values = [];

  /**
   * Creates a new collection with a facet added.
   *
   * @param string $facetName
   *   Name of the facet.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection $values
   *   The facet values.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetCollection
   *   The new facet collection.
   */
  public function with(string $facetName, FacetValuesCollection $values) {
    $c = clone $this;

    $c->values[$facetName] = $values;

    return $c;
  }

  /**
   * Creates a new collection without the specified facet.
   *
   * @param string $facetName
   *   Name of the facet.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetCollection
   *   The new facet collection.
   */
  public function without(string $facetName) {
    $c = clone $this;

    unset($c->values[$facetName]);

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new ArrayIterator($this->values);
  }

  /**
   * Get the values of a facet.
   *
   * @param string $facetName
   *   Name of the facet.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection
   *   The facet values.
   */
  public function values(string $facetName): FacetValuesCollection {
    if (isset($this->values[$facetName])) {
      return $this->values[$facetName];
    }
    else {
      return new FacetValuesCollection();
    }
  }

  /**
   * Find out if the collection contains values for this facet.
   *
   * @param string $facetName
   *   Name of the facet.
   *
   * @return bool
   *   Wether the collection contains values for this facet.
   */
  public function has(string $facetName): bool {
    return isset($this->values[$facetName]);
  }

  /**
   * Verifies if the collection is empty.
   *
   * @return bool
   *   True if empty, false if not.
   */
  public function isEmpty(): bool {
    return empty($this->values);
  }

}
