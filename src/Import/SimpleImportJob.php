<?php

namespace Drupal\ctek_common\Import;

use Drupal\ctek_common\Batch\Batch;

class SimpleImportJob extends ImportJobBase {

  public function createOperations(Batch $batch, callable $wrapper = NULL) {
    $batch->addOperation(
      $wrapper,
      $this->callback,
      $this->arguments
    );
  }

}
