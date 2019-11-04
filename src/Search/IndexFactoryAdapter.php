<?php

namespace Drupal\cgk_elastic_api\Search;

use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\IndexInterface;

/**
 * Class IndexFactoryAdapter.
 */
class IndexFactoryAdapter {

  /**
   * Index factory.
   *
   * @var \Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory
   */
  private $indexFactory;

  /**
   * IndexFactoryAdapter constructor.
   */
  public function __construct(IndexFactory $indexFactory) {
    $this->indexFactory = $indexFactory;
  }

  /**
   * Helper function. Returns the Elasticsearch name of an index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index object.
   *
   * @return string
   *   The name of the index on the Elasticsearch server. Includes a prefix for
   *   uniqueness, the database name, and index machine name.
   */
  public function getIndexName(IndexInterface $index) {
    return $this->indexFactory::getIndexName($index);
  }

}
