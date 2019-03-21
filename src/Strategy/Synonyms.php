<?php

namespace Drupal\cgk_elastic_api\Strategy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cgk_elastic_api\SyncStrategyInterface;
use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\Entity\Index;
use nodespark\DESConnector\ClientInterface;

/**
 * Strategy to sync synonyms.
 *
 * @package Drupal\cgk_elastic_api\Strategy
 */
class Synonyms implements SyncStrategyInterface {

  /**
   * Index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SynonymSync constructor.
   */
  public function __construct(Index $index, ConfigFactoryInterface $configFactory) {
    $this->index = $index;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ClientInterface $client) {
    $synonyms = $this->configFactory->get('cgk_elastic_api.synonym_settings')
      ->get('synonyms');

    if (is_null($synonyms)) {
      return TRUE;
    }

    $synonyms = explode("\r\n", $synonyms);
    $synonyms = array_map(function ($synonym) {
      return trim($synonym, ',');
    }, $synonyms);

    $index = IndexFactory::getIndexName($this->index);

    $params = ['index' => $index];
    $params['body'] = [
      "index" => [
        "analysis" => [
          "filter" => [
            "synonym" => [
              "type" => "synonym_graph",
              "synonyms" => $synonyms,
              "ignore_case" => TRUE,
            ],
          ],
          "analyzer" => [
            "default" => [
              "tokenizer" => "whitespace",
              "filter" => ["lowercase", "synonym"],
            ],
          ],
        ],
      ],
    ];

    try {
      $client->indices()->close(['index' => $index]);
      $client->indices()->putSettings($params);
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
