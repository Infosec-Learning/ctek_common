<?php

namespace Drupal\ctek_common\Batch;

class BatchManager {

  public function getCurrentBatch() {
    $batch = ManagedBatch::getCurrentBatch();
    if ($batch && $batch->isRunning()) {
      return $batch;
    }
    return NULL;
  }

  public function createBatch() {
    $batch = ManagedBatch::createBatch();
    if ($batch->isRunning()) {
      throw new \LogicException('Cannot create new batch while one is already running.');
    }
    return $batch;
  }

  public function run(ManagedBatch $batch) {
    if ($batch->isRunning()) {
      throw new \LogicException('Cannot run batch that is already running.');
    }
    batch_set($batch->toArray());
    if (PHP_SAPI === 'cli') {
      drush_backend_batch_process();
    }
  }

}
