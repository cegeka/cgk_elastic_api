<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Interface for hierarchical facet values.
 */
interface HierarchicalFacetValueInterface extends FacetValueInterface {

  /**
   * Get the full hierarchy, as an array of strings.
   *
   * @return string[]
   *   The full hierarchy.
   */
  public function hierarchy(): array;

  /**
   * Get the ancestors of the facet.
   *
   * For each ancestor a corresponding facet value is created.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\HierarchicalFacetValueInterface[]
   *   The ancestor facets.
   */
  public function ancestors(): array;

  /**
   * Get the facet parent, if any.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\HierarchicalFacetValueInterface|null
   *   The facet parent, or null if it's the top level element.
   */
  public function parent(): ?HierarchicalFacetValueInterface;

}
