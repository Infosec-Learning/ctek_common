<?php

namespace Drupal\ctek_common\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class SiteSection extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label.
   *
   * @var string
   */
  public $label;

}
