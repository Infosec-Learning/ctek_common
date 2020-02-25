<?php

namespace Drupal\ctek_common\Import;

use Drupal\ctek_common\Batch\Batch;
use Drupal\ctek_common\Logger\BatchLog;
use Psr\Log\LoggerInterface;

interface ImporterInterface {

  const BATCH_SIZE = 50;

  const LOG_TIMESTAMP_FORMAT = 'Y-m-d h:i:s A T';

  const STATUS_INCOMPLETE = 0;

  const STATUS_COMPLETE_WITH_ERRORS = 1;

  const STATUS_COMPLETE = 2;

  const STATUS_MESSAGES = [
    self::STATUS_INCOMPLETE => 'Incomplete',
    self::STATUS_COMPLETE_WITH_ERRORS => 'Complete, but with errors',
    self::STATUS_COMPLETE => 'Complete',
  ];

  const IMPORT_NOTIFYEE_ROLE = 'import_notifyees';

  const CONFIG_KEY_SEND_MAIL = 'sendMail';

  const STATE_KEY_HALTED = 'halted';

  const MAIL_KEY_IMPORT_EXCEPTION = 'import_exception';

  public static function getName() : string;

  public static function haltOnUnhandledException() : bool;

  /**
   * @param \Drupal\ctek_common\Batch\Batch $batch
   *
   * @return \Drupal\ctek_common\Import\ImportJob[]
   */
  public static function init(Batch $batch) : array;

  public static function import(array $config = []);

}
