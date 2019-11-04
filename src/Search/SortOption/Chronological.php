<?php

namespace Drupal\cgk_elastic_api\Search\SortOption;


use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class Chronological.
 */
class Chronological extends FieldSortBase {

  /**
   * {@inheritdoc}
   */
  protected function getSortLabel(): TranslatableMarkup {
    return $this->t('Chronological');
  }

  /**
   * {@inheritdoc}
   */
  protected function getNextSortOrder(string $currentSortOrder): string {
    return ($currentSortOrder === self::SORT_NONE) ? self::SORT_DESC : self::SORT_NONE;
  }

}
