<?php

namespace Drupal\ctek_common\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;

/**
 * Class CacheBackendFactory
 *
 * Chooses the appropriate cache backend. During installation, cache backends
 * provided by modules like memcache aren't available, and if set in
 * settings.php will cause the installation to fail. Fall back to database
 * backend if we're in the middle of an installation.
 *
 * @package Drupal\ctek_common\Cache
 */
class CacheBackendFactory implements CacheFactoryInterface {

  protected $factory;

  /**
   * Constructs the DatabaseBackendFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   * @param \Drupal\Core\Site\Settings $settings
   *   (optional) The site settings.
   *
   * @throws \BadMethodCallException
   */
  public function __construct() {
    $this->factory = drupal_installation_attempted()
      ?
      \Drupal::service('cache.backend.database')
      :
      \Drupal::service('cache.backend.memcache');
  }

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\DatabaseBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    return $this->factory->get($bin);
  }

}
