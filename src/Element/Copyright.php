<?php

namespace Drupal\ctek_common\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\ctek_common\Cache\AnnualCacheExpirationTrait;

/**
 * @RenderElement("copyright")
 */
class Copyright extends RenderElement {
  use AnnualCacheExpirationTrait;

  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderCopyright'],
      ],
    ];
  }

  public static function preRenderCopyright($element) {
    $now = new \DateTime();
    $element['#markup'] = "&copy; {$now->format('Y')} {$element['#name']}";
    $element['#cache']['max-age'] = static::getCacheMaxAge();
    return $element;
  }

}
