<?php

namespace Drupal\Test\cgk_elastic_api\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\cgk_elastic_api\Search\ElasticSearchParamsBuilder;
use Drupal\cgk_elastic_api\Search\Facet\Control\FacetControlInterface;
use Drupal\cgk_elastic_api\Search\Facet\FacetCollection;
use Drupal\cgk_elastic_api\Search\Facet\FacetValuesCollection;
use Drupal\cgk_elastic_api\Search\Facet\FlatFacetValue;
use Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction;
use Drupal\cgk_elastic_api\Search\IndexFactoryAdapter;
use Drupal\search_api\Entity\Index;
use PHPUnit\Framework\TestCase;

/**
 * Test case for \Drupal\cgk_elastic_api\Search\ElasticSearchParamsBuilder.
 *
 * @coversDefaultClass \Drupal\cgk_elastic_api\Search\ElasticSearchParamsBuilder
 */
class ElasticSearchParamsBuilderTest extends TestCase {

  /**
   * @var \Drupal\cgk_elastic_api\Search\ElasticSearchParamsBuilder
   */
  private $paramsBuilder;

  /**
   * Tests the build() method.
   */
  protected function setUp() {
    parent::setUp();

    $languageMock = $this->getMockBuilder(Language::class)
      ->disableOriginalConstructor()
      ->getMock();
    $languageMock->method('getId')->willReturn('nl');

    $indexMock = $this
      ->getMockBuilder(Index::class)
      ->disableOriginalConstructor()
      ->getMock();

    $languageManagerMock = $this->getMockBuilder(LanguageManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $languageManagerMock->method('getCurrentLanguage')
      ->willReturn($languageMock);

    $indexFactoryAdapterMock = $this->getMockBuilder(IndexFactoryAdapter::class)
      ->disableOriginalConstructor()
      ->getMock();
    $indexFactoryAdapterMock->method('getIndexName')->willReturn('bar');

    $this->paramsBuilder = new ElasticSearchParamsBuilder($indexMock, $languageManagerMock, $indexFactoryAdapterMock);

    $container = new ContainerBuilder();
    $container->set('cgk_elastic_api.facet_control.sector', $this->getFacetControlMock('sector'));
    $container->set('cgk_elastic_api.facet_control.omvang_bedrijf', $this->getFacetControlMock('omvang_bedrijf'));
    $container->set('cgk_elastic_api.facet_control.type_tegemoetkoming', $this->getFacetControlMock('type_tegemoetkoming'));

    \Drupal::setContainer($container);
  }

  /**
   * Tests the build() method.
   */
  public function testBuild() {
    $searchAction = new FacetedKeywordSearchAction(5,
      'foo',
      (new FacetCollection())
        ->with(
          'sector',
          new FacetValuesCollection(
            new FlatFacetValue(15),
            new FlatFacetValue(17)
          )
        )
        ->with(
          'omvang_bedrijf',
          new FacetValuesCollection(
            new FlatFacetValue(20),
            new FlatFacetValue(24)
          )
        ),
      ['sector', 'type_tegemoetkoming', 'omvang_bedrijf']);

    $expectedParams = [
      'body' => [
        '_source' => FALSE,
        'from' => 0,
        'size' => 5,
        'query' => [
          'bool' => [
            'must' => [
              [
                'term' => [
                  'langcode' => 'nl',
                ],
              ],
              [
                'term' => [
                  'status' => 1,
                ],
              ],
              [
                [
                  'function_score' => [
                    'functions' => [
                      [
                        'filter' => [
                          'multi_match' => [
                            'query' => 'foo',
                            'fields' => [
                              'title^5',
                              'title.ngram',
                              'body^5',
                              'body.ngram',
                            ],
                          ],
                        ],
                        'weight' => 2,
                      ],
                    ],
                    'score_mode' => 'multiply',
                  ],
                ],
                [
                  'bool' => [
                    'should' => [
                      'match' => [
                        'custom_all' => 'foo',
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        'aggs' => [
          'sector' => [
            'filter' => [
              'bool' => [
                'must' => [
                  [
                    'bool' => [
                      'should' => [
                        [
                          'term' => [
                            'field_omvang_bedrijf' => "20",
                          ],
                        ],
                        [
                          'term' => [
                            'field_omvang_bedrijf' => "24",
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'aggs' => [
              'filtered' => [
                'terms' => [
                  'field' => 'field_sector',
                  'size' => 999,
                ],
              ],
            ],
          ],
          'type_tegemoetkoming' => [
            'filter' => [
              'bool' => [
                'must' => [
                  [
                    'bool' => [
                      'should' => [
                        [
                          'term' => [
                            'field_sector' => "15",
                          ],
                        ],
                        [
                          'term' => [
                            'field_sector' => "17",
                          ],
                        ],
                      ],
                    ],
                  ],
                  [
                    'bool' => [
                      'should' => [
                        [
                          'term' => [
                            'field_omvang_bedrijf' => "20",
                          ],
                        ],
                        [
                          'term' => [
                            'field_omvang_bedrijf' => "24",
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'aggs' => [
              'filtered' => [
                'terms' => [
                  'field' => 'field_type_tegemoetkoming',
                  'size' => 999,
                ],
              ],
            ],
          ],
          'omvang_bedrijf' => [
            'filter' => [
              'bool' => [
                'must' => [
                  [
                    'bool' => [
                      'should' => [
                        [
                          'term' => [
                            'field_sector' => "15",
                          ],
                        ],
                        [
                          'term' => [
                            'field_sector' => "17",
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'aggs' => [
              'filtered' => [
                'terms' => [
                  'field' => 'field_omvang_bedrijf',
                  'size' => 999,
                ],
              ],
            ],
          ],
        ],
        'post_filter' => [
          'bool' => [
            'must' => [
              [
                'bool' => [
                  'should' => [
                    [
                      'term' => [
                        'field_sector' => "15",
                      ],
                    ],
                    [
                      'term' => [
                        'field_sector' => "17",
                      ],
                    ],
                  ],
                ],
              ],
              [
                'bool' => [
                  'should' => [
                    [
                      'term' => [
                        'field_omvang_bedrijf' => "20",
                      ],
                    ],
                    [
                      'term' => [
                        'field_omvang_bedrijf' => "24",
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      'index' => 'bar',
    ];

    $this->assertEquals($expectedParams, $this->paramsBuilder->build($searchAction));
  }

  /**
   * Helper function to get a mock for a facet control.
   *
   * @param string $facetId
   *   Facet id to get a mock for.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The facet control mock.
   */
  private function getFacetControlMock(string $facetId) {
    $mock = $this->getMockBuilder(FacetControlInterface::class)->getMock();
    $mock->method('addToAggregations')->willReturn(TRUE);
    $mock->method('getFieldName')->willReturn("field_$facetId");

    return $mock;
  }

}
