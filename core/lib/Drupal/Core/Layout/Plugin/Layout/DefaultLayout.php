<?php

namespace Drupal\Core\Layout\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides a default layout with no markup.
 *
 * @Layout(
 *   id = "layout_default",
 *   label = @Translation("Default"),
 *   category = @Translation("Columns: 1"),
 *   regions = {
 *     "content" = {
 *       "label" = @Translation("Content")
 *     },
 *   },
 * )
 */
class DefaultLayout extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    // Remove the theme hook so no additional markup is added.
    unset($build['#theme']);
    return $build;
  }

}
