<?php

namespace Drupal\cgk_elastic_api\Search\SortOption;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class Relevance.
 */
class Relevance extends FieldSortBase {

  /**
   * Relevance constructor.
   */
  public function __construct() {
    parent::__construct('relevance');
  }

  /**
   * {@inheritdoc}
   */
  public function buildSortQuery(array $sortParameters): array {
    // As the default sort of elastic search is on relevance,
    // no specific sorting options should be returned.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSortLabel(): TranslatableMarkup {
    return $this->t('Relevance');
  }

  /**
   * {@inheritdoc}
   */
  protected function getNextSortOrder(string $currentSortOrder): string {
    return self::SORT_NONE;
  }

  /**
   * {@inheritdoc}
   */
  protected function isActive(array $sort, string $sortOptionId): bool {
    return empty($sort) || parent::isActive($sort, $sortOptionId);
  }

  /**
   * {@inheritdoc}
   */
  protected function addSortToQueryParameters(string $sortOptionId, array $queryParameters, array $sort): array {
    unset($queryParameters['sort']);

    return $queryParameters;
  }

}
