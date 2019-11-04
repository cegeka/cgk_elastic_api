<?php

namespace Drupal\cgk_elastic_api\Strategy;

use Drupal\cgk_elastic_api\SyncStrategyInterface;
use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\Entity\Index;
use nodespark\DESConnector\ClientInterface;

/**
 * Class DidYouMean.
 */
class DidYouMean implements SyncStrategyInterface {

  /**
   * Index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * DidYouMean constructor.
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

      $fieldMapping['fields']['trigram'] = [
        'type' => 'text',
        'analyzer' => 'trigram',
      ];

      $mappingParams = [
        'index' => $index,
        'type' => $this->index->id(),
        'body' => [
          'properties' => [
            'title' => $fieldMapping,
          ],
        ],
      ];

      $analysisParams = [
        'index' => $index,
        'body' => [
          'analysis' => [
            'filter' => [
              'shingle' => [
                'type' => 'shingle',
                'min_shingle_size' => 2,
                'max_shingle_size' => 3,
              ],
            ],
            'analyzer' => [
              'trigram' => [
                'type' => 'custom',
                'tokenizer' => 'standard',
                'filter' => [
                  'lowercase',
                  'shingle',
                ],
              ],
            ],
          ],
        ],
      ];

      $client->indices()->close(['index' => $index]);
      $client->indices()->putSettings($analysisParams);
      $client->indices()->putMapping($mappingParams);
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
