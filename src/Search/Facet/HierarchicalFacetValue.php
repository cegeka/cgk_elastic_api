<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Hierarchical facet values, with an ancestry.
 */
class HierarchicalFacetValue implements FacetValueInterface, HierarchicalFacetValueInterface {

  /**
   * The actual values as a hiearchical list of strings.
   *
   * @var string[]
   */
  private $values;

  /**
   * Constructor.
   *
   * @param string[] ...$values
   *   The values, listed in hierarchical order.
   */
  public function __construct(string ...$values) {
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
   * {@inheritdoc}
   */
  public function hierarchy(): array {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function ancestors(): array {
    $count = count($this->values) - 1;
    $items = [];

    for ($i = 1; $i <= $count; $i++) {
      $values = array_slice($this->values, 0, $i);
      $items[] = new self(...$values);
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function parent(): ?HierarchicalFacetValueInterface {
    $ancestors = $this->ancestors();

    return array_pop($ancestors);
  }

}
