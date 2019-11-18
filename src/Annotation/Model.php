<?php

namespace Drupal\ctek_common\Annotation;

/**
 * Defines model annotation object.
 *
 * @Annotation
 */
class Model extends ContentEntityPluginBase {

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

  public $weight;

}
