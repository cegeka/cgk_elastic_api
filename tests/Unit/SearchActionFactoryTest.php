<?php

namespace Drupal\Test\cgk_elastic_api\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction;
use Drupal\cgk_elastic_api\Search\FacetedSearchAction;
use Drupal\cgk_elastic_api\Search\SearchActionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Test case for Drupal\cgk_elastic_api\Search\SearchActionFactory.
 *
 * @coversDefaultClass \Drupal\cgk_elastic_api\Search\SearchActionFactory
 */
class SearchActionFactoryTest extends TestCase {

  /**
   * The factory to test.
   *
   * @var SearchActionFactory
   */
  private $factory;

  /**
   * Available facets.
   *
   * @var string[]
   */
  private $facets = ['sector', 'type_tegemoetkoming'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $entityTypeManagerMock = $this
      ->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->factory = new SearchActionFactory(10, $entityTypeManagerMock);
  }

  public function testBuildFromQuery() {
    $query = new ParameterBag(
      [
        'sector' => [
          '10',
          '11',
          '20',
        ],
        'foo' => [
          '30',
          '33',
        ],
        'from' => 30,
      ]
    );

    $expectedAction = new FacetedKeywordSearchAction(
      10,
      NULL,
      (new FacetCollection())
        ->with(
          'sector',
          new FacetValuesCollection(
            new FlatFacetValue(10),
            new FlatFacetValue(11),
            new FlatFacetValue(20)
          )
        ),
      $this->facets
    );

    $action = $this->factory->searchActionFromQuery($query, $this->facets, TRUE);

    $this->assertEquals($expectedAction, $action);
  }

}
