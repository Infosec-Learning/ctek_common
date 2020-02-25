<?php

namespace Drupal\ctek_common\Import;

class ImportOperation {

  protected $callback;

  protected $page;

  protected $totalPages;

  protected $job;

  public function __construct(
    callable $callback,
    int $page,
    int $totalPages,
    ImportJob $job
  ) {
    $this->callback = $callback;
    $this->page = $page;
    $this->totalPages = $totalPages;
    $this->job = $job;
  }

  /**
   * @return callable
   */
  public function getCallback() : callable {
    return $this->callback;
  }

  /**
   * @return int
   */
  public function getPage(): int {
    return $this->page;
  }

  /**
   * @return int
   */
  public function getTotalPages() {
    return $this->totalPages;
  }


  /**
   * @return \Drupal\ctek_common\Import\ImportJob
   */
  public function getJob() : ImportJob {
    return $this->job;
  }

}
