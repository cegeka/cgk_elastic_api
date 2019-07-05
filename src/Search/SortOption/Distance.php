<?php

namespace Drupal\cgk_elastic_api\Search\SortOption;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class Distance.
 */
class Distance extends FieldSortBase {

  /**
   * Relevance constructor.
   */
  public function __construct() {
    parent::__construct('distance');
  }

  /**
   * {@inheritdoc}
   */
  public function buildSortQuery(array $sortParameters): array {

    var_dump($sortParameters);
    return [
      '_geo_distance' => [
        "latlon" => [
          "lat" => $sortParameters['lat'],
          "lon" => $sortParameters['lon'],
        ],
        "order" => "asc",
        "unit" => "km",
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function isActive(array $sort, string $sortOptionId, array $queryParameters = []): bool {
    return isset($sort[$sortOptionId]) || (empty($sort) && !empty($queryParameters['location']));
  }

  /**
   * {@inheritdoc}
   */
  protected function getSortLabel(): TranslatableMarkup {
    return $this->t('Distance');
  }

  /**
   * {@inheritdoc}
   */
  protected function getNextSortOrder(string $currentSortOrder): string {
    return ($currentSortOrder === self::SORT_NONE) ? self::SORT_DESC : self::SORT_NONE;
  }

  /**
   * {@inheritdoc}
   */
  protected function addSortToQueryParameters(string $sortOptionId, array $queryParameters, array $sort): array {
    //if (!$this->isActive($sort, $sortOptionId, $queryParameters)) {
      $sort = array_merge($sort, [
        $sortOptionId => [
          'lat' => 40.123,
          'lon' => 1,
          ]
      ]);
      $queryParameters['sort'] = $sort;
    //}
    return $queryParameters;
  }

}
