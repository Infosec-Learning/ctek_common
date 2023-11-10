<?php

namespace Drupal\ctek_common\TwigExtension;

use Drupal\Component\Utility\Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UniqueId extends AbstractExtension {

  public function getName() {
    return 'ctek_common.unique_id';
  }

  public function getFunctions() {
    return [
      new TwigFunction('getUniqueId', [$this, 'getUniqueId'])
    ];
  }

  public function getUniqueId(string $id) : string {
    return Html::getUniqueId($id);
  }

}
