<?php

namespace Drupal\cgk_elastic_api\Search\SortOption;

use IteratorAggregate;

/**
 * Class SortOptionCollection.
 */
class SortOptionCollection implements IteratorAggregate {

  /**
   * The values.
   *
   * @var array
   */
  private $values = [];

  /**
   * Adds a sort option value to the collection.
   *
   * @param string $sortOptionId
   *   The sort option id.
   * @param array $values
   *   The sort option values to add.
   *
   * @return \Drupal\cgk_elastic_api\Search\SortOption\SortOptionCollection
   *   The collection with the new value.
   */
  public function with(string $sortOptionId, array $values): SortOptionCollection {
    $c = clone $this;

    $c->values[$sortOptionId] = $values;

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->values);
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
