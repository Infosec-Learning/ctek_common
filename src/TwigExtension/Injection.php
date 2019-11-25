<?php

namespace Drupal\ctek_common\TwigExtension;

class Injection extends \Twig_Extension {

  public function getName() {
    return 'ctek_common.injection';
  }

  public function getFilters() {
    return [
      new \Twig_SimpleFilter('inject', [$this, 'inject']),
      new \Twig_SimpleFilter('bare', [$this, 'bare']),
    ];
  }

  public function inject(array $renderArray, string $key, string $value) : array {
    $renderArray['#' . $key] = $value;
    return $renderArray;
  }

  public function bare(array $renderArray) : array {
    $renderArray['#tag'] = '';
    $renderArray['#wrapper'] = '';
    return $renderArray;
  }

}
