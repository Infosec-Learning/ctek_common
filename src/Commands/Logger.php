<?php

namespace Drupal\ctek_common\Commands;

use Consolidation\Log\UnstyledLogOutputStyler;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

class Logger extends \Drush\Log\Logger {

  public const LOG_LEVELS_TRIGGERING_EMAIL = [
    LogLevel::WARNING,
    LogLevel::ERROR,
    LogLevel::CRITICAL,
    LogLevel::ALERT,
    LogLevel::EMERGENCY,
  ];

  protected $logsForEmail = [];
  protected $hasLogsForEmail = FALSE;

  public function hasLogsForEmail() {
    return $this->hasLogsForEmail;
  }

  public function getLogsForEmail() {

    return $this->logsForEmail;
  }

  public function log($level, $message, array $context = []) {
    parent::log($level, $message, $context);
    if (in_array($level, static::LOG_LEVELS_TRIGGERING_EMAIL)) {
      $this->hasLogsForEmail = TRUE;
    }
    $entry = $this->buildEntry($level, $message, $context);
    $entry['timestamp'] = (new \DateTime())
        ->setTimestamp($entry['timestamp'])
        ->setTimezone(new \DateTimeZone('America/New_York'))
        ->format('Y-m-d h:i:s A T');
    $this->logsForEmail[] = $entry;
  }

}
