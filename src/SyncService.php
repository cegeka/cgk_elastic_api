<?php

namespace Drupal\cgk_elastic_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elasticsearch_connector\ElasticSearch\ClientManager;
use Drupal\search_api\Entity\Index;

/**
 * Class SynonymSync.
 */
class SyncService {

  /**
   * Elasticsearch client.
   *
   * @var \nodespark\DESConnector\ClientInterface
   */
  private $client;

  /**
   * Search api index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * A collection of sync strategies.
   *
   * @var array
   */
  private $strategies;

  /**
   * SynonymSync constructor.
   */
  public function __construct(Index $index, ClientManager $clientManager, EntityTypeManagerInterface $entityTypeManager, array $strategies) {
    $this->index = $index;
    /** @var \Drupal\search_api\Entity\Server $server */
    $server = $this->index->getServerInstance();
    /** @var \Drupal\elasticsearch_connector\Plugin\search_api\backend\SearchApiElasticsearchBackend $backend */
    $backend = $server->getBackend();
    /** @var \Drupal\elasticsearch_connector\Entity\Cluster $cluster */
    $cluster = $entityTypeManager->getStorage('elasticsearch_cluster')
      ->load($backend->getCluster());
    $this->client = $clientManager->getClientForCluster($cluster);
    $this->strategies = $strategies;
  }

  /**
   * Sync synonyms with elasticsearch index.
   */
  public function sync() {
    // Update analysis.
    /** @var \Drupal\cgk_elastic_api\SyncStrategyInterface $strategy */
    foreach ($this->strategies as $strategy) {
      $strategy->execute($this->client);
    }

    // Reindex all items, so synonyms are correctly picked up.
    $this->reindexItems();
  }

  /**
   * Mark all items for reindexing, and index them.
   */
  private function reindexItems() {
    $this->index->reindex();
    $this->index->indexItems();
  }

}
