<?php

namespace Drupal\cgk_elastic_api\Controller;

use Drupal;
use Drupal\cgk_elastic_api\Search\ElasticSearchParamsBuilder;
use Drupal\cgk_elastic_api\Search\ElasticSearchResultParser;
use Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction;
use Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface;
use Drupal\cgk_elastic_api\Search\SearchActionFactory;
use Drupal\cgk_elastic_api\Search\SearchRepository;
use Drupal\cgk_elastic_api\Search\SearchResult;
use Drupal\cgk_elastic_api\Search\Suggest\SuggesterInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller to handle the maatregelen search.
 */
class SearchController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\\RendererInterface
   */
  protected $renderer;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatch
   */
  protected $routeMatch;

  /**
   * The Elasticsearch client.
   *
   * @var \nodespark\DESConnector\ClientInterface
   */
  protected $client;

  /**
   * The search action factory.
   *
   * @var \Drupal\cgk_elastic_api\Search\SearchActionFactory
   */
  protected $searchActionFactory;

  /**
   * The search parameters builder.
   *
   * @var \Drupal\cgk_elastic_api\Search\ElasticSearchParamsBuilder
   */
  protected $searchParamsBuilder;

  /**
   * The result parser.
   *
   * @var \Drupal\cgk_elastic_api\Search\ElasticSearchResultParser
   */
  protected $resultParser;

  /**
   * The suggester.
   *
   * @var \Drupal\cgk_elastic_api\Search\Suggest\SuggesterInterface
   */
  protected $suggester;

  /**
   * Facets.
   *
   * @var array
   */
  protected $facets;

  /**
   * Breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbManager
   */
  protected $breadCrumbManager;

  /**
   * SearchRepository.
   *
   * @var \Drupal\cgk_elastic_api\Search\SearchRepository
   */
  protected $searchRepository;

  /**
   * FacetedSearchActiveFiltersBuilder service.
   *
   * @var \Drupal\cgk_elastic_api\Search\FacetedSearchActiveFiltersBuilder
   */
  protected $activeFiltersBuilder;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * NodeViewBuilder.
   *
   * @var \Drupal\node\NodeViewBuilder
   */
  protected $nodeViewBuilder;

  /**
   * The name of the main search route of this controller.
   *
   * @var string
   */
  protected $searchRouteName;

  /**
   * The name of the ajax filter search route of this controller.
   *
   * @var string
   */
  protected $filterRouteName;

  /**
   * SearchController constructor.
   */
  public function __construct(
    RendererInterface $renderer,
    CurrentRouteMatch $routeMatch,
    SearchActionFactory $searchActionFactory,
    ElasticSearchParamsBuilder $searchParamsBuilder,
    ElasticSearchResultParser $resultParser,
    SuggesterInterface $suggester,
    SearchRepository $searchRepository,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->renderer = $renderer;
    $this->routeMatch = $routeMatch;
    $this->searchActionFactory = $searchActionFactory;
    $this->searchParamsBuilder = $searchParamsBuilder;
    $this->resultParser = $resultParser;
    $this->suggester = $suggester;
    $this->searchRepository = $searchRepository;
    $this->nodeViewBuilder = $entityTypeManager->getViewBuilder('node');

    $this->facets = [];
    $this->filterRouteName = 'cgk_elastic_api.filter';
    $this->searchRouteName = 'cgk_elastic_api.search';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('cgk_elastic_api.search_action_factory'),
      $container->get('cgk_elastic_api.elasticsearch_params_builder'),
      $container->get('cgk_elastic_api.elasticsearch_result_parser'),
      $container->get('cgk_elastic_api.suggest.title_suggester'),
      $container->get('cgk_elastic_api.search_repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Search for content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return array
   *   A render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When the HTTP request does not seem to be correct.
   */
  public function search(Request $request) {
    $query = $request->query;

    try {
      $searchAction = $this->searchActionFactory->searchActionFromQuery($query, $this->facets, $request->isXmlHttpRequest());
    }
    catch (Exception $e) {
      throw new AccessDeniedHttpException();
    }

    $result = $this->parsedResult($searchAction);
    $hits = $this->renderHits($searchAction, $result, $query);

    // Requested another page in the result set.
    if ($request->isXmlHttpRequest() && !((bool) $request->get('ajax_form'))) {
      return $hits + ['#type' => 'container'];
    }

    $facets = $this->renderFacets($searchAction, $result);

    $drupalSettings = [
      'cgk_elastic_api' => [
        'ajaxify' => [
          'filter_url' => $this->getFilterLink(),
          'facets' => $this->facets,
        ],
        'retainFilter' => $this->shouldRetainFilter(),
      ],
    ];

    return [
      '#theme' => 'cgk_elastic_api_search',
      '#header' => $this->getSearchHeader(),
      '#facets' => $facets,
      '#results' => $hits,
      '#did_you_mean' => $this->getSuggestions($query),
      '#cache' => [
        'tags' => [
          'cgk.search',
        ],
      ],
      '#attached' => [
        'library' => [
          'cgk_elastic_api/ajaxify',
          'cgk_elastic_api/blockui',
        ],
        'drupalSettings' => $drupalSettings,
      ],
    ];
  }

  /**
   * Get the link to send filter (ajax) requests to.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The link to send filter requests to.
   */
  protected function getFilterLink() {
    return Url::fromRoute($this->filterRouteName)->toString();
  }

  /**
   * Autocomplete callback for search suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A HTTP response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When the HTTP request does not seem to be correct.
   */
  public function handleAutocomplete(Request $request) {
    $searchQuery = $request->get('q');
    $results = [];

    $params = [
      'body' => [
        '_source' => 'title',
        'suggest' => [
          'search-suggest' => [
            'prefix' => $searchQuery,
            'completion' => [
              'field' => 'search_suggest',
              'size' => 10,
            ],
          ],
        ],
      ],
    ];

    $response = $this->searchRepository->query($params);

    $data = [];
    foreach ($response->getRawResponse()['suggest']['search-suggest'][0]['options'] as $suggestion) {
      $value = $suggestion['_source']['title'][0];
      $data[$value] = $value;
    }

    foreach ($data as $value => $label) {
      $results[] = [
        'value' => $value,
        'label' => $this->highlight($searchQuery, $label),
      ];
    }

    $build = [
      '#theme' => 'cgk_elastic_api_autocomplete',
      '#results' => $results,
    ];

    if ($request->get('t')) {
      $build['#layout_wide'] = FALSE;
    }

    return new Response($this->renderer->render($build));
  }

  /**
   * Highlight the search term in the target string.
   *
   * @param string $term
   *   The term that needs to be replaced.
   * @param string $target
   *   The target.
   *
   * @return string
   *   The replaced search term.
   */
  private function highlight(string $term, string $target) {
    return preg_replace('/(' . preg_quote($term) . ')/i', "<strong>$1</strong>", $target);
  }

  /**
   * Ajax callback to update search results, facets and active filters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response, containing commands to update elements on the page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When the HTTP request does not seem to be correct.
   */
  public function filter(Request $request) {
    $query = $request->request;

    try {
      $searchAction = $this->searchActionFactory->searchActionFromQuery($query, $this->facets, TRUE);
    }
    catch (Exception $e) {
      throw new AccessDeniedHttpException();
    }

    $searchResult = $this->parsedResult($searchAction);
    $facets = $this->renderFacets($searchAction, $searchResult);
    $hits = $this->renderHits($searchAction, $searchResult, $query);

    $facets = [
      '#type' => 'container',
      '#attributes' => ['class' => ['facets']],
      'facets' => $facets,
      '#attached' => [
        'drupalSettings' => [
          'cgk_elastic_api' => [
            'ajaxify' => [
              'facets' => $this->facets,
            ],
          ],
        ],
      ],
    ];

    $response = new AjaxResponse();

    // Replace suggestions.
    $response->addCommand(new RemoveCommand('.cgk-results-wrapper .col-search-content .did-you-mean'));
    $suggestions = $this->getSuggestions($query);
    if (!empty($suggestions)) {
      $response->addCommand(new PrependCommand('.cgk-results-wrapper .col-search-content .suggestion-wrapper', $this->renderer->render($suggestions)));
    }

    // Replace facets.
    $response->addCommand(new ReplaceCommand('.facets', $this->renderer->render($facets)));

    // Replace search hits.
    $response->addCommand(new RemoveCommand('.cgk-results-wrapper .col-search-content .results-wrapper > *'));
    $response->addCommand(new AppendCommand('.cgk-results-wrapper .col-search-content .results-wrapper', $this->renderer->render($hits)));

    return $response;
  }

  /**
   * Create a render array from facets.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The current search action.
   * @param \Drupal\cgk_elastic_api\Search\SearchResult $result
   *   SearchResult object parsed from the SearchAction.
   *
   * @return array
   *   Render array of facets.
   */
  private function renderFacets(FacetedSearchActionInterface $searchAction, SearchResult $result) {
    // Facets as lists of checkboxes.
    $facets = array_map(
      function ($facet) use ($searchAction, $result) {
        // This \Drupal call is hard to avoid as facets are added
        // semi-dynamically.
        // @codingStandardsIgnoreLine
        $renderedFacet = Drupal::service('cgk_elastic_api.facet_control.' . $facet)->build($facet, $searchAction, $result);
        if (!empty($renderedFacet)) {
          return $renderedFacet;
        }

        return FALSE;
      },
      $searchAction->getAvailableFacets()
    );

    return array_filter($facets);
  }

  /**
   * Render search results.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedSearchActionInterface $searchAction
   *   The current search action.
   * @param \Drupal\cgk_elastic_api\Search\SearchResult $result
   *   SearchResult.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   Query parameters.
   * @param string $view_mode
   *   The view mode to render search results in. Defaults to 'search_index'.
   *
   * @return array
   *   Render array of search results.
   */
  protected function renderHits(FacetedSearchActionInterface $searchAction, SearchResult $result, ParameterBag $query, $view_mode = 'search_index') {
    $hits = $this->searchRepository->getItemValueFromHits($result->getHits());
    $hits = $this->nodeViewBuilder->viewMultiple($hits, $view_mode);

    $start = $searchAction->getFrom() + 1;
    $end = $searchAction->getSize() + $searchAction->getFrom();
    $total = $result->getTotal();

    if ($end > $total) {
      $end = $total;
    }

    if ($total > $searchAction->getSize()) {
      $hits['summary'] = [
        '#type' => 'markup',
        '#prefix' => '<div class="pager-summary">',
        '#markup' => $this->t('<strong>@start - @end</strong> of @total results', [
          '@start' => $start,
          '@end' => $end,
          '@total' => $total,
        ]),
        '#suffix' => '</div>',
      ];

      $hits['more'] = $this->renderPager($query, $total, $searchAction->getSize());
    }

    return $hits;
  }

  /**
   * Get a render array representing the pager.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   Parameter bag.
   * @param int $total
   *   Total amount of results.
   * @param int $size
   *   Size of the result set.
   *
   * @return array
   *   Pager render array.
   */
  protected function renderPager(ParameterBag $query, int $total, int $size) {
    return [
      '#theme' => 'cgk_pager',
      '#tags' => [
        1 => 'Previous',
        3 => 'Next',
      ],
      '#element' => 0,
      '#parameters' => $query->all(),
      '#total_items' => $total,
      '#items_per_page' => $size,
      '#route_name' => $this->searchRouteName,
    ];
  }

  /**
   * Parses a SearchAction into a SearchResult object.
   *
   * @param \Drupal\cgk_elastic_api\Search\FacetedKeywordSearchAction $searchAction
   *   The current search action.
   *
   * @return \Drupal\cgk_elastic_api\Search\SearchResult
   *   SearchResult object parsed from the SearchAction
   */
  private function parsedResult(FacetedKeywordSearchAction $searchAction) {
    $params = $this->searchParamsBuilder->build($searchAction);
    $response = $this->searchRepository->query($params);

    return $this->resultParser->parse($searchAction, $response->getRawResponse());
  }

  /**
   * Get a list of suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   Query.
   *
   * @return array
   *   List of suggestions
   */
  protected function getSuggestions(ParameterBag $query) {
    $did_you_mean = [];
    $keyword = $query->get('keyword');
    if (empty(trim($keyword))) {
      return [];
    }

    $suggestions = $this->suggester->suggest($keyword);
    if (empty($suggestions)) {
      return [];
    }

    foreach ($suggestions as $suggestion) {
      $did_you_mean[] = [
        '#type' => 'link',
        '#url' => Url::fromRoute($this->searchRouteName, [], ['query' => ['keyword' => $suggestion]]),
        '#title' => $suggestion,
      ];
    }

    return [
      '#theme' => 'cgk_elastic_api_suggestions',
      '#suggestions' => $did_you_mean,
    ];
  }

  /**
   * Get a header to show above the search.
   *
   * @return array|null
   *   Render array to print the header, or NULL if no header.
   */
  protected function getSearchHeader() {
    return NULL;
  }

  /**
   * Define whether the facets should reset after searching for a new keyword.
   *
   * @return bool
   *   FALSE if the filter should be retained, TRUE if not.
   */
  protected function shouldRetainFilter() {
    return FALSE;
  }

}
