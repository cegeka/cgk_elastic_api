<?php

namespace Drupal\cgk_elastic_api\Search\Facet;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Meta data of a facet value.
 */
class FacetValueMetaData {

  /**
   * The label.
   *
   * @var string
   */
  private $label;

  /**
   * The image.
   *
   * @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList
   */
  private $image;

  /**
   * Original object.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  private $object;

  /**
   * Get the label of the facet.
   *
   * @return string
   *   The label.
   */
  public function label(): string {
    return $this->label;
  }

  /**
   * Get the image of the facet.
   *
   * @return \Drupal\file\Plugin\Field\FieldType\FileFieldItemList|null
   *   The image.
   */
  public function image(): ?FileFieldItemList {
    return $this->image;
  }

  /**
   * FacetMetaData constructor.
   *
   * @param string $label
   *   The label.
   * @param \Drupal\Core\Entity\EntityInterface $object
   *   Original object.
   */
  public function __construct(string $label, EntityInterface $object = NULL) {
    $this->label = $label;
    $this->object = $object;
  }

  /**
   * Create a copy of the meta data with the image added.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $image
   *   The image.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData
   *   The new meta data.
   */
  public function withImage(FileFieldItemList $image) {
    $c = clone $this;

    $c->image = $image;

    return $c;
  }

  /**
   * Create a copy of the meta data with the label replaced.
   *
   * @param string $label
   *   The new label.
   *
   * @return \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData
   *   The new meta data.
   */
  public function withLabel(string $label) {
    $c = clone $this;

    $c->label = $label;

    return $c;
  }

  /**
   * Get the original object.
   *
   * @return null|\Drupal\Core\Entity\EntityInterface
   *   The original object if available.
   */
  public function getOriginalObject(): ?EntityInterface {
    return $this->object;
  }

}
