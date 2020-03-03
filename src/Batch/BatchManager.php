<?php

namespace Drupal\ctek_common\Batch;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\ctek_common\Logger\BatchLogger;

class BatchManager {

  public function createBatch() {
    $batch = Batch::createBatch();
    if ($batch->isRunning()) {
      throw new \LogicException('Cannot create new batch while one is already running.');
    }
    return $batch;
  }

  public function run(Batch $batch) {
    if ($batch->isRunning()) {
      throw new \LogicException('Cannot run batch that is already running.');
    }
    batch_set($batch->toArray());
    if (PHP_SAPI === 'cli') {
      drush_backend_batch_process();
    }
  }

}
