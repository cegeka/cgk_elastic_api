<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Composite facet value, where values are keyed.
 */
class CompositeFacetValue implements FacetValueInterface {

  /**
   * The actual value as a string.
   *
   * @var array
   */
  private $values;

  /**
   * FlatFacetValue constructor.
   *
   * @param array $values
   *   The values.
   */
  public function __construct(array $values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function matches(FacetValueInterface $other): bool {
    return in_array($other->value(), $this->values);
  }

  /**
   * {@inheritdoc}
   */
  public function value(): string {
    return end($this->values);
  }

  /**
   * Get the (composite) values.
   *
   * @return array
   *   List of (composite) values.
   */
  public function getCompositeValue(): array {
    return $this->values;
  }

}
