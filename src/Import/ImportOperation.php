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
    ImportJobInterface $job
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
   * @return \Drupal\ctek_common\Import\ImportJobInterface
   */
  public function getJob() : ImportJobInterface {
    return $this->job;
  }

}
