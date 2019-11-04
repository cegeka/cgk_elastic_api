<?php

namespace Drupal\cgk_elastic_api\EventSubscriber;

use Drupal\elasticsearch_connector\Event\PrepareIndexEvent;
use Drupal\elasticsearch_connector\Event\PrepareIndexMappingEvent;
use Drupal\search_api\Entity\Index;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class InitializeIndexEventSubscriber.
 */
class InitializeIndexEventSubscriber implements EventSubscriberInterface {

  /**
   * Index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  private $index;

  /**
   * InitializeIndexEventSubscriber constructor.
   */
  public function __construct(Index $index) {
    $this->index = $index;
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * ['eventName' => 'methodName']
   *  * ['eventName' => ['methodName', $priority]]
   *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
   *
   * @return array
   *   The event names to listen to
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[PrepareIndexEvent::PREPARE_INDEX] = 'prepareSearchIndex';
    $events[PrepareIndexMappingEvent::PREPARE_INDEX_MAPPING] = 'prepareMapping';

    return $events;
  }

  /**
   * Alter index config.
   *
   * @param \Drupal\elasticsearch_connector\Event\PrepareIndexEvent $event
   *   Prepare index event.
   */
  public function prepareSearchIndex(PrepareIndexEvent $event) {
    $config = $event->getIndexConfig();
    $config['body'] = [
      'analysis' => [
        'tokenizer' => [
          'ngram_tokenizer' => [
            'type' => 'ngram',
            'min_gram' => 3,
            'max_gram' => 3,
          ],
        ],
        'analyzer' => [
          'ngram_analyzer' => [
            'type' => 'custom',
            'tokenizer' => 'ngram_tokenizer',
            'filter' => [
              'lowercase',
            ],
          ],
        ],
      ],
    ];

    $event->setIndexConfig($config);
  }

  /**
   * Alter index mappings.
   *
   * @param \Drupal\elasticsearch_connector\Event\PrepareIndexMappingEvent $event
   *   Prepare index event.
   */
  public function prepareMapping(PrepareIndexMappingEvent $event) {
    $mapping = $event->getIndexMappingParams();

    $mapping['body'][$this->index->id()]['properties']['title']['fields'] = [
      'ngram' => [
        'type' => 'text',
        'analyzer' => 'ngram_analyzer',
      ],
    ];

    $event->setIndexMappingParams($mapping);
  }

}
