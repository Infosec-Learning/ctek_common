<?php

namespace Drupal\ctek_common\Batch;

class SimpleBatchJob extends ManagedBatchJobBase {

  public function createOperations(ManagedBatch $batch, callable $wrapper = NULL) {
    $batch->addOperation(
      $wrapper,
      $this->callback,
      $this->arguments
    );
  }

}
