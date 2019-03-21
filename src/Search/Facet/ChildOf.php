<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Facet filter which only includes children of the specific parent.
 */
class ChildOf {

  /**
   * The parent.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface
   */
  private $parent;

  /**
   * Constructor.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface $parent
   *   The parent.
   */
  public function __construct(FacetValueInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(FacetValueInterface $facetValue) {
    if (!$facetValue instanceof HierarchicalFacetValue) {
      return FALSE;
    }

    $parent = $facetValue->parent();

    if (!$parent) {
      return FALSE;
    }

    return $parent->value() == $this->parent->value();
  }

}
