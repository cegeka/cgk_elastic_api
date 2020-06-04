<?php

namespace Drupal\cgk_elastic_api\Strategy;

use Drupal\cgk_elastic_api\SyncStrategyInterface;
use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\Entity\Index;
use nodespark\DESConnector\ClientInterface;

/**
 * Strategy to configure the autosuggest.
 *
 * @package Drupal\cgk_elastic_api\Strategy
 */
class Autosuggest implements SyncStrategyInterface {

  /**
   * Index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected $index;

  /**
   * Autosuggest constructor.
   */
  public function __construct(Index $index) {
    $this->index = $index;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ClientInterface $client) {
    $index = IndexFactory::getIndexName($this->index);

    try {
      $params = [
        'index' => $index,
        'type' => $this->index->id(),
        'fields' => 'title',
      ];

      $response = $client->indices()->getFieldMapping($params);
      if (empty($response)) {
        return FALSE;
      }

      // Strip the index.
      $response = current($response);
      $fieldMapping = $response['mappings'][$this->index->id()]['title']['mapping']['title'];

      $fieldMapping['fields']['keyword'] = [
        "type" => "keyword",
        "ignore_above" => 256,
      ];
      $fieldMapping['copy_to'] = "search_suggest";
      $fieldMapping['boost'] = 21;

      $params = [
        'index' => $index,
        'type' => $this->index->id(),
        'body' => [
          "properties" => [
            "search_suggest" => [
              "type" => "completion",
            ],
            "title" => $fieldMapping,
          ],
        ],
      ];

      $client->indices()->close(['index' => $index]);
      $client->indices()->putMapping($params);
      $client->indices()->open(['index' => $index]);
      sleep(1);

      return TRUE;
    }
    catch (\Exception $e) {
      watchdog_exception('cgk_elastic_api', $e);
      return FALSE;
    }
  }

}
