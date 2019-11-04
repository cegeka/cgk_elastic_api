<?php

namespace Drupal\Test\cgk_elastic_api\Unit;

use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction;
use Drupal\cgk_elastic_api\Search\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class SearchQueryBuilderTest extends TestCase {

  /**
   * Tests SearchQueryBuilder::buildKeywordQuery().
   *
   * @covers \Drupal\cgk_elastic_api\Search\SearchQueryBuilder::buildKeywordQuery
   */
  public function testBuildQuery() {
    $searchAction = new FacetedKeywordSearchAction(
      10,
      'foo',
      (new FacetCollection())
        ->with(
          'sector',
          new FacetValuesCollection(
            new FlatFacetValue(10),
            new FlatFacetValue(12),
            new FlatFacetValue(14)
          )
        )
        ->with(
          'omvang_bedrijf',
          new FacetValuesCollection(
            new FlatFacetValue(40)
          )
        ),
      ['sector', 'omvang_bedrijf']
    );

    $queryBuilder = new SearchQueryBuilder();

    $expectedQuery = [
      'keyword' => 'foo',
      'sector' => [
        10,
        12,
        14,
      ],
      'omvang_bedrijf' => [
        40,
      ],
    ];

    $query = $queryBuilder->buildKeywordQuery($searchAction);
    $this->assertEquals($expectedQuery, $query);
  }

}
