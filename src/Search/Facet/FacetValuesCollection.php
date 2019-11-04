<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * A collection of active facet values.
 */
class FacetValuesCollection implements IteratorAggregate, Countable {

  /**
   * The facet values in the collection.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface[]
   */
  protected $values;

  /**
   * FacetValuesCollection constructor.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface[] ...$facetValues
   *   The facet values to put in the collection.
   */
  public function __construct(FacetValueInterface ...$facetValues) {
    $this->values = $facetValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new ArrayIterator($this->values);
  }

  /**
   * Create a new collection without a particular value.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $value
   *   The value to remove from the collection.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection
   *   A new collection.
   */
  public function without(FacetValueInterface $value): FacetValuesCollection {
    return $this->filter(new Without($value));
  }

  /**
   * Create a new collection with a particular value added.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $value
   *   The new facet value.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection
   *   A new collection.
   */
  public function with(FacetValueInterface $value): FacetValuesCollection {
    $c = clone $this;

    $c->values[] = $value;

    return $c;
  }

  /**
   * Find out if the collection contains the facet value.
   *
   * This is also true for a fuzzy match, e.g. when for hierarchical facets
   * an ancestor is in the collection.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $value
   *   The facet value.
   *
   * @return bool
   *   True if the collection contains the value, false otherwise.
   */
  public function contains(FacetValueInterface $value): bool {
    foreach ($this->values as $current_value) {
      if ($current_value->matches($value)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Verify if the collection is empty.
   *
   * @return bool
   *   True if empty, false if not.
   */
  public function isEmpty(): bool {
    return empty($this->values);
  }

  /**
   * Create a new collection with values allowed by the filter.
   *
   * @param callable $filter
   *   The filter to apply.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection
   *   A new collection with the filter applied.
   */
  public function filter(callable $filter): FacetValuesCollection {
    $c = clone $this;

    $newValues = [];

    foreach ($this->values as $value) {
      if ($filter($value)) {
        $newValues[] = $value;
      }
    }

    $c->values = $newValues;

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->values);
  }

}
