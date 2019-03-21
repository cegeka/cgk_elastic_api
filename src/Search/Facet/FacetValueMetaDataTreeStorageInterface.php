<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Interface for facet value meta data tree storage.
 */
interface FacetValueMetaDataTreeStorageInterface {

  /**
   * Loads the top level facet values meta data.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData[]
   *   The meta data.
   */
  public function loadTopLevel(): array;

  /**
   * Loads the facet values meta data of the children of the specified parent.
   *
   * @param string $parentId
   *   The parent id.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData[]
   *   The meta data.
   */
  public function loadChildren(string $parentId): array;

  /**
   * Set the vocabulary to be used.
   *
   * @param string $vocabularyId
   *   Id of the vocabulary.
   */
  public function setVocabulary(string $vocabularyId);

}
