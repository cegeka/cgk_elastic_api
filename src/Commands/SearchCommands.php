<?php

namespace Drupal\cgk_elastic_api\Commands;

use Drupal\cgk_elastic_api\SyncService;
use Drupal\search_api\Entity\Index;
use Drush\Commands\DrushCommands;

/**
 * Class SearchCommands.
 */
class SearchCommands extends DrushCommands {

  /**
   * Index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * The sync service.
   *
   * @var \Drupal\cgk_elastic_api\SyncService
   */
  protected $syncService;

  /**
   * SearchCommands constructor.
   */
  public function __construct(Index $index, SyncService $syncService) {
    $this->index = $index;
    $this->syncService = $syncService;
  }

  /**
   * Reset the search index with an nGram analyzer.
   *
   * @command reset-search-index-with-ngram-analyzer
   */
  public function resetSearchIndexWithNgramAnalyzer() {
    $this->index->clear();
    $this->index->reindex();

    $this->syncService->sync();

    $this->index->indexItems();
  }

}
