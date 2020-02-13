<?php

namespace Drupal\ctek_common;

use Stiphle\Throttle\TimeWindow;
use STS\Backoff\Backoff;
use STS\Backoff\Strategies\PolynomialStrategy;

class ReliabilityHelper {

  protected $timeWindow;

  /**
   * @var callable
   */
  protected $callback;

  protected $throttle;

  protected $key;

  protected $maxExecutions;

  protected $timePeriodMilliseconds;

  protected $waitCallback;

  protected $backoff;

  protected $backoffMaxAttempts;

  protected $backoffMilliseconds;

  protected $backoffDegree;

  protected $backoffErrorHandler;

  public function __construct(callable $callback) {
    $this->callback = $callback;
    $this->timeWindow = new TimeWindow();
  }

  public function throttle($key, $maxExecutions, $timePeriodMilliseconds, callable $waitCallback = NULL) {
    $this->throttle = TRUE;
    $this->maxExecutions = $maxExecutions;
    $this->timePeriodMilliseconds = $timePeriodMilliseconds;
    $this->waitCallback = $waitCallback;
    return $this;
  }

  public function tolerateFaults($maxAttempts = 5, $milliseconds = 500, $degree = 2, callable $errorHandler = NULL) {
    $this->backoff = TRUE;
    $this->backoffMaxAttempts = $maxAttempts;
    $this->backoffMilliseconds = $milliseconds;
    $this->backoffDegree = $degree;
    $this->backoffErrorHandler = $errorHandler;
    return $this;
  }

  public function execute() {
    if ($this->throttle && $this->maxExecutions > 0 && $this->timePeriodMilliseconds > 0) {
      $wait = $this->timeWindow->getEstimate($this->key, $this->maxExecutions, $this->timePeriodMilliseconds);
      if ($wait > 0 && $this->waitCallback) {
        ($this->waitCallback)($wait);
      }
      $this->timeWindow->throttle($this->key, $this->maxExecutions, $this->timePeriodMilliseconds);
    }
    if ($this->backoff) {
      $backoffStrategy = new PolynomialStrategy($this->backoffMilliseconds, $this->backoffDegree);
      $backoff = new Backoff($this->backoffMaxAttempts, $backoffStrategy);
      if ($this->backoffErrorHandler) {
        $backoff->setErrorHandler(function(\Exception $exception, $attempt, $maxAttempts) use ($backoffStrategy) {
          ($this->backoffErrorHandler)($exception, $attempt, $maxAttempts, $backoffStrategy);
        });
      }
      return $backoff->run($this->callback);
    } else {
      return ($this->callback)();
    }
  }

}
