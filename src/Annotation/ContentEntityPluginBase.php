<?php

namespace Drupal\ctek_common\Annotation;

use Drupal\Component\Annotation\Plugin;

abstract class ContentEntityPluginBase extends Plugin {

  public $id;
  public $entityType;
  public $bundle;

}