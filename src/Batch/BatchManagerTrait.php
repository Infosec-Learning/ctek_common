<?php

namespace Drupal\ctek_common\Batch;

trait BatchManagerTrait {

  /** @var \Drupal\ctek_common\Batch\BatchManager */
  protected static $batchManager;

  public static function batchManager() {
    if (!static::$batchManager) {
      static::$batchManager = \Drupal::service('ctek_common.batch_manager');
    }
    return static::$batchManager;
  }

}
