<?php

namespace Drupal\ctek_common\Batch;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\ctek_common\Logger\BatchLogger;
use Symfony\Component\HttpFoundation\ParameterBag;

class Batch {

  const STATE_KEY_ID = 'id';

  const STATE_KEY_RUNNING = 'running';

  const STATE_KEY_HALTED = 'halted';

  const STATE_KEY_FINISH_CALLBACK = 'finishCallback';

  const CONFIG_KEY_HALT_ON_UNHANDLED_EXCEPTION = 'haltOnUnhandledException';

  protected static $currentBatch;

  protected static function generateBatchId() {
    /** @var \Drupal\Component\Uuid\UuidInterface $uuidGenerator */
    $uuidGenerator = \Drupal::service('uuid');
    return $uuidGenerator->generate();
  }

  public static function getBatch() : ?Batch {
    if (!static::$currentBatch) {
      $sets = batch_get();
      if (isset($sets['current_set'])) {
        $context = $sets['sets'][$sets['current_set']];
        static::$currentBatch = static::createFromContext($context);
      } else {
        static::$currentBatch = new static();
      }
    }
    return static::$currentBatch;
  }

  protected static function createFromContext($context) {
    return new static(
      $context['results']['state'],
      $context['results']['config']
    );
  }

  public static function initialize(array $config, array $state, &$context) {
    $state[static::STATE_KEY_RUNNING] = TRUE;
    $context['results']['config'] = $config;
    $context['results']['state'] = $state;
  }

  public static function finalize($success, $results, $operations) {
    $batch = static::createFromContext(['results' => $results]);
    BatchLogger::setCurrentBatch($batch);
    $callback = $batch->state->get(static::STATE_KEY_FINISH_CALLBACK);
    if (is_callable($callback)) {
      $batch->state->set(static::STATE_KEY_RUNNING, FALSE);
      $batch->operations = $operations;
      $callback($batch);
    }
  }

  public static function wrapCallback($callback, ...$arguments) {
    $context = end($arguments);
    $batch = static::getBatch();
    if (!$batch || !$batch->isRunning()) {
      return;
    }
    try {
      // Remove direct access to context, call callback
      $callback($batch, ...array_slice($arguments, 0, -1));
    } catch (\Exception $e) {
      \Drupal::logger('default')->emergency('Callback threw an unhandled exception: !message', ['!message' => $e->getMessage()]);
      if ($batch->config->get(static::CONFIG_KEY_HALT_ON_UNHANDLED_EXCEPTION)) {
        $batch->halt();
      }
    }
    $context['results']['state'] = $batch->state->all();
    $context['results']['config'] = $batch->config->all();
  }

  public $state;

  public $config;

  protected $operations = [];

  protected function __construct(array $state = [], array $config = []) {
    $this->state = new ParameterBag($state);
    if (!$this->state->has(static::STATE_KEY_ID)) {
      $this->state->set(static::STATE_KEY_ID, static::generateBatchId());
    }
    if (!$this->state->has(static::STATE_KEY_RUNNING)) {
      $this->state->set(static::STATE_KEY_RUNNING, FALSE);
    }
    $this->config = new ParameterBag($config);
    if (!$this->config->has(static::CONFIG_KEY_HALT_ON_UNHANDLED_EXCEPTION)) {
      $this->config->set(static::CONFIG_KEY_HALT_ON_UNHANDLED_EXCEPTION, TRUE);
    }
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->state->get(static::STATE_KEY_ID);
  }

  public function isRunning() {
    return $this->state->get(static::STATE_KEY_RUNNING);
  }

  public function isHalted() {
    return $this->state->get(static::STATE_KEY_HALTED);
  }

  public function halt() {
    $this->state->set(static::STATE_KEY_RUNNING, FALSE);
    $this->state->set(static::STATE_KEY_HALTED, TRUE);
  }

  public function toArray() {
    $batchBuilder = new BatchBuilder();
    $batchBuilder->addOperation([static::class, 'initialize'], [
      $this->config->all(),
      $this->state->all(),
    ]);
    foreach ($this->operations as $operation) {
      $batchBuilder->addOperation($operation[0], $operation[1]);
    }
    $batchBuilder->setFinishCallback([static::class, 'finalize']);
    return $batchBuilder->toArray();
  }

  public function addOperation(callable $callback, ...$arguments) {
    array_unshift($arguments, $callback);
    if ($this->isRunning()) {
      $newBatch = (new BatchBuilder())
        ->addOperation([static::class, 'wrapCallback'], $arguments)
        ->toArray();
      batch_set($newBatch); // batch_set merges the operations array
    } else {
      $this->operations[] = [
        [static::class, 'wrapCallback'], $arguments
      ];
    }
  }

  public function setFinished(callable $callback) {
    if ($this->state->has(static::STATE_KEY_FINISH_CALLBACK)) {
      throw new \LogicException('Finished callback may only be set once.');
    }
    $this->state->set(static::STATE_KEY_FINISH_CALLBACK, $callback);
  }

}
