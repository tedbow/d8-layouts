<?php

namespace Drupal\Core\Entity\Display;

use Drupal\Core\Layout\LayoutInterface;

/**
 * Provides a common interface for entity displays that have layout.
 */
interface EntityDisplayWithLayoutInterface extends EntityDisplayInterface {

  /**
   * Gets the layout plugin ID for this display.
   *
   * @return string
   *   The layout plugin ID.
   */
  public function getLayoutId();

  /**
   * Gets the layout plugin settings for this display.
   *
   * @return mixed[]
   *   The layout plugin settings.
   */
  public function getLayoutSettings();

  /**
   * Sets the layout for this display from a given ID and optional settings.
   *
   * @param string $layout_id
   *   A layout plugin ID.
   * @param array $layout_settings
   *   (optional) An array of settings for this layout.
   *
   * @return $this
   */
  public function setLayoutFromId($layout_id, array $layout_settings = []);

  /**
   * Sets the layout for this display.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   A layout plugin.
   *
   * @return $this
   */
  public function setLayout(LayoutInterface $layout);

  /**
   * Gets the layout plugin for this display.
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   *   The layout plugin.
   */
  public function getLayout();

}
