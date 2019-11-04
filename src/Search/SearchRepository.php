<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elasticsearch_connector\ElasticSearch\ClientManagerInterface;
use Drupal\search_api\Entity\Index;

/**
 * Class SearchRepository.
 */
class SearchRepository {

  /**
   * The search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;
  /**
   * The search cluster.
   *
   * @var \Drupal\elasticsearch_connector\Entity\Cluster
   */
  protected $cluster;

  /**
   * The client manager.
   *
   * @var \Drupal\elasticsearch_connector\ElasticSearch\ClientManagerInterface
   */
  protected $clientManager;

  /**
   * SearchRepository constructor.
   */
  public function __construct(Index $index, EntityTypeManagerInterface $entityTypeManager, ClientManagerInterface $clientManager) {
    $this->index = $index;

    /** @var \Drupal\search_api\Entity\Server $server */
    $server = $this->index->getServerInstance();
    /** @var \Drupal\elasticsearch_connector\Plugin\search_api\backend\SearchApiElasticsearchBackend $backend */
    $backend = $server->getBackend();

    $this->cluster = $entityTypeManager->getStorage('elasticsearch_cluster')->load($backend->getCluster());
    $this->clientManager = $clientManager;
  }

  /**
   * Query the client.
   *
   * @param array $params
   *   Search params.
   *
   * @return \nodespark\DESConnector\Elasticsearch\Response\SearchResponseInterface
   *   Search response.
   */
  public function query(array $params) {
    $client = $this->clientManager->getClientForCluster($this->cluster);

    return $client->search($params);
  }

  /**
   * Loads an item from the search index.
   *
   * @param string $id
   *   Id of the item to load.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface|null
   *   Loaded item.
   */
  public function loadItem(string $id) {
    return $this->index->loadItem($id);
  }

  /**
   * Returns a list of entities.
   *
   * @param array $hits
   *   Search hits.
   *
   * @return array
   *   List of loaded items.
   */
  public function getItemValueFromHits(array $hits) {
    /** @var \Drupal\Core\Entity\EntityInterface[] $hits */
    return array_map(
      function (string $id) {
        /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $item */
        $item = $this->loadItem($id);
        return $item->getValue();
      },
      $hits
    );
  }

}
