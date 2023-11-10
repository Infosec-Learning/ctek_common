<?php

namespace Drupal\ctek_common\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Injection extends AbstractExtension {

  public function getName() {
    return 'ctek_common.injection';
  }

  public function getFilters() {
    return [
      new TwigFilter('inject', [$this, 'inject']),
      new TwigFilter('bare', [$this, 'bare']),
    ];
  }

  public function inject(array $renderArray, string $key, string $value) : array {
    $renderArray['#' . $key] = $value;
    return $renderArray;
  }

  public function bare($renderArray) : ?array {
    if (!is_array($renderArray)) {
      return NULL;
    }
    $renderArray['#tag'] = '';
    $renderArray['#wrapper'] = '';
    return $renderArray;
  }

}
