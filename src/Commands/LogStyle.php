<?php

namespace Drupal\ctek_common\Commands;

use Robo\Log\RoboLogStyle;

class LogStyle extends RoboLogStyle {

  const TIMESTAMP_STYLE = 'fg=magenta';
  const DATETIME_FORMAT = 'Y-m-d h:i:s A T';

  public function __construct($labelStyles = [], $messageStyles = []) {
    parent::__construct($labelStyles, $messageStyles);
  }

  protected function formatMessage($label, $message, $context, $taskNameStyle, $messageStyle = '') {
    $now = (new \DateTime())
      ->setTimezone(new \DateTimeZone('America/New_York'))
      ->format(static::DATETIME_FORMAT);
    $message = parent::formatMessage($label, $message, $context, $taskNameStyle, $messageStyle);
    $message = ' [' . $this->wrapFormatString($now, static::TIMESTAMP_STYLE) . ']' . $message;
    return $message;
  }

}
