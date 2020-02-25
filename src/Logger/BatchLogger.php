<?php

namespace Drupal\ctek_common\Logger;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\ctek_common\Batch\Batch;
use Drupal\ctek_common\Batch\BatchManager;
use Drupal\ctek_common\Batch\BatchManagerTrait;
use Psr\Log\LoggerInterface;

class BatchLogger implements LoggerInterface {
  use RfcLoggerTrait;
  use BatchManagerTrait;

  protected static $batch;

  public static function setCurrentBatch(Batch $batch) {
    static::$batch = $batch;
  }

  const LOG_FILE_PREFIX = 'temporary://batch-log-';

  protected $parser;

  protected $logFile;

  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  public function __destruct() {
    if ($this->logFile) {
      fclose($this->logFile);
    }
  }

  public function log($level, $message, array $context = []) {
    $batch = static::$batch ?: Batch::getBatch();
    if ($batch) {
      if (!$this->logFile) {
        $path = static::LOG_FILE_PREFIX . $batch->getId();
        $this->logFile = fopen($path, 'a');
      }
      $messagePlaceholders = $this->parser->parseMessagePlaceholders($message, $context);
      $message = empty($messagePlaceholders) ? $message : strtr($message, $messagePlaceholders);
      $message = [
        'message' => $message,
        'channel' => $context['channel'],
        'severity' => $level,
        'timestamp' => $context['timestamp'],
        'memory' => memory_get_peak_usage(TRUE),
      ];
      $message = Json::encode($message);
      fwrite($this->logFile, $message . PHP_EOL);
    }
  }

  public static function getLog($id) : BatchLog {
    return new BatchLog(new \SplFileObject(static::LOG_FILE_PREFIX . $id));
  }

}
