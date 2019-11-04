<?php

namespace Drupal\Test\cgk_elastic_api\Unit;

use Drupal\cgk_elastic_api\Search\Facet\ChildOf;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\Facet\HierarchicalFacetValue;
use PHPUnit\Framework\TestCase;

class ChildOfTest extends TestCase {

  /**
   * The facet values collection.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection
   */
  private $collection;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $values = [
      new HierarchicalFacetValue(1, 2, 3),
      new HierarchicalFacetValue(1, 2, 4),
      new HierarchicalFacetValue(10, 11, 12),
      new HierarchicalFacetValue(10, 11, 13),
      new HierarchicalFacetValue(20, 21),
      new HierarchicalFacetValue(20, 22),
      new FlatFacetValue(1),
    ];

    $this->collection = new FacetValuesCollection(...$values);
  }

  /**
   * Data provider for testChildOf().
   */
  public function childOfDataProvider() {
    $data = [];

    $data[] = [
      'parent' => new HierarchicalFacetValue(1, 2),
      'values' => new FacetValuesCollection(
        new HierarchicalFacetValue(1, 2, 3),
        new HierarchicalFacetValue(1, 2, 4)
      ),
    ];

    $data[] = [
      'parent' => new HierarchicalFacetValue(10, 11),
      'values' => new FacetValuesCollection(
        new HierarchicalFacetValue(10, 11, 12),
        new HierarchicalFacetValue(10, 11, 13)
      ),
    ];

    $data[] = [
      'parent' => new HierarchicalFacetValue(1),
      'values' => new FacetValuesCollection(),
    ];

    return $data;
  }

  /**
   * Tests that the right elements are filtered when using ChildOf().
   *
   * @dataProvider childOfDataProvider
   */
  public function testChildOf(FacetValueInterface $parent, FacetValuesCollection $values) {
    $this->assertEquals($values, $this->collection->filter(new ChildOf($parent)));
  }

}
