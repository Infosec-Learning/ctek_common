<?php

namespace Drupal\ctek_common\Model;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for model plugins.
 */
interface ModelInterface {

  const ROUTE_PARAMETER = 'model';
  const CACHE_BIN = 'ctek_common.model';

  public static function getCache();

  public function getEntity() : ContentEntityInterface;

  public function getId();

  public function getBundle() : string;

  public function getEntityTypeId() : string;

  public function getCacheMaxAge() : int;

  public function getCacheContexts() : array;

  public function getCacheTags() : array;

}
