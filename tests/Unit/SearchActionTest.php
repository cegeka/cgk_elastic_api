<?php

namespace Drupal\Test\cgk_elastic_api\Unit;

use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\FacetedSearchAction;
use PHPUnit\Framework\TestCase;

class SearchActionTest extends TestCase {

  public function testMorePagesData() {
    $this->assertTrue(TRUE);
  }

  /**
   * Data provider for testHasMorePages().
   */
  public function hasMorePagesData() {
    $data = [];

    $data['first page 11 results'] = [
      'action' => new FacetedSearchAction(10),
      'total_results' => 11,
      'has_more' => TRUE,
    ];

    $data['second page 20 results'] = [
      'action' => (new FacetedSearchAction(10))->from(10),
      'total_results' => 20,
      'has_more' => FALSE,
    ];

    return $data;
  }

  /**
   * Tests SearchAction::hasMorePages().
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchAction $action
   *   The search action.
   * @param int $total_results
   *   Total search results.
   * @param bool $hasMore
   *   Expected result of hasMorePages().
   *
   * @dataProvider hasMorePagesData()
   */
  public function testHasMorePages(
    FacetedSearchAction $action,
    int $total_results,
    bool $hasMore
  ) {
    $this->assertSame($hasMore, $action->hasMorePages($total_results));
  }

  /**
   * Data provider for testNextFrom().
   */
  public function nextFromData() {
    $data = [];

    $data['first page'] = [
      'action' => new FacetedSearchAction(10),
      'next_from' => 10,
    ];

    $data['second page'] = [
      'action' => (new FacetedSearchAction(10))->from(10),
      'next_from' => 20,
    ];

    $data['page size 5, first page'] = [
      'action' => new FacetedSearchAction(5),
      'next_from' => 5,
    ];

    $data['page size 5, second page'] = [
      'action' => (new FacetedSearchAction(5))->from(5),
      'next_from' => 10,
    ];

    return $data;
  }

  /**
   * Tests SearchAction::nextFrom().
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchAction $action
   *   The search action.
   * @param bool $nextFrom
   *   Expected result of nextFrom().
   *
   * @dataProvider nextFromData().
   */
  public function testNextFrom(FacetedSearchAction $action, int $nextFrom) {
    $this->assertSame($nextFrom, $action->nextFrom());
  }

  /**
   * Data provider for testFacetValueWasChosen().
   */
  public function facetValueChosenData() {
    $data = [];

    $action = new FacetedSearchAction(
      10,
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

    $data['chosen'] = [
      'action' => $action,
      'facet' => 'sector',
      'value' => 12,
      'chosen' => TRUE,
    ];

    $data['not chosen'] = [
      'action' => $action,
      'facet' => 'omvang_bedrijf',
      'value' => 42,
      'chosen' => FALSE,
    ];

    return $data;
  }

  /**
   * Tests SearchAction::facetValueWasChosen().
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchAction $action
   *   The search action.
   * @param string $facet
   *   The facet id.
   * @param int $value
   *   The facet value.
   * @param bool $chosen
   *   Expected result of facetValueWasChosen().
   *
   * @dataProvider facetValueChosenData().
   */
  public function testFacetValueWasChosen(
    FacetedSearchAction $action,
    string $facet,
    int $value,
    bool $chosen
  ) {
    $this->assertSame($chosen, $action->facetValueWasChosen($facet, $value));
  }

  /**
   * Tests SearchAction::withoutFacetValue().
   */
  public function testWithoutFacetValue() {
    $action = new FacetedSearchAction(
      10,
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

    $expectedActionWithout40 = new FacetedSearchAction(
      10,
      (new FacetCollection())
        ->with(
          'sector',
          new FacetValuesCollection(
            new FlatFacetValue(10),
            new FlatFacetValue(12),
            new FlatFacetValue(14)
          )
        ),
      ['sector', 'omvang_bedrijf']
    );
    $actionWithout40 = $action->withoutFacetValue('omvang_bedrijf', new FlatFacetValue(40));

    $this->assertEquals($expectedActionWithout40, $actionWithout40);
  }

  /**
   * Tests KeywordSearchAction::withFacetValue().
   */
  public function testWithFacetValue() {
    $action = new FacetedSearchAction(
      10,
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

    $expectedActionWith42 = new FacetedSearchAction(
      10,
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
            new FlatFacetValue(40),
            new FlatFacetValue(42)
          )
        ),
      ['sector', 'omvang_bedrijf']
    );
    $actionWith42 = $action->withFacetValue('omvang_bedrijf', new FlatFacetValue(42));

    $this->assertEquals($expectedActionWith42, $actionWith42);
  }

  /**
   * Tests KeywordSearchAction::withoutFacets().
   */
  public function testWithoutFacets() {
    $action = new FacetedSearchAction(
      10,
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

    $expectedActionWithoutFacets = new FacetedSearchAction(
      10,
      NULL,
      ['sector', 'omvang_bedrijf']
    );

    $actionWithoutFacets = $action->withoutFacets();

    $this->assertEquals($expectedActionWithoutFacets, $actionWithoutFacets);

    $this->assertTrue($actionWithoutFacets->getChosenFacetValues()->isEmpty());
  }

}
