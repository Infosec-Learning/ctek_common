<?php

namespace Drupal\ctek_common\Block;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for block_enhancer plugins.
 */
abstract class BlockEnhancerPluginBase extends PluginBase implements BlockEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function enhanceBlock(array &$vars) {}

}
