<?php

namespace Drupal\ctek_common\Cache;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Why should only HTML and JSON responses get a cacheable version?
 *
 * @package Drupal\umass_common\Cache
 */
class CacheableXmlResponse extends Response implements CacheableResponseInterface {

  use CacheableResponseTrait;

  public function __construct($content = '', int $status = 200, array $headers = []) {
    parent::__construct($content, $status, $headers);
    $this->headers->set('Content-Type', 'application/xml');
  }

}
