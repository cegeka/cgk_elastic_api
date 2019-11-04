<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Tree storage implementation on top of Drupal terms.
 */
class TermFacetValueMetaDataTreeStorage implements FacetValueMetaDataTreeStorageInterface {

  /**
   * The meta data storage.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface
   */
  protected $metaDataStorage;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The vocabulary name.
   *
   * @var string
   */
  protected $vocabularyName;

  /**
   * TermFacetValueMetaDataTreeStorage constructor.
   *
   * @param \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface $metaDataStorage
   *   The meta data storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    FacetValueMetaDataStorageInterface $metaDataStorage,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->metaDataStorage = $metaDataStorage;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function loadTopLevel(): array {
    $terms = $this->termStorage->loadTree($this->vocabularyName, 0, 1);

    return $this->termsToMetaData($terms);
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren(string $parentParentId): array {
    $terms = $this->termStorage->loadTree($this->vocabularyName, $parentParentId, 1);

    return $this->termsToMetaData($terms);
  }

  /**
   * Creates meta data from terms.
   *
   * @param array $terms
   *   The terms.
   *
   * @return array
   *   The meta data.
   */
  protected function termsToMetaData(array $terms): array {
    $metaData = [];

    foreach ($terms as $term) {
      $facetValueMetaData = $this->metaDataStorage->load($term->tid);
      if ($facetValueMetaData) {
        $metaData[$term->tid] = $facetValueMetaData;
      }
    }

    return $metaData;
  }

  /**
   * {@inheritdoc}
   */
  public function setVocabulary(string $vocabularyId): void {
    $this->vocabularyName = $vocabularyId;
  }

}
