<?php

namespace Drupal\ctek_common\Import;

use Drupal\ctek_common\Batch\Batch;
use Symfony\Component\HttpFoundation\ParameterBag;

interface ImportJobInterface {

  public static function create(callable $callback);

  public function __construct(callable $callback);

  public function arguments() : ParameterBag;

  public function createOperations(Batch $batch, callable $wrapper = NULL);

}
