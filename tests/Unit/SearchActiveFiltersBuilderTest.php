<?php

namespace Drupal\Test\cgk_elastic_api\Unit;

use Drupal\Core\Url;
use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaData;
use Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\FacetedSearchActiveFiltersBuilder;
use Drupal\cgk_elastic_api\Search\FacetedSearchAction;
use Drupal\cgk_elastic_api\Search\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class SearchActiveFiltersBuilderTest extends TestCase {

  /**
   * The term storage mock.
   *
   * @var \Drupal\cgk_elastic_api\Search\Facet\FacetValueMetaDataStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $metaDataStorage;

  /**
   * The active filter builder service.
   *
   * @var \Drupal\cgk_elastic_api\Search\FacetedSearchActiveFiltersBuilder
   */
  private $activeFiltersBuilder;

  /**
   * The route name.
   *
   * @var string
   */
  private $route;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // We can not properly mock the interface because it uses return type
    // hinting, for which support is only added in PHPUnit 5.x.
    $this->metaDataStorage = new MockMetaDataStorage(
      [
        10 => new FacetValueMetaData('Industrie'),
        12 => new FacetValueMetaData('Diensten'),
        14 => new FacetValueMetaData('Handel'),
        40 => new FacetValueMetaData('< 50 werknemers'),
      ]
    );
    $this->route = 'cgk_maatregel.search';
    $this->activeFiltersBuilder = new FacetedSearchActiveFiltersBuilder(
      $this->route,
      $this->metaDataStorage,
      new SearchQueryBuilder()
    );
  }

  /**
   * Tests FacetedSearchActiveFiltersBuilder::build() with facets.
   *
   * @covers \Drupal\cgk_elastic_api\Search\FacetedSearchActiveFiltersBuilder::build
   */
  public function testBuildWithFacets() {
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

    $expectedBuild = [
      '#type' => 'container',
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => 'Gekozen filters:',
        '#attributes' => [
          'class' => 'u-hidden-mobile',
        ],
      ],
      'items' => [
        [
          '#title' => 'Industrie',
          '#type' => 'link',
          '#url' => new Url(
            $this->route,
            [],
            [
              'query' => [
                'sector' => [12, 14],
                'omvang_bedrijf' => [40],
              ],
            ]
          ),
          '#attributes' => [
            'class' => [
              'u-hidden-mobile',
              'active-filters-filter',
            ],
            'title' => 'Filter Industrie verwijderen',
            'aria-label' => 'Industrie',
            'data-drupal-facet-item-value' => 10,
            'data-drupal-facet-item-facet' => 'sector'
          ],
        ],
        [
          '#title' => 'Diensten',
          '#type' => 'link',
          '#url' => new Url(
            $this->route,
            [],
            [
              'query' => [
                'sector' => [10, 14],
                'omvang_bedrijf' => [40],
              ],
            ]
          ),
          '#attributes' => [
            'class' => [
              'u-hidden-mobile',
              'active-filters-filter',
            ],
            'title' => 'Filter Diensten verwijderen',
            'aria-label' => 'Diensten',
            'data-drupal-facet-item-value' => 12,
            'data-drupal-facet-item-facet' => 'sector'
          ],
        ],
        [
          '#title' => 'Handel',
          '#type' => 'link',
          '#url' => new Url(
            $this->route,
            [],
            [
              'query' => [
                'sector' => [10, 12],
                'omvang_bedrijf' => [40],
              ],
            ]
          ),
          '#attributes' => [
            'class' => [
              'u-hidden-mobile',
              'active-filters-filter',
            ],
            'title' => 'Filter Handel verwijderen',
            'aria-label' => 'Handel',
            'data-drupal-facet-item-value' => 14,
            'data-drupal-facet-item-facet' => 'sector'
          ],
        ],
        [
          '#title' => '< 50 werknemers',
          '#type' => 'link',
          '#url' => new Url(
            $this->route,
            [],
            [
              'query' => [
                'sector' => [10, 12, 14],
              ],
            ]
          ),
          '#attributes' => [
            'class' => [
              'u-hidden-mobile',
              'active-filters-filter',
            ],
            'title' => 'Filter < 50 werknemers verwijderen',
            'aria-label' => '< 50 werknemers',
            'data-drupal-facet-item-value' => 40,
            'data-drupal-facet-item-facet' => 'omvang_bedrijf'
          ],
        ],
      ],
      '#attributes' => [
        'class' => [
          'active-filters',
        ],
      ],
      'remove-all' => [
        '#type' => 'link',
        '#url' => new Url($this->route, [], ['query' => []]),
        '#attributes' => [
          'class' => [
            'active-filters-remove-all',
          ],
          'aria-label' => 'Alle filters verwijderen',
        ],
        '#title' => 'Alle filters verwijderen',
      ],
    ];

    $build = $this->activeFiltersBuilder->build($action);

    $this->assertEquals($expectedBuild, $build);
  }

  /**
   * Tests SearchActiveFilters::build() without facets.
   *
   * @covers \Drupal\cgk_elastic_api\Search\FacetedSearchActiveFiltersBuilder::build
   */
  public function testBuildWithoutFacets() {
    $action = new FacetedSearchAction(
      10,
      NULL,
      ['sector', 'omvang_bedrijf']
    );

    $build = $this->activeFiltersBuilder->build($action);

    $this->assertNull($build);
  }

}

class MockMetaDataStorage implements FacetValueMetaDataStorageInterface {

  /**
   * @var FacetValueMetaData[]
   */
  private $values;

  public function __construct(array $values) {
    $this->values = $values;
  }

  public function load(string $id): ?FacetValueMetaData {
    if (isset($this->values[$id])) {
      return $this->values[$id];
    }

    return NULL;
  }

}
