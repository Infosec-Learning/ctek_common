<?php

namespace Drupal\ctek_common\Import;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class ImportJobBase implements ImportJobInterface {

  public static function create(callable $callback) {
    return new static($callback);
  }

  protected $callback;

  protected $arguments;

  public function __construct(callable $callback) {
    $this->callback = $callback;
    $this->arguments = new ParameterBag();
  }

  public function getCallback() {
    return $this->callback;
  }

  public function arguments(): ParameterBag {
    return $this->arguments;
  }

}
