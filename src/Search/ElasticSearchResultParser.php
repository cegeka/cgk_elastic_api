<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\cgk_elastic_api\Search\Facet\Control\CompositeFacetControlInterface;

/**
 * Parses a raw ElasticSearch response into a SearchResult object.
 */
class ElasticSearchResultParser {

  /**
   * Parses a raw ElasticSearch response into a SearchResult object.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The current search action.
   * @param array $response
   *   The raw ElasticSearch response, as an array.
   *
   * @return \Drupal\cgk_elastic_api\Search\SearchResult
   *   The parsed search result.
   */
  public function parse(FacetedSearchActionInterface $searchAction, array $response): SearchResult {
    $total = (int) $response['hits']['total'];

    $raw_hits = $response['hits']['hits'];

    // Reduce hits to the actual ID of the hit.
    $hits = array_column($raw_hits, '_id');

    $facetCounts = [];

    $aggs = $response['aggregations'] ?? [];

    foreach ($searchAction->getAvailableFacets() as $facet) {
      if (!isset($aggs[$facet])) {
        $facetCounts[$facet] = [];
      }
      else {
        $aggregation = $aggs[$facet];

        $buckets = isset($aggregation['filtered']) ? $aggregation['filtered']['buckets'] : $aggregation['buckets'];
        $facetCounts[$facet] = array_column($buckets, 'doc_count', 'key');

        if (\Drupal::hasService('cgk_elastic_api.facet_control.' . $facet)) {
          $facetControlService = \Drupal::service('cgk_elastic_api.facet_control.' . $facet);
          if ($facetControlService instanceof CompositeFacetControlInterface) {
            $facetCounts[$facet] = $facetControlService->parseResult($facetCounts[$facet]);
          }
        }
      }
    }

    return new SearchResult($total, $hits, $facetCounts);
  }

}
