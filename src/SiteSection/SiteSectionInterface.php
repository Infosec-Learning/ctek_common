<?php

namespace Drupal\ctek_common\SiteSection;

use Drupal\Core\Cache\CacheableDependencyInterface;

interface SiteSectionInterface extends CacheableDependencyInterface {

  public function evaluate();

}
