<?php

namespace Drupal\cgk_pager\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\SqlBase;

/**
 * The custom plugin to handle full and mobile pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "cgk_pager",
 *   title = @Translation("Custom Cgk pager"),
 *   short_title = @Translation("Cgk"),
 *   help = @Translation("Custom Cgk pager"),
 *   theme = "cgk_pager",
 *   register_theme = FALSE
 * )
 */
class CgkPager extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], '@count item, skip @skip', 'Paged, @count items, skip @skip', [
        '@count' => $this->options['items_per_page'],
        '@skip' => $this->options['offset'],
      ]);
    }

    return $this->formatPlural($this->options['items_per_page'], '@count item', 'Paged, @count items', ['@count' => $this->options['items_per_page']]);
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {

    $tags = [
      1 => $this->options['tags']['previous'],
      3 => $this->options['tags']['next'],
    ];

    return [
      '#theme' => $this->themeFunctions(),
      '#tags' => $tags,
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#total_items' => $this->getTotalItems(),
      '#items_per_page' => $this->getItemsPerPage(),
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
    ];
  }

}
