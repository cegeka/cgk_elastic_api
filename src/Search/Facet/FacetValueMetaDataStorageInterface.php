<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

/**
 * Interface for facet value meta data storage.
 */
interface FacetValueMetaDataStorageInterface {

  /**
   * Load facet value meta data.
   *
   * @param string $id
   *   The facet value id.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData|null
   *   The facet value meta data.
   */
  public function load(string $id): ?FacetValueMetaData;

}
