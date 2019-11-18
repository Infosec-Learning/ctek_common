<?php

namespace Drupal\ctek_common\Annotation;

use Drupal\Component\Annotation\Plugin;

abstract class ContentEntityPluginBase extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity type this plugin applies to.
   *
   * @var string
   */
  public $entityType;

  /**
   * The entity bundle this plugin applies to.
   *
   * @var string
   */
  public $bundle;

}
