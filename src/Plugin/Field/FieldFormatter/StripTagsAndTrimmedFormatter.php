<?php

namespace Drupal\cgk_elastic_api\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;

/**
 * Plugin implementation of the 'strip_tags_trimmed' formatter.
 *
 * @FieldFormatter(
 *   id = "strip_tags_trimmed",
 *   label = @Translation("Strip tags and trim"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class StripTagsAndTrimmedFormatter extends TextTrimmedFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as $index => $element) {
      $elements[$index]['#text'] = strip_tags($element['#text'], '<p>');
    }

    return $elements;
  }

}
