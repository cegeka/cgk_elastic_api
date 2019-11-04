<?php

namespace Drupal\cgk_elastic_api\Search\SortOption;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class FieldSortBase.
 *
 * Abstract class to provide a simple "fieldName: sortOrder" sort option.
 */
abstract class FieldSortBase implements SortOptionInterface {

  use StringTranslationTrait;

  protected const SORT_ASC = 'asc';

  protected const SORT_DESC = 'desc';

  /**
   * Const indicating the next sorting order is "no sort".
   */
  protected const SORT_NONE = 'none';

  /**
   * The field to sort on.
   *
   * @var string
   */
  protected $sortFieldName;

  /**
   * FieldSortBase constructor.
   */
  public function __construct(string $sortFieldName = NULL) {
    $this->sortFieldName = $sortFieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRenderable(string $sortOptionId, ParameterBag $query, string $route): array {
    $sort = $query->get('sort', []);
    $isActive = $this->isActive($sort, $sortOptionId);
    $attributes = new Attribute();

    if ($isActive) {
      $attributes->addClass('active');
    }

    $queryParameters = $this->addSortToQueryParameters($sortOptionId, $query->all(), $sort);

    $sortParams = $queryParameters['sort'] ?? [];
    $attributes->setAttribute('data-sort-option-name', $sortOptionId);
    $attributes->setAttribute('data-sort-option-params', json_encode($sortParams[$sortOptionId] ?? []));

    return [
      '#theme' => 'cgk_elastic_api_sort_option',
      '#name' => $sortOptionId,
      '#label' => $this->getSortLabel(),
      '#url' => Url::fromRoute($route, [], ['query' => $queryParameters])->toString(),
      '#attributes' => $attributes,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildSortQuery(array $sortParameters): array {
    return [
      $this->getFieldName() => $this->getSortOrder($sortParameters),
    ];
  }

  /**
   * Get the field name to sort on.
   *
   * @return string
   *   The field name to be used in the sort query.
   */
  private function getFieldName(): string {
    return $this->sortFieldName;
  }

  /**
   * Get the sort order.
   *
   * @param array $sortParameters
   *   Sort query parameters.
   *
   * @return string
   *   The sort order (asc or desc).
   */
  private function getSortOrder(array $sortParameters): string {
    return $sortParameters['order'] ?? self::SORT_NONE;
  }

  /**
   * Get the label for the sort option.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The sort option label.
   */
  protected function getSortLabel(): TranslatableMarkup {
    return $this->t('Sort on @label', ['@label' => $this->sortFieldName]);
  }

  /**
   * Get the next sort order by current sort order.
   *
   * @param string $currentSortOrder
   *   Current sort order.
   *
   * @return string
   *   The next sort order.
   */
  protected function getNextSortOrder(string $currentSortOrder): string {
    switch ($currentSortOrder) {
      case self::SORT_DESC:
        return self::SORT_ASC;

      case self::SORT_ASC:
        return self::SORT_NONE;

      default:
        return self::SORT_DESC;
    }
  }

  /**
   * Check if the sort option is active.
   *
   * @param array $sort
   *   The sort parameter.
   * @param string $sortOptionId
   *   The sort option id.
   *
   * @return bool
   *   TRUE is the sort option is active, FALSE otherwise.
   */
  protected function isActive(array $sort, string $sortOptionId): bool {
    return isset($sort[$sortOptionId]);
  }

  /**
   * Add the sort option to the list of query parameters.
   *
   * @param string $sortOptionId
   *   Sort option id.
   * @param array $queryParameters
   *   The HTTP request query.
   * @param array $sort
   *   The sort.
   *
   * @return array
   *   The modified request query parameters.
   */
  protected function addSortToQueryParameters(string $sortOptionId, array $queryParameters, array $sort): array {
    $nextSortOrder = $this->getNextSortOrder($this->getSortOrder($sort[$sortOptionId] ?? []));

    if ($nextSortOrder === self::SORT_NONE) {
      unset($sort[$sortOptionId]);
    }
    else {
      $sort = array_merge($sort, [
        $sortOptionId => ['order' => $nextSortOrder],
      ]);
    }

    $queryParameters['sort'] = $sort;

    return $queryParameters;
  }

}
