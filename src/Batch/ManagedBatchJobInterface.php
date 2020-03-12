<?php

namespace Drupal\ctek_common\Batch;

use Symfony\Component\HttpFoundation\ParameterBag;

interface ManagedBatchJobInterface {

  public static function create(callable $callback);

  public function __construct(callable $callback);

  public function arguments() : ParameterBag;

  public function createOperations(ManagedBatch $batch, callable $wrapper = NULL);

}
