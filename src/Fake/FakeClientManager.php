<?php

namespace Drupal\cgk_elastic_api\Fake;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\elasticsearch_connector\ElasticSearch\ClientManagerInterface;
use Drupal\elasticsearch_connector\Entity\Cluster;
use nodespark\DESConnector\ClientFactoryInterface;

/**
 * Class FakeClientManager.
 *
 * This fake client class prevents the site from breaking
 * if the elasticsearch_connector module is not yet enabled
 * while this search module is.
 *
 * @see \Drupal\cgk_elastic_api\SearchServiceProvider
 */
class FakeClientManager implements ClientManagerInterface {

  /**
   * Create a client manager.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \nodespark\DESConnector\ClientFactoryInterface $clientManagerFactory
   *   Client factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ClientFactoryInterface $clientManagerFactory) {

  }

  /**
   * Get a client to interact with the given Elasticsearch cluster.
   *
   * @param \Drupal\elasticsearch_connector\Entity\Cluster $cluster
   *   Cluster to get a client for.
   *
   * @return \nodespark\DESConnector\ClientInterface
   *   Client object to interact with the given cluster.
   */
  public function getClientForCluster(Cluster $cluster) {
    return NULL;
  }

}
