<?php

namespace Drupal\ctek_common\Block;

/**
 * Interface for block_enhancer plugins.
 */
interface BlockEnhancerInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label() : string;

  public function enhanceBlock(array &$vars) : void;

}
