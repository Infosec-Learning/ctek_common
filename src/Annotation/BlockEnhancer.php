<?php

namespace Drupal\ctek_common\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines block_enhancer annotation object.
 *
 * @Annotation
 */
class BlockEnhancer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $blockId;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
