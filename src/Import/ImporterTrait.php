<?php

namespace Drupal\ctek_common\Import;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\Markup;
use Drupal\ctek_common\Batch\Batch;
use Drupal\ctek_common\Batch\BatchManagerTrait;
use Drupal\ctek_common\Logger\BatchLog;
use Drupal\ctek_common\Logger\BatchLogger;
use Drupal\user\UserInterface;
use Drush\Utils\StringUtils;
use Psr\Log\LoggerInterface;

trait ImporterTrait {
  use BatchManagerTrait;

  protected static $logger;

  protected static function logger() : LoggerInterface {
    if (!static::$logger) {
      static::$logger = \Drupal::logger('ctek_import');
    }
    return static::$logger;
  }

  private static function getStateKey() {
    return 'ctek_common.import.' . strtolower(str_replace(' ', '_', static::getName()));
  }

  private static function getTimezone() {
    return new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
  }

  public static function haltOnUnhandledException(): bool {
    return FALSE;
  }

  public static function import(array $config = []) {
    $batch = static::batchManager()->createBatch();
    $batch->config->add(
      $config + [
        static::CONFIG_KEY_SEND_MAIL => FALSE,
      ]
    );
    $batch->config->set(static::STATE_KEY_HALTED, FALSE);
    $batch->addOperation([static::class, 'start']);
    /** @var \Drupal\ctek_common\Import\ImportJob $job */
    try {
      foreach (static::init($batch) as $job) {
        /** @var \Drupal\ctek_common\Import\ImportOperation $operation */
        foreach ($job->getOperations() as $operation) {
          $batch->addOperation([static::class, 'wrapCallback'], $operation->getCallback(), $operation);
        }
      }
    } catch (\Exception $e) {
      $message = Markup::create(StringUtils::interpolate("Importer failed to start: !importer\nEncountered unhandled exception: !message", [
        '!importer' => static::getName(),
        '!message' => $e->getMessage(),
      ]));
      \Drupal::messenger()->addError($message);
      static::logger()->error($message);
      if ($batch->config->get(static::CONFIG_KEY_SEND_MAIL)) {
        static::sendMail($batch);
      }
      return;
    }
    $batch->setFinished([static::class, 'finished']);
    static::batchManager()->run($batch);
  }

  public static function start(Batch $batch) {
    $logger = static::logger();
    $stateKey = static::getStateKey();
    $previous = \Drupal::state()->get($stateKey);
    if ($previous) {
      $timestamp = (new \DateTime())
        ->setTimestamp($previous['timestamp'])
        ->setTimezone(static::getTimezone())
        ->format(static::LOG_TIMESTAMP_FORMAT);
      $logger->info('Last run at {previoustimestamp} with status: {previousstatus}.', [
        'previoustimestamp' => $timestamp,
        'previousstatus' => static::STATUS_MESSAGES[$previous['status']],
      ]);
    } else {
      $logger->info('No previous run on record.');
    }
    \Drupal::state()->set($stateKey, [
      'timestamp' => time(),
      'status' => static::STATUS_INCOMPLETE,
    ]);
    static::logger()->notice('Running importer: !importer', ['!importer' => static::getName()]);
  }

  public static function wrapCallback(Batch $batch, callable $callback, ImportOperation $importOperation, ...$arguments) {
    try {
      static::logger()->debug('Calling: !callback', ['!callback' => $callback[0] . '::' . $callback[1]]);
      $callback($batch, $importOperation, ...$arguments);
    } catch (\Exception $e) {
      static::logger()->error('Callback threw an unhandled exception: !message', ['!message' => $e->getMessage()]);
      if (static::haltOnUnhandledException()) {
        $batch->halt();
      }
    }
  }

  public static function finished(Batch $batch) {
    $stateKey = static::getStateKey();
    $hasErrors = FALSE;
    $log = BatchLogger::getLog($batch->getId());
    foreach ($log as $entry) {
      if ($entry['severity'] < RfcLogLevel::NOTICE) {
        $hasErrors = TRUE;
        break;
      }
    }
    if ($batch->isHalted()) {
      \Drupal::messenger()->addError(StringUtils::interpolate('Importer encountered an unhandled exception and was halted: !importer', ['!importer' => static::getName()]));
    } elseif ($hasErrors) {
      \Drupal::messenger()->addWarning(StringUtils::interpolate('Finished running importer, with warnings or errors: !importer', ['!importer' => static::getName()]));
    } else {
      \Drupal::messenger()->addStatus(StringUtils::interpolate('Finished running importer: !importer', ['!importer' => static::getName()]));
    }
    if ($hasErrors && $batch->config->get(static::CONFIG_KEY_SEND_MAIL)) {
      static::sendMail($batch);
    }
    \Drupal::state()->set($stateKey, [
      'timestamp' => time(),
      'status' => $hasErrors ? static::STATUS_COMPLETE_WITH_ERRORS : static::STATUS_COMPLETE,
    ]);
  }

  protected static function sendMail(Batch $batch) {
    $notifyees = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties([
      'status' => 1,
      'roles' => static::IMPORT_NOTIFYEE_ROLE,
    ]);
    if (count($notifyees) === 0) {
      return;
    }
    $langCode = \Drupal::languageManager()
      ->getDefaultLanguage()
      ->getId();
    $notifyees = array_map(function(UserInterface $user){
      return $user->getEmail();
    }, $notifyees);
    /** @var \Drupal\Core\Mail\MailManagerInterface $mailManager */
    $mailManager = \Drupal::service('plugin.manager.mail');
    $mailManager->mail('ctek_common', static::MAIL_KEY_IMPORT_EXCEPTION, '', $langCode, [
      'notifyees' => $notifyees,
      'subject' => 'Warnings or Errors for importer: ' . static::getName(),
      'body' => static::formatLogsForMail(BatchLogger::getLog($batch->getId())),
    ]);
  }

  protected static function formatLogsForMail(BatchLog $log) {
    $body = [
      Markup::create("<h1>Logs</h1>"),
    ];
    $typeMap = [
      RfcLogLevel::DEBUG => 'Debug',
      RfcLogLevel::INFO => 'Info',
      RfcLogLevel::NOTICE => 'Notice',
      RfcLogLevel::WARNING => 'Warning',
      RfcLogLevel::ERROR => 'Error',
      RfcLogLevel::CRITICAL => 'Critical',
      RfcLogLevel::ALERT => 'Alert',
      RfcLogLevel::EMERGENCY => 'Emergency',
    ];
    $table = [
      '#type' => 'table',
      '#header' => [
        'Timestamp',
        'Type',
        'Message',
        'Memory',
      ],
      '#rows' => [],
    ];
    foreach ($log as $entry) {
      $row = [
        'data' => [
          $entry['timestamp'],
          $typeMap[$entry['severity']],
          Markup::create(nl2br($entry['message'])),
          number_format(intval($entry['memory']) / 1024 / 1024, 2) . ' MiB',
        ],
      ];
      switch ($entry['severity']) {
        case RfcLogLevel::DEBUG:
        case RfcLogLevel::INFO:
          $row['style'] = 'background:#EFEFEF;color:#888888;';
          break;
        case RfcLogLevel::WARNING:
          $row['style'] = 'background:#FFFF00;font-weight:bold;';
          break;
        case RfcLogLevel::ERROR:
        case RfcLogLevel::ALERT:
        case RfcLogLevel::CRITICAL:
        case RfcLogLevel::EMERGENCY:
          $row['style'] = 'background:#FF0000;color:#FFFFFF;font-weight:bold;';
          break;
        default:
          $row['style'] = 'background:#EEEEEE;color:#000000;font-weight:bold;';
          break;
      }
      $table['#rows'][] = $row;
    }
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $body[] = $renderer->renderRoot($table);
    return $body;
  }

}
