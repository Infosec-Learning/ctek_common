<?php
namespace Drupal\ctek_common\Logger;

use Drupal\Core\Logger\RfcLogLevel;
use Drush\Log\LogLevel;

class Logger extends \Drush\Log\Logger {

  public function log($level, $message, array $context = array()) {
    if ($level === RfcLogLevel::INFO) {
      $level = LogLevel::OK;
    }
    parent::log($level, $message, $context);
  }

}