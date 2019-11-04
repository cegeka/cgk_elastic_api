<?php

namespace Drupal\cgk_elastic_api;

use Drupal\cgk_elastic_api\Fake\FakeClientManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class SearchServiceProvider.
 */
class SearchServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    if (!isset($modules['elasticsearch_connector'])) {
      $container->removeDefinition('cgk_elastic_api.event_subscriber.initialize_index');
      $container->setDefinition('elasticsearch_connector.client_manager', new Definition(FakeClientManager::class));

      $container->setDefinition('elasticsearch_connector.index_factory', new Definition(\stdClass::class));
    }
  }

}
