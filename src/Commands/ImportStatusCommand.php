<?php

namespace Drupal\ctek_common\Commands;

use Drupal\Core\Render\Markup;

class ImportStatusCommand extends ImportCommand {

  /**
   * @command ctek_common:check-import-status
   *
   * @param $name
   * @param $maxAge
   */
  public function checkStatus($name, $maxAge) {
    $logger = $this->logger();
    try {
      $maxAge = new \DateInterval($maxAge);
      $stateKey = $this->getStateKey($name);
      $previous = $this->state->get($stateKey);
      if (!$previous) {
        $logger->error('Importer {importname} has failed to run.', ['importname' => $name]);
      } else {
        $now = (new \DateTime())
          ->setTimezone($this->timezone);
        $cutoff = $now->sub($maxAge);
        $previousTimestamp = (new \DateTime())
          ->setTimestamp($previous['timestamp'])
          ->setTimezone($this->timezone);
        if ($previousTimestamp < $cutoff) {
          $logger->error('Scheduled import {importname} failed to run within the allotted time.', ['importname' => $name]);
          $logger->debug('Importer should have run after {cutoff}', [
            'cutoff' => $cutoff->format(static::DATETIME_FORMAT),
          ]);
          $logger->debug('Importer last ran at {previoustimestamp}', [
            'previoustimestamp' => $previousTimestamp->format(static::DATETIME_FORMAT),
          ]);
        }
      }
      if ($logger instanceof Logger && $logger->hasLogsForEmail()) {
        $title = 'Scheduled Import Problem';
        $body = [
          Markup::create("<h1>$title</h1>"),
        ];
        $body = array_merge(
          $body,
          $this->formatLogsForMail($logger->getLogsForEmail())
        );
        $this->mail($title, $body);
      }
    } catch (\Exception $e) {
      $logger->emergency($e->getMessage());
      $this->mail('Unable to Check Status', $this->formatLogsForMail($logger->getLogsForEmail()));
      return;
    }
  }

}
