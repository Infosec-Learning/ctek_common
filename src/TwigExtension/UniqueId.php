<?php

namespace Drupal\ctek_common\TwigExtension;

use Drupal\Component\Utility\Html;

class UniqueId extends \Twig_Extension {

  public function getName() {
    return 'ctek_common.unique_id';
  }

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('getUniqueId', [$this, 'getUniqueId'])
    ];
  }

  public function getUniqueId(string $id) : string {
    return Html::getUniqueId($id);
  }

}
