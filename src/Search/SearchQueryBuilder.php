<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface;
use InvalidArgumentException;

/**
 * Builds a URL query array for a given search action.
 */
class SearchQueryBuilder implements SearchQueryBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function buildKeywordQuery(FacetedKeywordSearchAction $searchAction): array {
    $query = [
      'keyword' => $searchAction->getKeyword(),
    ];

    foreach ($searchAction->getChosenFacetValues() as $facet => $values) {
      $values = array_map(
        function (FacetValueInterface $value) {
          return $value->value();
        },
        iterator_to_array($values)
      );
      $query[$facet] = $values;
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFacetedQuery(FacetedSearchAction $searchAction): array {
    if (!$searchAction instanceof FacetedSearchAction) {
      throw new InvalidArgumentException('Expected a ' . FacetedSearchAction::class);
    }

    $query = [];

    foreach ($searchAction->getChosenFacetValues() as $facet => $values) {
      $values = array_map(
        function (FacetValueInterface $value) {
          return $value->value();
        },
        iterator_to_array($values)
      );
      $query[$facet] = $values;
    }

    return $query;
  }

}
