<?php

namespace Drupal\ctek_common\Batch;

interface ManagedBatchProcessInterface {

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

  const LEGACY_IMPORT_NOTIFYEE_ROLE = 'import_notifyees';

  const CONFIG_KEY_SEND_MAIL = 'sendMail';

  const STATE_KEY_HALTED = 'halted';

  const MAIL_KEY_UNHANDLED_EXCEPTION = 'import_exception';

  public static function getName() : string;

  public static function haltOnUnhandledException() : bool;

  /**
   * @param \Drupal\ctek_common\Batch\ManagedBatch $batch
   *
   * @return \Drupal\ctek_common\Batch\ManagedBatchJobInterface[]
   */
  public static function init(ManagedBatch $batch) : array;

  public static function process(array $config = []);

  public static function getNotifyees(ManagedBatch $batch) : array;

}
