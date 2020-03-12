<?php

namespace Drupal\ctek_common\Import;

use Drupal\ctek_common\Batch\ManagedBatch;
use Drupal\ctek_common\Batch\ManagedBatchProcessInterface;
use Drupal\ctek_common\Batch\ManagedBatchProcessTrait;
use Drupal\file\FileInterface;

abstract class FileUploadImportBase implements ManagedBatchProcessInterface {
  use ManagedBatchProcessTrait;

  const CONFIG_KEY_FILE = 'file';

  protected static $file;

  protected static function getFile() : FileInterface {
    return static::$file;
  }

  public static function init(ManagedBatch $batch): array {
    $file = $batch->config->get(static::CONFIG_KEY_FILE);
    if (!$file instanceof FileInterface) {
      throw new \InvalidArgumentException('Invalid file.');
    }
    $batch->config->remove(static::CONFIG_KEY_FILE);
    static::$file = $file;
    return [];
  }

}
