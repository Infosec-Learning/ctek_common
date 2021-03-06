<?php

namespace Drupal\ctek_common\Cache;

trait AnnualCacheExpirationTrait {

  public static function getCacheMaxAge() : int {
    $now = new \DateTime();
    $year = intval($now->format('Y'));
    $exp = new \DateTime('1/1/' . ($year + 1));
    return $exp->getTimestamp() - $now->getTimestamp();
  }

}
