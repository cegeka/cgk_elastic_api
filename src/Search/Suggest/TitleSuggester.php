<?php

namespace Drupal\cgk_elastic_api\Search\Suggest;

use Drupal\cgk_elastic_api\Search\SearchRepository;
use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\Entity\Index;

/**
 * Class TitleSuggester.
 */
class TitleSuggester implements SuggesterInterface {

  /**
   * The index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * The search repository.
   *
   * @var \Drupal\cgk_elastic_api\Search\SearchRepository
   */
  private $repository;

  /**
   * TitleSuggester constructor.
   *
   * @param \Drupal\search_api\Entity\Index $index
   *   The index.
   * @param \Drupal\cgk_elastic_api\Search\SearchRepository $repository
   *   The search repository.
   */
  public function __construct(Index $index, SearchRepository $repository) {
    $this->index = $index;
    $this->repository = $repository;
  }

  /**
   * {@inheritdoc}
   */
  public function suggest(string $text) {
    $index = IndexFactory::getIndexName($this->index);

    $response = $this->repository->query([
      'index' => $index,
      'type' => $this->index->id(),
      'body' => [
        "suggest" => [
          "text" => $text,
          "simple_phrase" => [
            "phrase" => [
              "field" => "title",
              "size" => 1,
              "gram_size" => 3,
              "direct_generator" => [
                [
                  "field" => "title",
                  "suggest_mode" => "always",
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
    $rawResponse = $response->getRawResponse();
    $rawSuggestions = $rawResponse['suggest']['simple_phrase'][0]['options'];

    return array_map(function ($rawSuggestion) {
      return $rawSuggestion['text'];
    }, $rawSuggestions);
  }

}
