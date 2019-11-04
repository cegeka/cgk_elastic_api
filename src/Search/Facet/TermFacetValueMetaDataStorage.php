<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Facet value meta data storage implementation on top of Drupal terms.
 */
class TermFacetValueMetaDataStorage implements FacetValueMetaDataStorageInterface {

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * TermFacetStorage constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $id): ?FacetValueMetaData {
    $term = $this->termStorage->load($id);

    if (!$term) {
      return NULL;
    }

    $metaData = $this->metaDataFromTerm($term);

    return $metaData;
  }

  /**
   * Creates meta data from a term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData
   *   The meta data.
   */
  protected function metaDataFromTerm(TermInterface $term) {
    $metaData = new FacetValueMetaData($term->label(), $term);

    if (
      $term instanceof FieldableEntityInterface &&
      $term->hasField('field_image') &&
      !$term->get('field_image')->isEmpty()
    ) {
      $metaData = $metaData->withImage($term->get('field_image'));
    }

    return $metaData;
  }

}
