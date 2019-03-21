<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use InvalidArgumentException;

/**
 * Models the current search action.
 */
class FacetedSearchAction implements FacetedSearchActionInterface {

  /**
   * The chosen facet values.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetCollection
   */
  protected $chosenFacetValues;

  /**
   * The available facets.
   *
   * @var array
   */
  protected $availableFacets;

  /**
   * Position in results to start from.
   *
   * @var int
   */
  protected $from = 0;

  /**
   * Size of one result set.
   *
   * @var int
   */
  protected $size;

  /**
   * FacetedSearchAction constructor.
   *
   * @param int $size
   *   Desired size of one page in the result set.
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetCollection|null $facetValues
   *   The facet values.
   * @param array $availableFacets
   *   All available facets.
   */
  public function __construct(
    int $size,
    FacetCollection $facetValues = NULL,
    array $availableFacets = []) {
    $this->size = $size;
    $this->chosenFacetValues = $facetValues ?: new FacetCollection();
    $this->availableFacets = $availableFacets;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableFacets(): array {
    return $this->availableFacets;
  }

  /**
   * {@inheritdoc}
   */
  public function getChosenFacetValues(): FacetCollection {
    return $this->chosenFacetValues;
  }

  /**
   * {@inheritdoc}
   */
  public function facetValueWasChosen(string $facet, string $value): bool {
    return
      $this->chosenFacetValues->has($facet) &&
      $this->chosenFacetValues->values($facet)->contains(new FlatFacetValue($value));
  }

  /**
   * {@inheritdoc}
   */
  public function withoutFacetValue(string $facet, FacetValueInterface $facetValue): FacetedSearchAction {
    if (!$this->chosenFacetValues->has($facet)) {
      throw new InvalidArgumentException(
        sprintf('there are no chosen values for facet %s', $facet)
      );
    }

    if (!$this->chosenFacetValues->values($facet)->contains($facetValue)) {
      throw new InvalidArgumentException(
        sprintf('value %s is not chosen for facet %s', $facetValue->value(), $facet)
      );
    }

    $c = clone $this;

    $values = $c->chosenFacetValues->values($facet)->without($facetValue);
    if ($values->isEmpty()) {
      $c->chosenFacetValues = $c->chosenFacetValues->without($facet);
    }
    else {
      $c->chosenFacetValues = $c->chosenFacetValues->with(
        $facet,
        $values
      );
    }

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function withFacetValue(string $facet, FacetValueInterface $value): FacetedSearchAction {
    if ($this->chosenFacetValues->has($facet) && $this->chosenFacetValues->values($facet)->contains($value)) {
      throw new InvalidArgumentException(
        sprintf('value %s is already chosen for facet %s', $value->value(), $facet)
      );
    }

    $valuesBase = $this->chosenFacetValues->values($facet);

    $c = clone $this;

    $c->chosenFacetValues = $c->chosenFacetValues->with($facet, $valuesBase->with($value));

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function withoutFacet(string $facet): FacetedSearchActionInterface {
    $c = clone $this;

    $c->chosenFacetValues = $c->chosenFacetValues->without($facet);

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function withFacet(string $facet, FacetValuesCollection $facetValues): FacetedSearchActionInterface {
    $c = clone $this;

    $c->chosenFacetValues = $this->chosenFacetValues->with($facet, $facetValues);

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function withoutFacets(): FacetedSearchActionInterface {
    $c = clone $this;

    $c->chosenFacetValues = new FacetCollection();

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize(): int {
    return $this->size;
  }

  /**
   * {@inheritdoc}
   */
  public function getFrom(): int {
    return $this->from;
  }

  /**
   * {@inheritdoc}
   */
  public function from(int $from): FacetedSearchActionInterface {
    $c = clone $this;

    $c->from = $from;

    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function nextFrom(): int {
    return $this->from + $this->size;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMorePages(int $total_results): bool {
    return $total_results > $this->nextFrom();
  }

}
