<?php

namespace Drupal\ctek_common\Logger;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\ctek_common\Batch\ManagedBatch;
use Drupal\ctek_common\Batch\BatchManagerTrait;
use Psr\Log\LoggerInterface;

class BatchLogger implements LoggerInterface {
  use RfcLoggerTrait;
  use BatchManagerTrait;

  protected static $batch;

  public static function setCurrentBatch(ManagedBatch $batch) {
    static::$batch = $batch;
  }

  protected static function getLogFilename($id) {
    return join('', [
      static::LOG_FILE_BASE_DIR,
      static::LOG_FILE_PREFIX,
      $id,
      static::LOG_FILE_SUFFIX
    ]);
  }

  const LOG_FILE_BASE_DIR = 'temporary://';
  const LOG_FILE_PREFIX = 'batch-log-';
  const LOG_FILE_SUFFIX = '.log';

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

  public function log($level, $message, array $context = []): void {
    $batch = static::$batch ?: static::batchManager()->getCurrentBatch();
    if ($batch) {
      if (!$this->logFile) {
        $this->logFile = fopen(static::getLogFilename($batch->getId()), 'a');
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
    return new BatchLog(new \SplFileObject(static::getLogFilename($id)));
  }

}
