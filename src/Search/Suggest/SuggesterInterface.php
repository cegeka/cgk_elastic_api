<?php

namespace Drupal\cgk_elastic_api\Search\Suggest;

/**
 * Interface SuggesterInterface.
 */
interface SuggesterInterface {

  /**
   * Make suggestions based on the given text.
   *
   * @param string $text
   *   The text to make suggestions for.
   *
   * @return array
   *   The array with suggestions.
   */
  public function suggest(string $text);

}
