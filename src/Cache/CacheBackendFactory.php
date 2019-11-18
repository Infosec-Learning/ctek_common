<?php

namespace Drupal\ctek_common\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;

/**
 * Class CacheBackendFactory
 *
 * Chooses the appropriate cache backend. During installation, cache backends
 * provided by modules like memcache aren't available, and if set in
 * settings.php will cause the installation to fail. Fall back to database
 * backend if the memcache service is unavailable.
 *
 * @package Drupal\ctek_common\Cache
 */
class CacheBackendFactory implements CacheFactoryInterface {

  protected $factory;

  public function __construct() {
    $this->factory = \Drupal::hasService('cache.backend.memcache')
      ?
      \Drupal::service('cache.backend.memcache')
      :
      \Drupal::service('cache.backend.database');
  }

  public function get($bin) : CacheBackendInterface {
    return $this->factory->get($bin);
  }

}
