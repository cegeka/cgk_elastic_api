<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Flat facet value, without any hierarchy.
 */
class FlatFacetValue implements FacetValueInterface {

  /**
   * The actual value as a string.
   *
   * @var string
   */
  private $value;

  /**
   * FlatFacetValue constructor.
   *
   * @param string $value
   *   The value.
   */
  public function __construct(string $value) {
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function matches(FacetValueInterface $other): bool {
    return $this->value == $other->value();
  }

  /**
   * {@inheritdoc}
   */
  public function value(): string {
    return $this->value;
  }

}
