<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cgk_elastic_api\Search\Facet\Control\CompositeFacetControlInterface;
use Drupal\cgk_elastic_api\Search\Facet\Control\TermFacetBase;
use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\Facet\HierarchicalFacetValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Factory for search actions.
 *
 * Builds a search action based on HTTP request query parameters.
 */
class SearchActionFactory {

  /**
   * Result set size.
   *
   * @var int
   */
  private $size;

  /**
   * Term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * SearchActionFactory constructor.
   */
  public function __construct(int $size, EntityTypeManagerInterface $entityTypeManager) {
    $this->size = $size;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Creates a search action from HTTP request query parameters.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   The HTTP request query.
   * @param array $facets
   *   Facet collection.
   * @param bool $isXmlHttpRequest
   *   Boolean indicating if the current request is a XMLHttpRequest.
   *
   * @return \Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction
   *   The search action.
   */
  public function searchActionFromQuery(ParameterBag $query, array $facets, bool $isXmlHttpRequest): FacetedKeywordSearchAction {
    $keyword = $query->get('keyword');
    $facetValues = new FacetCollection();

    foreach ($facets as $facet) {
      if (\Drupal::hasService('cgk_elastic_api.facet_control.' . $facet)) {
        $facetControlService = \Drupal::service('cgk_elastic_api.facet_control.' . $facet);
        if ($facetControlService instanceof CompositeFacetControlInterface) {
          $values = $facetControlService->buildFacetValuesFromQuery($query, $facet);
        }
        elseif ($facetControlService instanceof TermFacetBase && $facetControlService->hasEnabledHierarchy() && !$facetControlService->getCanSelectMultiple()) {
          $values = $this->getHierarchicalValues($query->get($facet, []));
        }
      }

      if (!isset($values)) {
        $values = $query->get($facet, []);
        if (!empty($values) && !is_array($values)) {
          $values = [$values];
        }
        $values = array_map(
          function ($value) {
            return new FlatFacetValue($value);
          },
          $values
        );
      }

      if (!empty($values)) {
        $facetValues = $facetValues->with(
          $facet,
          new FacetValuesCollection(...$values)
        );
      }
      unset($values);
    }

    // If this is not an xmlHttpRequest, but a from is set, we need to update
    // the size rather than the from, so all results are loaded (instead of only
    // the results that need to be appended).
    if (!$isXmlHttpRequest && $from = $query->getInt('from', 0)) {
      if ($from > 2) {
        $from--;
      }

      $this->size = $this->size * ($from);
    }

    $searchAction = new FacetedKeywordSearchAction($this->size, $keyword, $facetValues, $facets);

    $from = $query->getInt('page', 0);
    if ($from) {
      $searchAction = $searchAction->from($from * $searchAction->getSize());
    }

    return $searchAction;
  }

  /**
   * Get the result set size.
   *
   * @return int
   *   The result set size.
   */
  public function getSize(): int {
    return $this->size;
  }

  /**
   * Set the size of the result set.
   *
   * @param int $size
   *   The size to set.
   */
  public function setSize(int $size): void {
    $this->size = $size;
  }

  /**
   * Get facet values for hierarchical term-based facets.
   *
   * @param array $values
   *   List of facet values.
   *
   * @return array
   *   List of hierarchical facet values.
   */
  private function getHierarchicalValues(array $values) {
    $termStorage = $this->termStorage;

    return array_map(
      function ($value) use ($termStorage) {
        $parents = $termStorage->loadAllParents($value);
        $hierarchy = array_keys($parents);
        $hierarchy[] = $value;
        return new HierarchicalFacetValue(...$hierarchy);
      },
      $values
    );
  }

}
