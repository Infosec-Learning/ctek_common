<?php

namespace Drupal\ctek_common\EventSubscriber;

use Centretek\Environment\Environment;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Log;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides profiling information on all master HtmlResponse responses.
 *
 * @package Drupal\ctek_common\EventSubscriber
 */
class ProfilerSubscriber implements EventSubscriberInterface {

  const DATABASE_KEY = 'default';

  /** @var Log */
  protected static $log;

  protected static $start;

  /**
   * Begin logging database queries.
   */
  public static function startDatabaseLog() : void {
    self::$log->start(self::DATABASE_KEY);
  }

  /**
   * Stop logging database queries.
   */
  public static function stopDatabaseLog() : void {
    self::$log->end(self::DATABASE_KEY);
  }

  /**
   * Get the list of database queries.
   *
   * @return array
   */
  public static function getQueries() : array {
    return self::$log->get(self::DATABASE_KEY);
  }

  /**
   * Returns the time between KernelEvent::REQUEST and when we generate the
   * response.
   *
   * @return float
   */
  public static function getElapsedTime() : float {
    return microtime(true) - self::$start;
  }

  protected static function isRequestAMP(Request $request) {
    return $request->query->has('amp');
  }

  /**
   * Begin database logging, start the execution timer.
   *
   * @param RequestEvent $event
   */
  public function getRequest(RequestEvent $event) : void {
    if (!$event->isMasterRequest() || static::isRequestAMP($event->getRequest())) {
      return;
    }
    if (!self::$log) {
      self::$log = new Log();
      \Drupal::database()->setLogger(self::$log);
      self::startDatabaseLog();
    }
    self::$start = microtime(TRUE);
  }

  /**
   * Stop database logging, stop the execution timer, output the profiling
   * information.
   *
   * @param ResponseEvent $event
   *
   * @throws \Exception
   */
  public function getResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();
    if (!$event->isMasterRequest() || !$response instanceof HtmlResponse || static::isRequestAMP($event->getRequest())) {
      return;
    }
    $queries = ProfilerSubscriber::getQueries();
    ProfilerSubscriber::stopDatabaseLog();
    $metrics = Json::encode([
      'queryCount' => count($queries),
      'memoryUsage' => number_format(memory_get_peak_usage(TRUE) / 1024) . 'kB',
      'executionTime' => number_format(ProfilerSubscriber::getElapsedTime() * 1000) . 'ms',
      'cacheBackend' => (new \ReflectionClass(\Drupal::cache()))->getShortName(),
      'env' => Environment::getEnvironment(),
      //      'queries' => $queries,
    ]);
    $html = <<<EOHTML
<script type="application/json" data-drupal-selector="ctek-common-profiler-json">
{$metrics}
</script>
EOHTML;

    $event->getResponse()->setContent(str_replace('</body>', $html . '</body>', $event->getResponse()->getContent()));
  }

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents() : array {
    return [
      /** Run before everything. */
      KernelEvents::REQUEST => ['getRequest', 1001],
      /** Run after everything else EXCEPT BigPipe at -10000. */
      KernelEvents::RESPONSE => ['getResponse', -9999],
    ];
  }

}
