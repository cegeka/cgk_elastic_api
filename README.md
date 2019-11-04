# Cegeka Elastic search API

## About

This module provides a way to easily created faceted search pages. Depends on search_api and elasticsearch_connector to index content.

Features:
- ajax powered faceted search
- synonyms
- autocompletion
- search suggestions

## Installation

Add both this module and the block ui library to your project's composer.json, which is a dependency of this module:
```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/cegeka/cgk_elastic_api.git"
    },
    {
      "type": "package",
      "package": {
        "name": "library-blockui/blockui",
        "version": "v2.70",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/malsup/blockui/archive/2.70.zip",
          "type": "zip"
        }
      }
    }
  ]
```
And install it as usual: `composer require drupal/cgk_elastic_api`

## Usage

### Routes & Controllers

A search results page typically needs two routes/callbacks:
- search callback to render the search page on page loads
- filter callback to update the page using ajax

See `cgk_elastic_api.routing.yml.example` for more info

For "default" search pages the `src/Controller/SearchController.php` can be extended and will provide most features. This can keep custom controllers relatively simple:
```
class MyCustomController extends SearchController {

  public function __construct(RendererInterface $renderer, CurrentRouteMatch $routeMatch, SearchActionFactory $searchActionFactory, ElasticSearchParamsBuilder $searchParamsBuilder, ElasticSearchResultParser $resultParser, SuggesterInterface $suggester, SearchRepository $searchRepository, EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack) {
    parent::__construct($renderer, $routeMatch, $searchActionFactory, $searchParamsBuilder, $resultParser, $suggester, $searchRepository, $entityTypeManager);

    $this->facets = ['my-custom-facet'];
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('renderer'), $container->get('current_route_match'), $container->get('cgk_elastic_api.search_action_factory'), $container->get('cgk_elastic_api.elasticsearch_params_builder'), $container->get('cgk_elastic_api.elasticsearch_result_parser'), $container->get('cgk_elastic_api.suggest.title_suggester'), $container->get('cgk_elastic_api.search_repository'), $container->get('entity_type.manager'), $container->get('request_stack'));
  }

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
      '#route_name' => 'my_module.my_search_route',
    ];
  }

  protected function getFilterLink() {
    return Url::fromRoute('my_module.my_filter_route')->toString();
  }

  protected function getSuggestions(ParameterBag $query) {
    return [];
  }

  protected function getSearchHeader() {
    return [
      '#type' => 'container',
      '#children' => $this->formBuilder()->getForm(SearchForm::class),
    ];
  }

}
```

### Facets

Creating and using facets requires the following:
- an instance of `Drupal\cgk_elastic_api\Search\Facet\Control\CompositeFacetControlInterface` or `Drupal\cgk_elastic_api\Search\Facet\Control\FacetControlInterface`
- a service tagged in the following format `cgk_elastic_api.facet_control.my_facet`
- adding the facet to the constructor of the controller (see above)

Example of term-based facet:
```
class RegionFacetControl extends TermFacetBase {

  use StringTranslationTrait;

  const VOCABULARY_ID = 'MY_VOCABULARY';
  const PROPERTY_PATH = 'SEARCH_API_PROPERTY_PATH';

  public function __construct(FacetValueMetaDataTreeStorageInterface $facetValueMetaDataTreeStorage, string $routeName, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($facetValueMetaDataTreeStorage, $routeName, $entityTypeManager);

    $this->setVocabulary(self::VOCABULARY_ID);
    $this->setfacetValuesSortMethod(self::SORT_TERM_WEIGHT);
    $this->setCanSelectMultiple(FALSE);
  }

  public function getFieldName(): string {
    return self::PROPERTY_PATH;
  }

  public function addToAggregations(): bool {
    return TRUE;
  }

  protected function getFacetTitle() {
    return sprintf('<h2>%s</h2>', $this->t('MY_FACET_TITLE'));
  }
```

### ParamsBuilder


### Sync service
