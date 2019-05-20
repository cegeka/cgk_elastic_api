<?php

namespace Drupal\cgk_elastic_api\Strategy;

use Drupal\cgk_elastic_api\SyncStrategyInterface;
use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\Entity\Index;
use nodespark\DESConnector\ClientInterface;

/**
 * Strategy to copy mappings to a custom _all field.
 *
 * @package Drupal\cgk_elastic_api\Strategy
 */
class CustomAll implements SyncStrategyInterface {

  const UNSUPPORTED_FIELD_TYPES = [
    'object',
    'nested',
  ];

  /**
   * Index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * CustomAll constructor.
   */
  public function __construct(Index $index) {
    $this->index = $index;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ClientInterface $client) {
    /** @var \Drupal\search_api\Entity\Index $index */
    $configuredFields = $this->index->getFields();

    $indexName = IndexFactory::getIndexName($this->index);

    try {
      $params = [
        'index' => $indexName,
        'type' => $this->index->id(),
        'fields' => '*',
      ];

      $response = $client->indices()->getFieldMapping($params);
      if (empty($response)) {
        return FALSE;
      }

      // Strip the index.
      // TODO check if we can't just use the loaded index (name)
      $response = current($response);

      $params = [
        'index' => $indexName,
        'type' => $this->index->id(),
        'body' => [
          'properties' => [
            'custom_all' => [
              'type' => 'text',
              'analyzer' => 'ngram_analyzer',
            ],
          ],
        ],
      ];

      foreach ($configuredFields as $configuredField) {
        if (in_array($configuredField->getType(), static::UNSUPPORTED_FIELD_TYPES)) {
          continue;
        }

        $mapping = $response['mappings'][$this->index->id()][$configuredField->getFieldIdentifier()]['mapping'][$configuredField->getFieldIdentifier()];
        $mapping['copy_to'][] = 'custom_all';

        $params['body']['properties'][$configuredField->getFieldIdentifier()] = $mapping;
      }

      $client->indices()->close(['index' => $indexName]);
      $client->indices()->putMapping($params);
      $client->indices()->open(['index' => $indexName]);
      sleep(1);

      return TRUE;
    }
    catch (\Exception $e) {
      watchdog_exception('cgk_elastic_api', $e);
      return FALSE;
    }
  }

}
